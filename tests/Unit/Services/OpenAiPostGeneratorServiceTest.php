<?php

namespace Tests\Unit\Services;

use App\Features\Ai\Services\OpenAiPostGeneratorService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiPostGeneratorServiceTest extends TestCase
{
    protected function service(): OpenAiPostGeneratorService
    {
        return new OpenAiPostGeneratorService('https://api.openai.com/v1', 'mock-key', 'gpt-4o-mini');
    }

    public function test_empty_prompt_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prompt tidak boleh kosong.');
        $this->service()->generatePosts('   ');
    }

    public function test_successful_generation(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'posts' => [
                                ['title' => 'Artikel 1', 'category' => 'Teknologi', 'excerpt' => 'Ringkasan 1', 'content' => '## Isi 1'],
                                ['title' => 'Artikel 2', 'category' => 'Kategori Palsu', 'excerpt' => '  ', 'content' => 'Isi 2'],
                                ['title' => 'Artikel 3'],
                            ],
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $results = $this->service()->generatePosts('Tulis 3 artikel', 3);

        $this->assertCount(3, $results);
        $this->assertSame('Artikel 1', $results[0]->title);
        $this->assertSame('Teknologi', $results[0]->category);
        $this->assertSame('Ringkasan 1', $results[0]->excerpt);
        $this->assertSame('## Isi 1', $results[0]->content);

        $this->assertSame('Umum', $results[1]->category);
        $this->assertNull($results[1]->excerpt);

        $this->assertSame('Umum', $results[2]->category);
        $this->assertSame('', $results[2]->content);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer mock-key')
            && $request['model'] === 'gpt-4o-mini'
            && str_contains($request['messages'][0]['content'], 'tepat 3 artikel'));
    }

    public function test_api_failure_throws_exception(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response(['error' => ['message' => 'Invalid API Key provided']], 401),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API Error: Invalid API Key provided');
        $this->service()->generatePosts('Rencana artikel');
    }

    public function test_api_failure_without_error_message_uses_status_code(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response('boom', 500),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request failed with status code 500');
        $this->service()->generatePosts('Rencana artikel');
    }

    public function test_parse_content_with_markdown_fences_and_plain_list(): void
    {
        $raw = "```json\n[{\"title\": \"Code Fence\", \"content\": \"Isi\"}]\n```";

        $results = $this->service()->parseContent($raw);

        $this->assertCount(1, $results);
        $this->assertSame('Code Fence', $results[0]->title);
    }

    public function test_parse_content_invalid_json(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AI mengembalikan format respon yang tidak valid.');
        $this->service()->parseContent('Bukan JSON');
    }

    public function test_parse_content_empty_posts(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tidak ada artikel yang berhasil di-generate dari AI.');
        $this->service()->parseContent('{"posts": []}');
    }

    public function test_parse_content_no_valid_items(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Format artikel yang dikembalikan AI tidak sesuai spesifikasi.');
        $this->service()->parseContent('{"posts": [{"bad_key": "val"}, "teks"]}');
    }

    public function test_constructor_handles_swapped_endpoint_and_key(): void
    {
        Http::fake([
            'https://ai.example.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '{"posts":[{"title":"Artikel"}]}']]],
            ]),
        ]);

        // Key diberikan sebagai URL, endpoint diberikan sebagai rahasia
        $service = new OpenAiPostGeneratorService('sk-secret-key', 'https://ai.example.com/v1');

        $this->assertCount(1, $service->generatePosts('Rencana'));
    }

    public function test_constructor_auto_prefixes_https_and_reads_config_defaults(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '{"posts":[{"title":"Artikel"}]}']]],
            ]),
        ]);

        $service = new OpenAiPostGeneratorService('api.openai.com/v1', 'mock-key');
        $this->assertCount(1, $service->generatePosts('Rencana'));

        config(['openai.endpoint' => 'https://api.openai.com/v1', 'openai.key' => 'cfg-key', 'openai.model' => 'cfg-model']);
        $fromConfig = new OpenAiPostGeneratorService();
        $this->assertCount(1, $fromConfig->generatePosts('Rencana'));

        Http::assertSent(fn ($request) => $request['model'] === 'cfg-model');
    }
}
