<?php

namespace App\B\Requests;

use App\Models\Position;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $positionTable = (new Position)->getTable();
        $companyId = $this->user('b')?->token()?->responsible_id;
        $positionId = $this->route('position') ?? $this->route('id');

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique($positionTable, 'code')
                    ->where(function ($query) use ($companyId): void {
                        if ($companyId) {
                            $query->where('company_id', $companyId);
                        }
                    })
                    ->whereNull('deleted_at')
                    ->ignore($positionId),
            ],
            'is_leader' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'remark' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_leader' => $this->boolean('is_leader'),
            'sort' => $this->input('sort', 0),
        ]);
    }

    public function attributes(): array
    {
        return [
            'name' => '岗位名称',
            'code' => '岗位编码',
            'is_leader' => '管理岗',
            'sort' => '排序',
            'remark' => '备注',
        ];
    }
}
