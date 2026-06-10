<?php

namespace App\Rc\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OcrBusinessLicenseRequest extends FormRequest
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
            'file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,bmp,webp', 'max:10240', 'required_without:url'],
            'url' => ['nullable', 'url', 'max:2048', 'required_without:file'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->hasFile('file') && filled($this->input('url'))) {
                $validator->errors()->add('file', 'file 与 url 不能同时传递。');
                $validator->errors()->add('url', 'file 与 url 不能同时传递。');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => '营业执照图片',
            'url' => '营业执照图片 URL',
        ];
    }
}
