<?php

namespace App\Features\Post\Services;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostExcelService
{
    /**
     * Maximum number of rows processed per import (protects the server).
     */
    public const MAX_IMPORT_ROWS = 200;

    public function __construct(
        protected PostService $postService
    ) {}

    /**
     * Build a Spreadsheet instance containing posts data.
     * All text cells are written as strings to prevent spreadsheet formula injection.
     *
     * @param Collection<int, Post> $posts
     */
    public function buildSpreadsheet(Collection $posts, string $sheetTitle = 'Artikel', bool $withAuthor = false): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($sheetTitle, 0, 31));

        $headers = ['No', 'Judul', 'Kategori', 'Ringkasan', 'Konten', 'Status', 'Tanggal Publikasi', 'Dibuat'];
        if ($withAuthor) {
            $headers[] = 'Penulis';
        }

        $lastColumn = chr(ord('A') + count($headers) - 1);

        foreach ($headers as $index => $header) {
            $sheet->setCellValueExplicit(chr(ord('A') + $index).'1', $header, DataType::TYPE_STRING);
        }

        $headerRange = "A1:{$lastColumn}1";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4338CA');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowNumber = 2;
        foreach ($posts as $index => $post) {
            $values = [
                $index + 1,
                $post->title,
                $post->category,
                $post->excerpt ?? '-',
                $post->content,
                $post->is_published ? 'Dipublikasikan' : 'Draf',
                $post->published_at ? $post->published_at->format('Y-m-d H:i') : '-',
                $post->created_at ? $post->created_at->format('Y-m-d H:i') : '-',
            ];

            if ($withAuthor) {
                $values[] = $post->user?->name ?? '-';
            }

            foreach ($values as $columnIndex => $value) {
                $sheet->setCellValueExplicit(
                    chr(ord('A') + $columnIndex).$rowNumber,
                    (string) $value,
                    $columnIndex === 0 ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING
                );
            }

            $rowNumber++;
        }

        $lastRow = max(2, $rowNumber - 1);
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setWidth(in_array($column, ['D', 'E'], true) ? 50 : 20);
        }

        return $spreadsheet;
    }

    /**
     * Export posts as a downloadable streamed response.
     *
     * @param Collection<int, Post> $posts
     */
    public function exportToResponse(Collection $posts, string $filename = 'artikel.xlsx', bool $withAuthor = false): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet($posts, 'Artikel', $withAuthor);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Import posts from an uploaded spreadsheet file path.
     * Expected columns: A=No, B=Judul, C=Kategori, D=Ringkasan, E=Konten, F=Status.
     *
     * @return array{imported: int, skipped: int}
     */
    public function import(string $filePath, int $userId): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $imported = 0;
        $skipped = 0;

        // Skip the header row.
        array_shift($rows);

        foreach ($rows as $row) {
            if ($imported + $skipped >= self::MAX_IMPORT_ROWS) {
                break;
            }

            $title = trim((string) ($row['B'] ?? ''));

            if ($title === '') {
                $skipped++;

                continue;
            }

            $excerpt = $this->cleanText($row['D'] ?? null);
            $content = trim((string) ($row['E'] ?? ''));

            $this->postService->createPost($userId, [
                'title' => mb_substr($title, 0, 255),
                'category' => $this->parseCategory((string) ($row['C'] ?? '')),
                'excerpt' => $excerpt,
                'content' => $content !== '' && $content !== '-' ? $content : ($excerpt ?? $title),
                'is_published' => $this->parsePublished((string) ($row['F'] ?? '')),
            ]);

            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
        ];
    }

    /**
     * Normalise an optional text cell ("-" and blanks become null).
     */
    protected function cleanText(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' || $text === '-' ? null : $text;
    }

    /**
     * Resolve the category name case-insensitively, falling back to "Umum".
     */
    protected function parseCategory(string $raw): string
    {
        $needle = mb_strtolower(trim($raw));

        foreach (PostCategory::cases() as $case) {
            if (mb_strtolower($case->value) === $needle) {
                return $case->value;
            }
        }

        return PostCategory::UMUM->value;
    }

    /**
     * Interpret the status cell (Dipublikasikan / Published / 1 / ya ...) as boolean.
     */
    protected function parsePublished(string $raw): bool
    {
        return in_array(mb_strtolower(trim($raw)), ['dipublikasikan', 'published', 'publish', 'publik', 'ya', 'yes', 'true', '1'], true);
    }
}
