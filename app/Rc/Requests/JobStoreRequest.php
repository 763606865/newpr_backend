<?php

namespace App\Rc\Requests;

use App\Rc\Requests\Concerns\JobRequestRules;
use Illuminate\Foundation\Http\FormRequest;

class JobStoreRequest extends FormRequest
{
    use JobRequestRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->jobFieldRules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->jobFieldAttributes();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->jobFieldMessages();
    }
}
