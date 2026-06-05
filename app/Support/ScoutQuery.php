<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class ScoutQuery
{
    public static function timestamp(mixed $value): ?int
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->getTimestamp();
        }

        return Carbon::parse((string) $value)->getTimestamp();
    }

    /**
     * 转义 Elasticsearch query_string 特殊字符，避免用户输入破坏查询语法。
     */
    public static function escape(string $keyword): string
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return '';
        }

        return preg_replace('/([+\-=&|><!(){}[\]^"~*?:\\\\\/])/', '\\\\$1', $keyword) ?? $keyword;
    }
}
