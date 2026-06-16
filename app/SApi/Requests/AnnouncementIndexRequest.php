<?php

namespace App\SApi\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AnnouncementIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'province_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'city_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'district_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'created_from' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s'],
            'created_to' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s', 'after_or_equal:created_from'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'pagination_enabled' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'province_code' => '省份编码',
            'city_code' => '城市编码',
            'district_code' => '区县编码',
            'created_from' => '创建时间起始',
            'created_to' => '创建时间截止',
            'page' => '页码',
            'per_page' => '每页条数',
            'pagination_enabled' => '是否分页',
        ];
    }

    public function regionCode(): ?string
    {
        $provinceCode = $this->validated('province_code');
        $cityCode = $this->validated('city_code');
        $districtCode = $this->validated('district_code');

        if (is_string($districtCode) && $districtCode !== '') {
            return $districtCode;
        }

        if (is_string($cityCode) && $cityCode !== '') {
            return $cityCode;
        }

        if (is_string($provinceCode) && $provinceCode !== '') {
            return $provinceCode;
        }

        return null;
    }
}
