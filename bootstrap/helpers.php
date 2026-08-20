<?php

use Illuminate\Http\JsonResponse;

if (! function_exists('tree')) {
    /**
     * @param  array<int, array<string, mixed>>  $data
     * @return array<int, array<string, mixed>>
     */
    function tree(
        array $data = [],
        string $parent_column = 'parent_id',
        string $id_column = 'id',
        int|string $root_parent_id = 0,
    ): array {
        $grouped = collect($data)->groupBy(
            fn (array $item): string => (string) ($item[$parent_column] ?? $root_parent_id),
        );

        $build = function (int|string $parentId) use (&$build, $grouped, $id_column): array {
            return collect($grouped->get((string) $parentId, []))
                ->map(function (array $item) use (&$build, $id_column): array {
                    $item['children'] = $build($item[$id_column] ?? 0);

                    return $item;
                })
                ->values()
                ->all();
        };

        return $build($root_parent_id);
    }
}

if (! function_exists('generation')) {
    function generation(array $data = [])
    {
        foreach ($data as $value) {
            yield $value;
        }
    }
}

if (! function_exists('generation_random_string')) {
    function generation_random_string(int $length = 1): string
    {
        $result = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        for ($index = 0; $index < $length; $index++) {
            $result .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $result;
    }
}

if (! function_exists('api_response')) {
    /**
     * @throws Exception
     */
    function api_response($data = null, $status = 200, array $headers = [], $options = 0): JsonResponse
    {
        $now = microtime(true);
        if ($data === null) {
            $data = (object) [];
        }
        $startedAt = defined('LARAVEL_START') ? LARAVEL_START : ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
        $rv = [
            'code' => $status,
            'data' => $data,
            'meta' => [
                'timestamp' => $now,
                'response_time' => $now - $startedAt,
            ],
        ];

        return response()->json($rv, $status, $headers, $options);
    }
}

if (! function_exists('is_url')) {
    /**
     * @throws Exception
     */
    function is_url(string $url): bool
    {
        return str_contains($url, 'http');
    }
}

if (! function_exists('is_phone_number')) {
    /**
     * 判断是否为手机号
     */
    function is_phone_number($value): bool
    {
        return (bool) preg_match('/^(1)\d{10}$/', $value);
    }
}

if (! function_exists('amap_picker')) {
    /**
     * 返回高德地图坐标拾取器的URL地址
     */
    function amap_picker(string $amapWebKey): string
    {
        if (blank($amapWebKey)) {
            return '<p style="font-size: 12px; color: #ef4444;">未配置高德 Web Key，请在 .env 中设置 AMAP_WEB_API_KEY。</p>';
        }

        return str_replace('__AMAP_KEY__', $amapWebKey, <<<'HTML'
<div id="attendance-rule-amap-picker" style="height: 320px; border-radius: 0.5rem; overflow: hidden;"></div>
<p style="margin-top: 0.5rem; font-size: 12px; color: #6b7280;">点击地图可自动回填到“允许的GPS位置”字段，格式为：经度,纬度</p>
<script>
(() => {
    const mapContainerId = 'attendance-rule-amap-picker';
    if (window.__attendanceRuleAmapPickerLoaded) {
        return;
    }
    window.__attendanceRuleAmapPickerLoaded = true;

    const parseCoordinate = (value) => {
        if (!value || typeof value !== 'string') {
            return null;
        }
        const parts = value.split(',');
        if (parts.length < 2) {
            return null;
        }
        const lng = Number(parts[0].trim());
        const lat = Number(parts[1].trim());
        if (Number.isNaN(lng) || Number.isNaN(lat)) {
            return null;
        }
        return { lng, lat };
    };

    const getGpsInput = () => document.querySelector('input[wire\\:model$="extra.allowed_gps_locations"]');

    const loadAmap = () => new Promise((resolve) => {
        if (window.AMap) {
            resolve();
            return;
        }
        const script = document.createElement('script');
        script.src = 'https://webapi.amap.com/maps?v=2.0&key=__AMAP_KEY__';
        script.async = true;
        script.onload = resolve;
        document.body.appendChild(script);
    });

    const init = async () => {
        await loadAmap();

        const container = document.getElementById(mapContainerId);
        if (!container || container.dataset.initialized === '1') {
            return;
        }
        container.dataset.initialized = '1';

        const input = getGpsInput();
        const initial = parseCoordinate(input?.value);
        const center = initial ? [initial.lng, initial.lat] : [116.397428, 39.90923];

        const map = new AMap.Map(mapContainerId, {
            zoom: initial ? 15 : 11,
            center: center,
        });

        let marker = null;
        if (initial) {
            marker = new AMap.Marker({
                position: [initial.lng, initial.lat],
                map: map,
            });
        }

        map.on('click', (event) => {
            const lng = Number(event.lnglat.getLng().toFixed(6));
            const lat = Number(event.lnglat.getLat().toFixed(6));

            if (marker) {
                marker.setPosition([lng, lat]);
            } else {
                marker = new AMap.Marker({
                    position: [lng, lat],
                    map: map,
                });
            }

            const gpsInput = getGpsInput();
            if (!gpsInput) {
                return;
            }
            gpsInput.value = `${lng},${lat}`;
            gpsInput.dispatchEvent(new Event('input', { bubbles: true }));
            gpsInput.dispatchEvent(new Event('change', { bubbles: true }));
        });
    };

    setTimeout(init, 0);
})();
</script>
HTML
        );
    }
}
