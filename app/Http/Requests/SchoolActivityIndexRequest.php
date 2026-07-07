<?php

namespace App\Http\Requests;

use App\Enums\RcSchoolActivityOrganizerType;
use App\Enums\RcSchoolActivityType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolActivityIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $types = $this->input('types');

        if (is_string($types) && $types !== '') {
            $this->merge([
                'types' => array_values(array_filter(array_map('trim', explode(',', $types)))),
            ]);
        }

        $organizerTypes = $this->input('organizer_types');

        if (is_string($organizerTypes) && $organizerTypes !== '') {
            $this->merge([
                'organizer_types' => array_values(array_filter(array_map('trim', explode(',', $organizerTypes)))),
            ]);
        }
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
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'keyword' => ['sometimes', 'nullable', 'string', 'max:100'],
            'start_time' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s'],
            'end_time' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s'],
            'type' => ['sometimes', 'nullable', 'integer', Rule::enum(RcSchoolActivityType::class)],
            'types' => ['sometimes', 'array'],
            'types.*' => [
                'integer',
                'distinct',
                Rule::enum(RcSchoolActivityType::class),
            ],
            'organizer_type' => ['sometimes', 'nullable', 'string', Rule::enum(RcSchoolActivityOrganizerType::class)],
            'organizer_types' => ['sometimes', 'array'],
            'organizer_types.*' => [
                'string',
                'distinct',
                Rule::enum(RcSchoolActivityOrganizerType::class),
            ],
            'is_hot' => ['sometimes', 'nullable', 'boolean'],
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
            'page' => '页码',
            'per_page' => '每页条数',
            'keyword' => '关键词',
            'start_time' => '开始时间',
            'end_time' => '结束时间',
            'type' => '活动类型',
            'types' => '活动类型列表',
            'organizer_type' => '主办方类型',
            'organizer_types' => '主办方类型列表',
            'is_hot' => '热门活动',
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

    /**
     * @return array<string, mixed>
     */
    public function searchFilters(): array
    {
        $filters = [
            'keyword' => $this->validated('keyword'),
            'start_time' => $this->validated('start_time'),
            'end_time' => $this->validated('end_time'),
            'type' => $this->validated('type'),
            'is_hot' => $this->validated('is_hot'),
            'region_code' => $this->regionCode(),
        ];

        $types = $this->types();

        if ($types !== []) {
            $filters['types'] = $types;
        }

        $organizerTypes = $this->organizerTypes();

        if ($organizerTypes !== []) {
            $filters['organizer_types'] = $organizerTypes;
        } elseif (filled($this->validated('organizer_type'))) {
            $filters['organizer_type'] = $this->validated('organizer_type');
        }

        return $filters;
    }

    /**
     * @return array<int, int>
     */
    public function types(): array
    {
        $types = $this->validated('types', []);

        if (! is_array($types)) {
            return [];
        }

        return array_values(array_map(static fn (mixed $type): int => (int) $type, $types));
    }

    /**
     * @return array<int, string>
     */
    public function organizerTypes(): array
    {
        $organizerTypes = $this->validated('organizer_types', []);

        if (! is_array($organizerTypes)) {
            return [];
        }

        return array_values(array_map(static fn (mixed $organizerType): string => (string) $organizerType, $organizerTypes));
    }
}
