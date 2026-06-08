<?php

namespace Tests\Unit\Libs\Amap;

use App\Libs\Amap\Amap;
use App\Libs\Amap\AmapException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AmapTest extends TestCase
{
    public function test_geocode_returns_parsed_results(): void
    {
        Http::fake([
            'https://restapi.amap.com/v3/geocode/geo*' => Http::response([
                'status' => '1',
                'info' => 'OK',
                'geocodes' => [
                    [
                        'formatted_address' => '北京市朝阳区阜通东大街6号',
                        'country' => '中国',
                        'province' => '北京市',
                        'city' => '北京市',
                        'district' => '朝阳区',
                        'adcode' => '110105',
                        'location' => '116.481028,39.990339',
                        'level' => '门牌号',
                    ],
                ],
            ]),
        ]);

        $results = $this->amap()->geocode('北京市朝阳区阜通东大街6号', '北京');

        $this->assertCount(1, $results);
        $this->assertSame('北京市朝阳区阜通东大街6号', $results[0]->formattedAddress);
        $this->assertSame('110105', $results[0]->adcode);
        $this->assertSame(116.481028, $results[0]->location['lng']);
        $this->assertSame(39.990339, $results[0]->location['lat']);
    }

    public function test_geocode_first_returns_null_when_no_match(): void
    {
        Http::fake([
            'https://restapi.amap.com/v3/geocode/geo*' => Http::response([
                'status' => '1',
                'info' => 'OK',
                'geocodes' => [],
            ]),
        ]);

        $this->assertNull($this->amap()->geocodeFirst('不存在的地址'));
    }

    public function test_regeocode_returns_parsed_address(): void
    {
        Http::fake([
            'https://restapi.amap.com/v3/geocode/regeo*' => Http::response([
                'status' => '1',
                'info' => 'OK',
                'regeocode' => [
                    'formatted_address' => '北京市朝阳区望京街道阜通东大街6号',
                    'addressComponent' => [
                        'province' => '北京市',
                        'city' => [],
                        'district' => '朝阳区',
                        'adcode' => '110105',
                    ],
                ],
            ]),
        ]);

        $result = $this->amap()->regeocode(116.481028, 39.990339);

        $this->assertNotNull($result);
        $this->assertSame('北京市朝阳区望京街道阜通东大街6号', $result->formattedAddress);
        $this->assertSame('北京市', $result->province());
        $this->assertSame('朝阳区', $result->district());
        $this->assertSame('110105', $result->adcode());
    }

    public function test_request_throws_when_api_key_is_missing(): void
    {
        $this->expectException(AmapException::class);
        $this->expectExceptionMessage('未配置高德地图 Key');

        (new Amap(['key' => '']))->geocode('测试地址');
    }

    public function test_request_throws_when_api_returns_error_status(): void
    {
        Http::fake([
            'https://restapi.amap.com/v3/geocode/geo*' => Http::response([
                'status' => '0',
                'info' => 'INVALID_USER_KEY',
            ]),
        ]);

        $this->expectException(AmapException::class);
        $this->expectExceptionMessage('INVALID_USER_KEY');

        $this->amap()->geocode('测试地址');
    }

    private function amap(): Amap
    {
        return new Amap([
            'key' => 'test-amap-key',
            'base_uri' => 'https://restapi.amap.com',
            'timeout' => 3,
        ]);
    }
}
