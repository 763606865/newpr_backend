<?php

namespace App\Resources\Rc;

use App\Models\Rc\AiResumeParseTask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiResumeParseTaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof AiResumeParseTask) {
            return (array) $this->resource;
        }
        $parsed_resume = $this->resource->parsed_resume['fields'] ?? [];
        if (!empty($parsed_resume) && $this->resource->provider === 'custom') {
            $parsed_resume = $this->customProviderTransform($parsed_resume);
        }

        return [
            'id' => $this->resource->id,
            'file_url' => $this->resource->file_url,
            'provider' => $this->resource->provider,
            'status' => $this->resource->status->name,
            'status_value' => $this->resource->status->value,
            'status_label' => $this->resource->status->getLabel(),
            'parsed_resume' => $parsed_resume,
            'meta_parsed_resume' => $this->resource->parsed_resume,
            'error_message' => $this->resource->error_message,
            'token_cost' => $this->resource->token_cost,
            'started_at' => $this->resource->started_at,
            'finished_at' => $this->resource->finished_at,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }

    protected function customProviderTransform(array $parsed): array
    {
        $works = $educations = [];
        if (!blank($parsed['DYYX'] ?? '')) {
            $educations[] = [
                'school_name' => $parsed['DYYX'] ?? '',
                'major' => $parsed['DYZY'] ?? '',
                'degree' => $parsed['DYXL'] ?? '',
                'education_type' => $parsed['DYXLXS'] ?? '',
                'end_date' => $parsed['DYSJ'] ?? '',
            ];
        }

        return [
            'full_name' => $parsed['USERNAME'] ?? '',
            'gender' => $parsed['SEX'] ?? '',
            'id_card' => $parsed['IDENTITY'] ?? '',
            'nation' => $parsed['MZ'] ?? '',
            'birth_date' => $parsed['BRITHDAY'] ?? '',
            'birth_month' => $parsed['BRITHDAY'] ?? '',
            'marital_status' => $parsed['HYZK'] ?? '',
            'political_status' => $parsed['ZZMM'] ?? '',
            'native_place' => $parsed['JG'] ?? '',
            'highest_education_level' => $parsed['DYXL'] ?? '',
            'phone' => $parsed['TEL'] ?? ($parsed['mobile'] ?? ''),
            'email' => $parsed['EMAIL'] ?? '',
            'works' => $works,
            'educations' => $educations,
        ];
    }
}
