<?php

namespace App\Rc\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobApplicationStoreRequest extends FormRequest
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
        $userId = $this->user()?->id;

        return [
            'resume_id' => [
                'nullable',
                'integer',
                Rule::exists('rc_resumes', 'id')
                    ->where(static fn ($query) => $query
                        ->where('user_id', $userId)
                        ->whereNull('deleted_at')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'resume_id' => '简历',
        ];
    }
}
