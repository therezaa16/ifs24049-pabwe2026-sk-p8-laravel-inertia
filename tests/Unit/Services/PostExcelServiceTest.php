<?php

namespace Tests\Unit\Services;

use App\Features\Post\Models\Post;
use App\Features\Post\Repositories\PostRepository;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class PostExcelServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PostExcelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PostExcelService(new PostService(new PostRepository()));
    }

    public function test_build_spreadsheet_and_export_response(): void
    {
        $user = User::factory()->create(['name' => 'Penulis Excel']);
        $posts = Post::factory()->count(2)->create(['user_id' => $user->id]);
        Post::factory()->published()->create(['user_id' => $user->id, 'excerpt' => null]);
        $posts = Post::with('user')->get();

        $spreadsheet = $this->service->buildSpreadsheet($posts, 'Artikel Saya');
        $sheet = $spreadsheet->getActiveSheet();
        $this->assertSame('Artikel Saya', $sheet->getTitle());
        $this->assertSame('Judul', $sheet->getCell('B1')->getValue());
        $this->assertSame('Status', $sheet->getCell('F1')->getValue());
        $this->assertSame('', (string) $sheet->getCell('I1')->getValue());

        $withAuthor = $this->service->buildSpreadsheet($posts, 'Semua', true);
        $this->assertSame('Penulis', $withAuthor->getActiveSheet()->getCell('I1')->getValue());
        $this->assertSame('Penulis Excel', $withAuthor->getActiveSheet()->getCell('I2')->getValue());

        $response = $this->service->exportToResponse($posts, 'test.xlsx');
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();
        $this->assertNotEmpty($content);

        $adminResponse = $this->service->exportToResponse($posts, 'admin.xlsx', true);
        $this->assertInstanceOf(StreamedResponse::class, $adminResponse);
    }

    public function test_formulas_are_written_as_plain_strings(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id, 'title' => '=1+1', 'content' => '@SUM(A1)']);

        $spreadsheet = $this->service->buildSpreadsheet(Post::with('user')->get());
        $cell = $spreadsheet->getActiveSheet()->getCell('B2');

        $this->assertSame('=1+1', $cell->getValue());
        $this->assertSame('s', $cell->getDataType());
        $this->assertSame($post->id, Post::first()->id);
    }

    public function test_import_spreadsheet(): void
    {
        $user = User::factory()->create();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['No', 'Judul', 'Kategori', 'Ringkasan', 'Konten', 'Status'],
            // standard row
            [1, 'Artikel Impor 1', 'teknologi', 'Ringkas satu', '## Isi 1', 'Published'],
            // unknown category -> Umum, "-" excerpt -> null, "-" content -> fallback to title, unknown status -> draft
            [2, 'Artikel Impor 2', 'Kategori Aneh', '-', '-', 'apa saja'],
            // empty content -> falls back to excerpt
            [3, 'Artikel Impor 3', 'Bisnis', 'Ringkasan jadi isi', '', 'ya'],
            // empty title -> skipped
            [4, '', 'Opini', 'x', 'y', 'Draf'],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'test_excel_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $result = $this->service->import($path, $user->id);
        @unlink($path);

        $this->assertSame(['imported' => 3, 'skipped' => 1], $result);

        $this->assertDatabaseHas('posts', ['user_id' => $user->id, 'title' => 'Artikel Impor 1', 'category' => 'Teknologi', 'is_published' => true, 'content' => '## Isi 1']);
        $this->assertDatabaseHas('posts', ['user_id' => $user->id, 'title' => 'Artikel Impor 2', 'category' => 'Umum', 'excerpt' => null, 'content' => 'Artikel Impor 2', 'is_published' => false]);
        $this->assertDatabaseHas('posts', ['user_id' => $user->id, 'title' => 'Artikel Impor 3', 'content' => 'Ringkasan jadi isi', 'is_published' => true]);
    }

    public function test_import_is_capped_at_max_rows(): void
    {
        $user = User::factory()->create();

        $rows = [['No', 'Judul', 'Kategori', 'Ringkasan', 'Konten', 'Status']];
        for ($i = 1; $i <= PostExcelService::MAX_IMPORT_ROWS + 5; $i++) {
            $rows[] = [$i, "Artikel {$i}", 'Umum', null, "Isi {$i}", 'Draf'];
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows);

        $path = tempnam(sys_get_temp_dir(), 'test_excel_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $result = $this->service->import($path, $user->id);
        @unlink($path);

        $this->assertSame(PostExcelService::MAX_IMPORT_ROWS, $result['imported']);
        $this->assertSame(PostExcelService::MAX_IMPORT_ROWS, Post::count());
    }
}
