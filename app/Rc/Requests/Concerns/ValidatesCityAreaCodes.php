<?php

namespace App\Rc\Requests\Concerns;

use App\Enums\AreaLevel;
use Illuminate\Validation\Rule;

trait ValidatesCityAreaCodes
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function cityAreaCodeFieldRules(bool $sometimes = false): array
    {
        return [
            'native_place' => $this->cityAreaCodeRule(100, $sometimes),
            'household_register' => $this->cityAreaCodeRule(100, $sometimes),
            'current_city_code' => $this->cityAreaCodeRule(32, $sometimes),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected function cityAreaCodeRule(int $maxLength = 32, bool $sometimes = false): array
    {
        $rules = array_values(array_filter([
            $sometimes ? 'sometimes' : null,
            'nullable',
            'string',
            'max:'.$maxLength,
            Rule::exists('areas', 'code')->where('level', AreaLevel::City->value),
        ]));

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function cityAreaCodeMessages(): array
    {
        return [
            'native_place.exists' => '籍贯必须选择有效的市级行政区划。',
            'household_register.exists' => '户口所在地必须选择有效的市级行政区划。',
            'current_city_code.exists' => '现居住城市编码必须选择有效的市级行政区划。',
        ];
    }
}
