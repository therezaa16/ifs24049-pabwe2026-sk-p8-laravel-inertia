<?php

namespace Tests\Unit\DTO;

use App\Features\Ai\DTO\GeneratedPostItem;
use PHPUnit\Framework\TestCase;

class GeneratedPostItemTest extends TestCase
{
    public function test_dto_properties_and_to_array(): void
    {
        $dto = new GeneratedPostItem(
            title: 'Belajar Laravel',
            category: 'Teknologi',
            excerpt: 'Ringkasan',
            content: '## Isi',
        );

        $this->assertSame('Belajar Laravel', $dto->title);
        $this->assertSame([
            'title' => 'Belajar Laravel',
            'category' => 'Teknologi',
            'excerpt' => 'Ringkasan',
            'content' => '## Isi',
        ], $dto->toArray());
    }

    public function test_dto_defaults(): void
    {
        $dto = new GeneratedPostItem('Judul');

        $this->assertSame('Umum', $dto->category);
        $this->assertNull($dto->excerpt);
        $this->assertSame('', $dto->content);
    }
}
