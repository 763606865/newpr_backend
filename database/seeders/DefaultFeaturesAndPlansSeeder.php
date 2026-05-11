<?php

namespace Database\Seeders;

use App\Models\Oa\System\Feature;
use App\Models\Oa\System\Menu;
use App\Models\Oa\System\Plan;
use Illuminate\Database\Seeder;

class DefaultFeaturesAndPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 创建默认菜单
        $attendanceMenu = Menu::firstOrCreate(
            ['menu_code' => 'attendance'],
            [
                'parent_id' => 0,
                'menu_name' => '打卡签到',
                'menu_code' => 'attendance',
                'menu_type' => 1,
                'path' => '/attendance',
                'component' => 'Attendance',
                'icon' => 'clock',
                'sort' => 1,
                'visible' => 1,
                'style' => [],
                'extra' => [],
            ]
        );

        // 子菜单
        $subMenus = [
            [
                'parent_id' => $attendanceMenu->id,
                'menu_name' => '考勤打卡',
                'menu_code' => 'attendance_checkin',
                'menu_type' => 1,
                'path' => '/attendance/checkin',
                'component' => 'Checkin',
                'icon' => 'check-circle',
                'sort' => 1,
                'visible' => 1,
            ],
            [
                'parent_id' => $attendanceMenu->id,
                'menu_name' => '补卡申请',
                'menu_code' => 'attendance_supplement',
                'menu_type' => 1,
                'path' => '/attendance/supplement',
                'component' => 'Supplement',
                'icon' => 'edit',
                'sort' => 2,
                'visible' => 1,
            ],
            [
                'parent_id' => $attendanceMenu->id,
                'menu_name' => '考勤记录',
                'menu_code' => 'attendance_records',
                'menu_type' => 1,
                'path' => '/attendance/records',
                'component' => 'Records',
                'icon' => 'list',
                'sort' => 3,
                'visible' => 1,
            ],
        ];

        foreach ($subMenus as $subMenu) {
            Menu::firstOrCreate(
                ['menu_code' => $subMenu['menu_code']],
                array_merge($subMenu, ['style' => [], 'extra' => []])
            );
        }

        // OA审批父菜单
        $oaMenu = Menu::firstOrCreate(
            ['menu_code' => 'oa_approval'],
            [
                'parent_id' => 0,
                'menu_name' => 'OA审批',
                'menu_code' => 'oa_approval',
                'menu_type' => 1,
                'path' => '/oa/approval',
                'component' => 'Approval',
                'icon' => 'approval',
                'sort' => 2,
                'visible' => 1,
                'style' => [],
                'extra' => [],
            ]
        );

        // 创建默认功能点
        $features = [
            [
                'feature_name' => '打卡签到',
                'feature_code' => 'attendance_checkin',
                'menu_id' => $attendanceMenu->id,
                'description' => '员工打卡签到功能',
                'status' => 1,
            ],
            [
                'feature_name' => 'OA审批',
                'feature_code' => 'oa_approval',
                'menu_id' => $oaMenu->id,
                'description' => 'OA系统审批流程',
                'status' => 1,
            ],
        ];

        foreach ($features as $feature) {
            Feature::firstOrCreate(
                ['feature_code' => $feature['feature_code']],
                $feature
            );
        }

        // 创建试用plan
        $plan = Plan::firstOrCreate(
            ['plan_code' => 'trial_plan'],
            [
                'plan_name' => '试用方案',
                'plan_code' => 'trial_plan',
                'price' => 0.00,
                'duration' => 30, // 30天试用
                'sort' => 1,
                'remark' => '免费试用方案，包含基本功能',
                'status' => 1,
                'extra' => [],
            ]
        );

        // 关联功能点到plan
        $featureIds = Feature::whereIn('feature_code', ['attendance_checkin', 'oa_approval'])->pluck('id');
        $plan->features()->sync($featureIds);
    }
}
