<?php

namespace Database\Seeders;

use App\Models\Cms\ArticleCategory;
use App\Models\Cms\ArticleTag;
use Illuminate\Database\Seeder;

class DefaultCmsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->initArticles();
    }

    private function initArticles(): void
    {
        $this->resolveCmsArticleCategories();
        $this->resolveCmsArticleTags();
    }

    private function resolveCmsArticleCategories(): void
    {
        $data = [
            ['name' => '校园资讯', 'slug' => 'campus'],
            ['name' => '新闻时事', 'slug' => 'news'],
            ['name' => '就业动态', 'slug' => 'employment'],
        ];
        foreach ($data as $datum) {
            ArticleCategory::query()->firstOrCreate($datum);
        }
    }

    private function resolveCmsArticleTags(): void
    {
        $data = [
            ['name' => '社招'],
            ['name' => '兼职'],
            ['name' => '招考'],
            ['name' => '校招'],
            ['name' => '新闻'],
        ];
        foreach ($data as $datum) {
            ArticleTag::query()->firstOrCreate($datum);
        }
    }
}
