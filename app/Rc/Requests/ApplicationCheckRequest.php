<?php

namespace App\Rc\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplicationCheckRequest extends FormRequest
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
        return [
            'job_id' => [
                'required',
                'integer',
                Rule::exists('rc_jobs', 'id')->whereNull('deleted_at'),
            ],
            'candidate_user_id' => [
                'nullable',
                'integer',
                'required_without:resume_id',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
            'resume_id' => [
                'nullable',
                'integer',
                'required_without:candidate_user_id',
                Rule::exists('rc_resumes', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'job_id' => '职位',
            'candidate_user_id' => '求职者用户',
            'resume_id' => '简历',
        ];
    }
}
