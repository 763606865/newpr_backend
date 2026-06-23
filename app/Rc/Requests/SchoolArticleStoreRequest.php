<?php

namespace App\Rc\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class SchoolArticleStoreRequest extends SchoolArticleFormRequest
{
    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return $this->articleRules(requireTitle: true);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->articleAttributes();
    }
}
