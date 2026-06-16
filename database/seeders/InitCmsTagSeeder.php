<?php

namespace Database\Seeders;

use App\Enums\CmsStatus;
use App\Enums\CmsTagCategory;
use App\Models\Cms\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class InitCmsTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('cms_tags')) {
            $this->command?->warn('cms_tags 表不存在，已跳过 InitCmsTagSeeder。');

            return;
        }

        foreach ($this->tagGroups() as $group) {
            foreach ($group['tags'] as $sort => $tag) {
                Tag::query()->updateOrCreate(
                    [
                        'category' => $group['category'],
                        'name' => $tag['name'],
                    ],
                    [
                        'slug' => $tag['slug'],
                        'status' => CmsStatus::Enabled,
                        'sort' => $sort + 1,
                    ]
                );
            }
        }
    }

    /**
     * @return array<int, array{category: string, tags: array<int, array{name: string, slug: string}>}>
     */
    private function tagGroups(): array
    {
        return [
            [
                'category' => CmsTagCategory::ExamRecruitment->value,
                'tags' => [
                    ['name' => '国家公务员', 'slug' => 'national-civil-service'],
                    ['name' => '地方公务员', 'slug' => 'local-civil-service'],
                    ['name' => '事业单位', 'slug' => 'public-institution'],
                    ['name' => '教师', 'slug' => 'teacher-recruitment'],
                    ['name' => '医疗卫生', 'slug' => 'medical-healthcare'],
                    ['name' => '银行', 'slug' => 'bank-recruitment'],
                    ['name' => '公安', 'slug' => 'police-recruitment'],
                    ['name' => '农商行', 'slug' => 'rural-commercial-bank'],
                    ['name' => '三支一扶', 'slug' => 'three-supports-one-assistance'],
                    ['name' => '村官', 'slug' => 'village-official'],
                    ['name' => '法检', 'slug' => 'law-judiciary'],
                    ['name' => '公选遴选', 'slug' => 'public-selection'],
                    ['name' => '选调生', 'slug' => 'selected-transfer-student'],
                    ['name' => '国企招聘', 'slug' => 'state-owned-enterprise'],
                    ['name' => '会计取证', 'slug' => 'accounting-certification'],
                    ['name' => '研究生', 'slug' => 'graduate-admission'],
                    ['name' => '教师资格', 'slug' => 'teacher-qualification'],
                    ['name' => '社区工作者', 'slug' => 'community-worker'],
                    ['name' => '西部计划', 'slug' => 'western-plan'],
                    ['name' => '军队文职', 'slug' => 'military-civilian'],
                ],
            ],
            [
                'category' => CmsTagCategory::SchoolExam->value,
                'tags' => [
                    ['name' => '中考', 'slug' => 'zhongkao'],
                    ['name' => '高考', 'slug' => 'gaokao'],
                    ['name' => '考研', 'slug' => 'graduate-exam'],
                    ['name' => '考博', 'slug' => 'doctoral-exam'],
                    ['name' => '专升本', 'slug' => 'junior-to-undergraduate'],
                    ['name' => '保研', 'slug' => 'recommended-postgraduate'],
                    ['name' => '同等学力申硕', 'slug' => 'equivalent-master-admission'],
                    ['name' => '雅思', 'slug' => 'ielts'],
                    ['name' => '托福', 'slug' => 'toefl'],
                    ['name' => 'GRE', 'slug' => 'gre'],
                    ['name' => 'GMAT', 'slug' => 'gmat'],
                ],
            ],
            [
                'category' => CmsTagCategory::CertificateExam->value,
                'tags' => [
                    ['name' => '计算机等级', 'slug' => 'computer-rank'],
                    ['name' => 'CET-4', 'slug' => 'cet-4'],
                    ['name' => 'CET-6', 'slug' => 'cet-6'],
                    ['name' => '初级会计', 'slug' => 'junior-accountant'],
                    ['name' => '中级会计', 'slug' => 'intermediate-accountant'],
                    ['name' => '高级会计', 'slug' => 'senior-accountant'],
                ],
            ],
            [
                'category' => CmsTagCategory::Rc->value,
                'tags' => [
                    ['name' => '社招', 'slug' => 'social-recruitment'],
                    ['name' => '校招', 'slug' => 'campus-recruitment'],
                    ['name' => '兼职', 'slug' => 'part-time'],
                    ['name' => '劳务派遣', 'slug' => 'labor-dispatch'],
                ],
            ],
        ];
    }
}
