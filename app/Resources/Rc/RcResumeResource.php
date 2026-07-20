<?php

namespace App\Resources\Rc;

use App\Models\Rc\Resume;
use App\Resources\Concerns\SerializesOssAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcResumeResource extends JsonResource
{
    use SerializesOssAttributes;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Resume) {
            return (array) $this->resource;
        }

        $avatar = $this->ossAttributePair('avatar');
        $file = $this->ossAttributePair('file_url');

        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'resume_no' => $this->resource->resume_no,
            'title' => $this->resource->title,
            'full_name' => $this->resource->full_name,
            'avatar' => $avatar['path'],
            'display_avatar' => $avatar['display'],
            'personal_advantage' => $this->resource->personal_advantage,
            'gender' => $this->resource->gender,
            'id_card' => $this->resource->id_card,
            'nation' => $this->resource->nation,
            'birth_date' => $this->resource->birth_date,
            'birth_month' => $this->resource->birth_month,
            'age' => $this->resource->age,
            'marital_status' => $this->resource->marital_status?->value ?? $this->resource->marital_status,
            'marital_status_label' => $this->resource->marital_status?->getLabel(),
            'political_status' => $this->resource->political_status?->value ?? $this->resource->political_status,
            'political_status_label' => $this->resource->political_status?->getLabel(),
            'native_place' => $this->resource->native_place,
            'current_identity' => $this->resource->current_identity,
            'work_start_date' => $this->resource->work_start_date,
            'work_years' => $this->resource->work_years,
            'current_salary' => $this->resource->current_salary,
            'salary_remark' => $this->resource->salary_remark,
            'recruit_source' => $this->resource->recruit_source,
            'highest_education_level' => $this->resource->highest_education_level,
            'is_fresh_graduate' => $this->resource->is_fresh_graduate,
            'expected_salary_min' => $this->resource->expected_salary_min,
            'expected_salary_max' => $this->resource->expected_salary_max,
            'expected_salary_unit' => $this->resource->expected_salary_unit,
            'household_register' => $this->resource->household_register,
            'household_register_detail' => $this->resource->household_register_detail,
            'current_residence_city' => $this->resource->current_residence_city,
            'current_city_code' => $this->resource->current_city_code,
            'current_residence_detail' => $this->resource->current_residence_detail,
            'residence_country' => $this->resource->residence_country,
            'phone' => $this->resource->phone,
            'email' => $this->resource->email,
            'source_type' => $this->resource->source_type?->value ?? $this->resource->source_type,
            'file_url' => $file['path'],
            'display_file_url' => $file['display'],
            'file_name' => $this->resource->file_name,
            'file_ext' => $this->resource->file_ext,
            'text_content' => $this->resource->text_content,
            'parsed_data' => $this->resource->parsed_data,
            'is_primary' => $this->resource->is_primary,
            'status' => $this->resource->status?->value ?? $this->resource->status,
            'extra' => $this->resource->extra,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
