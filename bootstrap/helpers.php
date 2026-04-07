<?php

if (!function_exists('tree')) {
    function tree(array $data = [], string $parent_column = 'parent_id'): array
    {
        return collect($data)->where($parent_column, 0)->map(function ($item) use ($parent_column, $data) {
            $item['children'] = collect($data)->where($parent_column, $item['id'])->values();
            return $item;
        })->toArray();
    }
}

if (!function_exists('generation')) {
    function generation(array $data = [])
    {
        foreach ($data as $value) {
            yield $value;
        }
    }
}

if (!function_exists('generation_random_string')) {
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

if (!function_exists('api_response')) {
    /**
     * @throws Exception
     */
    function api_response($data = null, $status = 200, array $headers = [], $options = 0): \Illuminate\Http\JsonResponse
    {
        $now = microtime(true);
        if ($data === null) {
            $data = (object)[];
        }
        $rv = [
            'code' => $status,
            'data' => $data,
            'meta' => [
                'timestamp' => $now,
                'response_time' => $now - LARAVEL_START,
            ],
        ];
        return response()->json($rv, $status, $headers, $options);
    }
}

if (!function_exists('is_url')) {
    /**
     * @throws Exception
     */
    function is_url(string $url): bool
    {
        return str_contains($url, "http");
    }
}

if (!function_exists('is_phone_number')) {
    /**
     * 判断是否为手机号
     *
     * @param $value
     * @return bool
     */
    function is_phone_number($value): bool
    {
        return (bool)preg_match('/^(1)\d{10}$/', $value);
    }
}
