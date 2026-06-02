<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RcIndustrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('rc_industries')) {
            $this->command?->warn('rc_industries 表不存在，已跳过 RcIndustrySeeder。');

            return;
        }

        // 使用 delete 避免某些环境下 truncate 受外键限制报错。
        DB::table('rc_industries')->delete();

        $now = now();
        $industries = $this->normalizeIndustryTree($this->industryTree());

        foreach ($industries as $index => $industry) {
            $this->insertIndustry($industry, null, $index + 1, $now);
        }
    }

    /**
     * 将外部字段（id/value/label/children）映射为本地字段（name/code/children）。
     *
     * @param  array<int, array<string, mixed>>  $sourceTree
     * @return array<int, array{name:string, code:string, children:array<int, array<string, mixed>>}>
     */
    private function normalizeIndustryTree(array $sourceTree): array
    {
        $normalized = [];

        foreach ($sourceTree as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = isset($item['label']) ? trim((string) $item['label']) : '';
            $code = isset($item['value']) ? trim((string) $item['value']) : '';

            if ($name === '' || $code === '') {
                continue;
            }

            $children = $item['children'];

            $normalized[] = [
                'name' => $name,
                'code' => $code,
                // 明确忽略外部 id/parentid，层级关系使用本表自增 id 建立。
                'children' => is_array($children) ? $this->normalizeIndustryTree($children) : [],
            ];
        }

        return $normalized;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function industryTree(): array
    {
        return [
            [
                'id' => 5109,
                'value' => '117KncnL',
                'label' => '不限',
                'children' => [
                    [
                        'id' => 5110,
                        'value' => '117GnnqW',
                        'label' => '不限行业',
                        'children' => null,
                    ],
                ],
            ],
            [
                'id' => 2468,
                'value' => '113Dd81T',
                'label' => '互联网/IT/电子/通信',
                'children' => [
                    ['id' => 2469, 'value' => '112Nc6TK', 'label' => '电子商务', 'children' => null],
                    ['id' => 2470, 'value' => '117JFwsG', 'label' => '数据服务', 'children' => null],
                    ['id' => 2471, 'value' => '11UtCzc', 'label' => '旅游', 'children' => null],
                    ['id' => 2472, 'value' => '116zkT1d', 'label' => '社交网络', 'children' => null],
                    ['id' => 2473, 'value' => '116E4wTG', 'label' => '新零售', 'children' => null],
                    ['id' => 2474, 'value' => '115J2HbA', 'label' => '计算机软件', 'children' => null],
                    ['id' => 2475, 'value' => '115WiyLu', 'label' => '运营商/增值服务', 'children' => null],
                    ['id' => 2476, 'value' => '117M1JK2', 'label' => '游戏', 'children' => null],
                    ['id' => 2477, 'value' => '1171PGjv', 'label' => '医疗健康', 'children' => null],
                    ['id' => 2478, 'value' => '11vUEJj', 'label' => '分类信息', 'children' => null],
                    ['id' => 2480, 'value' => '115Rjsd', 'label' => '智能硬件', 'children' => null],
                    ['id' => 2481, 'value' => '113nQQxJ', 'label' => '计算机硬件', 'children' => null],
                    ['id' => 2482, 'value' => '116Fyp6K', 'label' => '电子/半导体/集成电路', 'children' => null],
                    ['id' => 2483, 'value' => '112gnYKB', 'label' => '媒体', 'children' => null],
                    ['id' => 2484, 'value' => '114zYqMe', 'label' => '生活服务', 'children' => null],
                    ['id' => 2485, 'value' => '113dTrQp', 'label' => '音乐/视频/阅读', 'children' => null],
                    ['id' => 2486, 'value' => '112oeFTZ', 'label' => '企业服务', 'children' => null],
                    ['id' => 2487, 'value' => '112xjoBu', 'label' => '移动互联网', 'children' => null],
                    ['id' => 2488, 'value' => '11qUb84', 'label' => '计算机服务', 'children' => null],
                    ['id' => 2489, 'value' => '115XyaNe', 'label' => '消费电子', 'children' => null],
                    ['id' => 2490, 'value' => '1153tYSk', 'label' => '广告营销', 'children' => null],
                    ['id' => 2491, 'value' => '11NNhSa', 'label' => 'O2O', 'children' => null],
                    ['id' => 2492, 'value' => '1153KgCA', 'label' => '在线教育', 'children' => null],
                    ['id' => 2493, 'value' => '114g3N3d', 'label' => '信息安全', 'children' => null],
                    ['id' => 2494, 'value' => '116Z4EPV', 'label' => '互联网', 'children' => null],
                    ['id' => 2495, 'value' => '113KME2y', 'label' => '通信/网络设备', 'children' => null],
                ],
            ],
            [
                'id' => 2496,
                'value' => '112F1eTN',
                'label' => '广告/传媒/文化/体育',
                'children' => [
                    ['id' => 2497, 'value' => '112sMWTm', 'label' => '广告/公关/会展', 'children' => null],
                    ['id' => 2498, 'value' => '116qXARP', 'label' => '新闻/出版', 'children' => null],
                    ['id' => 2499, 'value' => '113XdUPo', 'label' => '广播/影视', 'children' => null],
                    ['id' => 2500, 'value' => '11Zf6wh', 'label' => '文化/体育/娱乐', 'children' => null],
                ],
            ],
            [
                'id' => 2501,
                'value' => '116fKh5g',
                'label' => '金融',
                'children' => [
                    ['id' => 2502, 'value' => '115hNH2p', 'label' => '银行', 'children' => null],
                    ['id' => 2503, 'value' => '116m1ecL', 'label' => '信托', 'children' => null],
                    ['id' => 2504, 'value' => '1135Adg', 'label' => '保险', 'children' => null],
                    ['id' => 2505, 'value' => '112DBcHU', 'label' => '互联网金融', 'children' => null],
                    ['id' => 2506, 'value' => '1167XfcV', 'label' => '证券/期货', 'children' => null],
                    ['id' => 2507, 'value' => '117J12Hm', 'label' => '投资/融资', 'children' => null],
                    ['id' => 2508, 'value' => '113ix8C1', 'label' => '基金', 'children' => null],
                    ['id' => 2509, 'value' => '11iRk4q', 'label' => '租赁/拍卖/典当/担保', 'children' => null],
                ],
            ],
            [
                'id' => 2510,
                'value' => '117FymvK',
                'label' => '教育培训',
                'children' => [
                    ['id' => 2511, 'value' => '114u797R', 'label' => '学前教育', 'children' => null],
                    ['id' => 2512, 'value' => '114fyQMB', 'label' => '院校', 'children' => null],
                    ['id' => 2513, 'value' => '11438oo5', 'label' => '培训机构', 'children' => null],
                    ['id' => 2514, 'value' => '1132jvTq', 'label' => '学术/科研', 'children' => null],
                ],
            ],
            [
                'id' => 2515,
                'value' => '1141dncU',
                'label' => '制药/医疗',
                'children' => [
                    ['id' => 2516, 'value' => '112PJJFk', 'label' => '制药', 'children' => null],
                    ['id' => 2517, 'value' => '115Xub9L', 'label' => '医疗/护理/卫生', 'children' => null],
                    ['id' => 2518, 'value' => '113LwjbW', 'label' => '医疗设备/器械', 'children' => null],
                ],
            ],
            [
                'id' => 2519,
                'value' => '115wuyBZ',
                'label' => '交通/物流/贸易/零售',
                'children' => [
                    ['id' => 2520, 'value' => '114urWa4', 'label' => '交通/运输', 'children' => null],
                    ['id' => 2521, 'value' => '117Rs1sA', 'label' => '物流/仓储', 'children' => null],
                    ['id' => 2522, 'value' => '117U91pF', 'label' => '批发/零售', 'children' => null],
                    ['id' => 2523, 'value' => '1151fugy', 'label' => '贸易/进出口', 'children' => null],
                ],
            ],
            [
                'id' => 2524,
                'value' => '112RCBrp',
                'label' => '专业服务',
                'children' => [
                    ['id' => 2525, 'value' => '115W6QrQ', 'label' => '咨询', 'children' => null],
                    ['id' => 2526, 'value' => '116FhDWb', 'label' => '法律', 'children' => null],
                    ['id' => 2527, 'value' => '1124GsQ2', 'label' => '翻译', 'children' => null],
                    ['id' => 2528, 'value' => '115rWYUm', 'label' => '人力资源服务', 'children' => null],
                    ['id' => 2529, 'value' => '112zbGVF', 'label' => '财务/审计/税务', 'children' => null],
                    ['id' => 2530, 'value' => '115a2kMv', 'label' => '检测/认证', 'children' => null],
                    ['id' => 2531, 'value' => '114E2U18', 'label' => '专利/商标/知识产权', 'children' => null],
                    ['id' => 2532, 'value' => '115GBuHN', 'label' => '其他专业服务', 'children' => null],
                ],
            ],
            [
                'id' => 2533,
                'value' => '113eDtPR',
                'label' => '房地产/建筑',
                'children' => [
                    ['id' => 2534, 'value' => '116iKKmt', 'label' => '房地产开发', 'children' => null],
                    ['id' => 2535, 'value' => '115YxZ3S', 'label' => '工程施工', 'children' => null],
                    ['id' => 2536, 'value' => '113Uz9zk', 'label' => '建筑设计', 'children' => null],
                    ['id' => 2537, 'value' => '113vPqhv', 'label' => '装修装饰', 'children' => null],
                    ['id' => 2538, 'value' => '112w5NGu', 'label' => '建材', 'children' => null],
                    ['id' => 2539, 'value' => '1157FJ1U', 'label' => '地产经纪/中介', 'children' => null],
                    ['id' => 2540, 'value' => '112xks14', 'label' => '物业服务', 'children' => null],
                ],
            ],
            [
                'id' => 2541,
                'value' => '1141Lw3A',
                'label' => '汽车',
                'children' => [
                    ['id' => 2542, 'value' => '113P35Ut', 'label' => '汽车生产', 'children' => null],
                    ['id' => 2543, 'value' => '113Acdqe', 'label' => '汽车零部件', 'children' => null],
                    ['id' => 2544, 'value' => '115m827F', 'label' => '4S店/后市场', 'children' => null],
                ],
            ],
            [
                'id' => 2545,
                'value' => '113qD1mL',
                'label' => '机械/制造',
                'children' => [
                    ['id' => 2546, 'value' => '11q46p3', 'label' => '机械设备/机电/重工', 'children' => null],
                    ['id' => 2547, 'value' => '115UHCAA', 'label' => '仪器仪表/工业自动化', 'children' => null],
                    ['id' => 2548, 'value' => '112z2dqJ', 'label' => '原材料及加工/模具', 'children' => null],
                    ['id' => 2549, 'value' => '1151DSAg', 'label' => '印刷/包装/造纸', 'children' => null],
                    ['id' => 2550, 'value' => '114pJhT3', 'label' => '船舶/航空/航天', 'children' => null],
                ],
            ],
            [
                'id' => 2551,
                'value' => '113JFXM3',
                'label' => '消费品',
                'children' => [
                    ['id' => 2552, 'value' => '1174uTtA', 'label' => '食品/饮料/烟酒', 'children' => null],
                    ['id' => 2553, 'value' => '116Dc8io', 'label' => '日化', 'children' => null],
                    ['id' => 2554, 'value' => '114teevr', 'label' => '服装/纺织/皮革', 'children' => null],
                    ['id' => 2555, 'value' => '116KXZoN', 'label' => '家具/家电/家居', 'children' => null],
                    ['id' => 2556, 'value' => '115L7Ytr', 'label' => '玩具/礼品', 'children' => null],
                    ['id' => 2557, 'value' => '1155NdoK', 'label' => '珠宝/首饰', 'children' => null],
                    ['id' => 2558, 'value' => '115hDAv9', 'label' => '工艺品/收藏品', 'children' => null],
                    ['id' => 2559, 'value' => '113Yi97J', 'label' => '办公用品及设备', 'children' => null],
                ],
            ],
            [
                'id' => 2560,
                'value' => '115gXYp9',
                'label' => '服务业',
                'children' => [
                    ['id' => 2561, 'value' => '117X8JLc', 'label' => '餐饮', 'children' => null],
                    ['id' => 2562, 'value' => '1159DrFV', 'label' => '酒店', 'children' => null],
                    ['id' => 2563, 'value' => '115xBZKt', 'label' => '旅游', 'children' => null],
                    ['id' => 2564, 'value' => '113wSTBX', 'label' => '美容/美发', 'children' => null],
                    ['id' => 2565, 'value' => '11tu9J9', 'label' => '婚庆/摄影', 'children' => null],
                    ['id' => 2566, 'value' => '112xF8f2', 'label' => '其他服务业', 'children' => null],
                    ['id' => 2567, 'value' => '113tUnWT', 'label' => '休闲/娱乐', 'children' => null],
                    ['id' => 2568, 'value' => '115XMVFw', 'label' => '回收/维修', 'children' => null],
                ],
            ],
            [
                'id' => 2569,
                'value' => '1132f3Cu',
                'label' => '能源/化工/环保',
                'children' => [
                    ['id' => 2570, 'value' => '112WpjXs', 'label' => '石油/石化', 'children' => null],
                    ['id' => 2571, 'value' => '114AmHVf', 'label' => '化工', 'children' => null],
                    ['id' => 2572, 'value' => '115emrvd', 'label' => '矿产/地质', 'children' => null],
                    ['id' => 2573, 'value' => '114RtV8h', 'label' => '采掘/冶炼', 'children' => null],
                    ['id' => 2574, 'value' => '112yUhHv', 'label' => '电力/热力/燃气/水利', 'children' => null],
                    ['id' => 2575, 'value' => '11GxQT6', 'label' => '新能源', 'children' => null],
                    ['id' => 2576, 'value' => '115vxu3u', 'label' => '环保', 'children' => null],
                ],
            ],
            [
                'id' => 2577,
                'value' => '11rvXjK',
                'label' => '政府/非盈利机构/其他',
                'children' => [
                    ['id' => 2578, 'value' => '113PKZ21', 'label' => '政府/公共事业', 'children' => null],
                    ['id' => 2579, 'value' => '112gmh9e', 'label' => '非盈利机构', 'children' => null],
                    ['id' => 2580, 'value' => '113ifRbw', 'label' => '农/林/牧/渔', 'children' => null],
                    ['id' => 2581, 'value' => '11KYUA1', 'label' => '其他行业', 'children' => null],
                ],
            ],
        ];
    }

    /**
     * @param  array{name:string, code:string, children:array<int, array<string, mixed>>}  $industry
     */
    private function insertIndustry(array $industry, ?int $parentId, int $sort, mixed $now): void
    {
        $dbId = DB::table('rc_industries')->insertGetId([
            'name' => $industry['name'],
            'code' => $industry['code'],
            'parent_id' => $parentId,
            'sort' => $sort,
            // 仅使用本表自增 ID 建立层级关系，不依赖外部来源 ID。
            'extra' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($industry['children'] as $childSort => $child) {
            $this->insertIndustry($child, $dbId, $childSort + 1, $now);
        }
    }
}
