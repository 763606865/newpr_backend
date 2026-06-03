<?php

namespace App\Rc\Requests;

use App\Rc\Requests\Concerns\ResumeRequestRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResumeStoreRequest extends FormRequest
{
    use ResumeRequestRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return $this->resumeFieldRules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->resumeFieldAttributes();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->cityAreaCodeMessages();
    }
}
