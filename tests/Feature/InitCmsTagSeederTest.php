<?php

namespace Tests\Feature;

use App\Enums\CmsTagCategory;
use App\Models\Cms\Tag;
use Database\Seeders\InitCmsTagSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitCmsTagSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_recruitment_and_exam_tags(): void
    {
        $this->seed(InitCmsTagSeeder::class);

        $this->assertSame(41, Tag::query()->count());
        $this->assertSame(20, Tag::query()->forCategory(CmsTagCategory::ExamRecruitment->value)->count());
        $this->assertSame(11, Tag::query()->forCategory(CmsTagCategory::SchoolExam->value)->count());
        $this->assertSame(6, Tag::query()->forCategory(CmsTagCategory::CertificateExam->value)->count());
        $this->assertSame(4, Tag::query()->forCategory(CmsTagCategory::Rc->value)->count());

        $this->assertTrue(
            Tag::query()
                ->forCategory(CmsTagCategory::ExamRecruitment->value)
                ->where('name', '国家公务员')
                ->where('slug', 'national-civil-service')
                ->exists()
        );
        $this->assertTrue(
            Tag::query()
                ->forCategory(CmsTagCategory::SchoolExam->value)
                ->where('name', '高考')
                ->where('slug', 'gaokao')
                ->exists()
        );
        $this->assertTrue(
            Tag::query()
                ->forCategory(CmsTagCategory::CertificateExam->value)
                ->where('name', 'CET-4')
                ->where('slug', 'cet-4')
                ->exists()
        );
        $this->assertTrue(
            Tag::query()
                ->forCategory(CmsTagCategory::Rc->value)
                ->where('name', '校招')
                ->where('slug', 'campus-recruitment')
                ->exists()
        );
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed(InitCmsTagSeeder::class);
        $this->seed(InitCmsTagSeeder::class);

        $this->assertSame(41, Tag::query()->count());
    }
}
