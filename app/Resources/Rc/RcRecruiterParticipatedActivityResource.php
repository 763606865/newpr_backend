<?php

namespace App\Resources\Rc;

use App\Enums\RcSchoolActivityOrganizerType;
use App\Models\Rc\SchoolActivityCompany;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcRecruiterParticipatedActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof SchoolActivityCompany) {
            return (array) $this->resource;
        }

        $application = $this->resource;
        $activity = $application->activity;

        return [
            'activity' => $activity
                ? (new RcSchoolActivityResource($activity))->resolve($request)
                : null,
            'application' => (new RcSchoolActivityCompanyResource($application))->resolve($request),
            'is_organizer' => $activity !== null
                && $activity->organizer_type === RcSchoolActivityOrganizerType::Company
                && (int) $activity->organizer_id === (int) $application->company_id,
        ];
    }
}
