<?php

namespace App\Rc\Requests\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesRegionCodes
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function regionCodeRules(bool $sometimes = false): array
    {
        $prefix = $sometimes ? ['sometimes'] : [];

        return [
            'province_code' => [...$prefix, 'nullable', 'string', 'max:30', Rule::exists('areas', 'code')],
            'city_code' => [...$prefix, 'nullable', 'string', 'max:30', Rule::exists('areas', 'code')],
            'district_code' => [...$prefix, 'nullable', 'string', 'max:30', Rule::exists('areas', 'code')],
        ];
    }
}
