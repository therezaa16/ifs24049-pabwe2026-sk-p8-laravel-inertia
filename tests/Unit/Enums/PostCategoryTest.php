<?php

namespace Tests\Unit\Enums;

use App\Features\Post\Enums\PostCategory;
use PHPUnit\Framework\TestCase;

class PostCategoryTest extends TestCase
{
    public function test_values_contain_all_six_categories(): void
    {
        $this->assertSame(
            ['Teknologi', 'Pendidikan', 'Gaya Hidup', 'Bisnis', 'Opini', 'Umum'],
            PostCategory::values()
        );
    }

    public function test_labels(): void
    {
        $this->assertSame('Teknologi & Koding', PostCategory::TEKNOLOGI->label());
        $this->assertSame('Pendidikan & Tutorial', PostCategory::PENDIDIKAN->label());
        $this->assertSame('Gaya Hidup & Produktivitas', PostCategory::GAYA_HIDUP->label());
        $this->assertSame('Bisnis & Karir', PostCategory::BISNIS->label());
        $this->assertSame('Opini & Pemikiran', PostCategory::OPINI->label());
        $this->assertSame('Umum', PostCategory::UMUM->label());
    }

    public function test_badge_classes(): void
    {
        $this->assertStringContainsString('blue', PostCategory::TEKNOLOGI->badgeClass());
        $this->assertStringContainsString('emerald', PostCategory::PENDIDIKAN->badgeClass());
        $this->assertStringContainsString('pink', PostCategory::GAYA_HIDUP->badgeClass());
        $this->assertStringContainsString('amber', PostCategory::BISNIS->badgeClass());
        $this->assertStringContainsString('purple', PostCategory::OPINI->badgeClass());
        $this->assertStringContainsString('slate', PostCategory::UMUM->badgeClass());
    }

    public function test_options_are_formatted_for_frontend(): void
    {
        $options = PostCategory::options();

        $this->assertCount(6, $options);
        $this->assertSame(['value' => 'Umum', 'label' => 'Umum', 'badgeClass' => PostCategory::UMUM->badgeClass()], $options[5]);
    }
}
