<?php

namespace App\Libs\Amap;

use App\Libs\Amap\Data\GeocodeResult;
use App\Libs\Amap\Data\RegeocodeResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class Amap
{
    public function __construct(protected array $config) {}

    /**
     * 地理编码：地址转坐标。
     *
     * @param string $address
     * @param string|null $city
     * @return array<int, GeocodeResult>
     * @throws AmapException
     * @throws ConnectionException
     */
    public function geocode(string $address, ?string $city = null): array
    {
        $query = ['address' => $address];

        if (filled($city)) {
            $query['city'] = $city;
        }

        $body = $this->request('/v3/geocode/geo', $query);
        $geocodes = $body['geocodes'] ?? [];

        if (! is_array($geocodes)) {
            return [];
        }

        return array_values(array_map(
            fn (array $item): GeocodeResult => GeocodeResult::fromArray($item),
            $geocodes,
        ));
    }

    public function geocodeFirst(string $address, ?string $city = null): ?GeocodeResult
    {
        return $this->geocode($address, $city)[0] ?? null;
    }

    /**
     * 逆地理编码：坐标转地址。
     *
     * @param float|string $longitude
     * @param float|string $latitude
     * @param array<string, mixed> $options
     * @return RegeocodeResult|null
     * @throws AmapException
     */
    public function regeocode(float|string $longitude, float|string $latitude, array $options = []): ?RegeocodeResult
    {
        $query = array_merge([
            'location' => sprintf('%s,%s', $longitude, $latitude),
        ], $options);

        $body = $this->request('/v3/geocode/regeo', $query);
        $regeocode = $body['regeocode'] ?? null;

        if (! is_array($regeocode)) {
            return null;
        }

        return RegeocodeResult::fromArray($regeocode);
    }

    /**
     * @param string $path
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     * @throws AmapException
     * @throws ConnectionException
     */
    protected function request(string $path, array $query): array
    {
        $key = (string) ($this->config['key'] ?? '');

        if (blank($key)) {
            throw new AmapException('未配置高德地图 Key，请在 .env 中设置 AMAP_KEY。');
        }

        $response = $this->httpClient()->get($path, array_merge($query, [
            'key' => $key,
            'output' => 'JSON',
        ]));

        if (! $response->successful()) {
            throw new AmapException('高德地图 API 请求失败：HTTP '.$response->status());
        }

        /** @var array<string, mixed>|null $body */
        $body = $response->json();

        if (! is_array($body)) {
            throw new AmapException('高德地图 API 返回了无效响应。');
        }

        if (($body['status'] ?? '0') !== '1') {
            throw new AmapException((string) ($body['info'] ?? '高德地图 API 返回错误'));
        }

        return $body;
    }

    protected function httpClient(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) ($this->config['base_uri'] ?? 'https://restapi.amap.com'), '/'))
            ->acceptJson()
            ->timeout((int) ($this->config['timeout'] ?? 5));
    }
}
