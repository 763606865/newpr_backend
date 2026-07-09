<?php

namespace App\Resources\Rc;

use App\Enums\RcSchoolActivityOrganizerType;
use App\Models\Rc\SchoolActivitySchool;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcSchoolParticipatedActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof SchoolActivitySchool) {
            return (array) $this->resource;
        }

        $schoolApplication = $this->resource;
        $activity = $schoolApplication->activity;

        return [
            'activity' => $activity
                ? (new RcSchoolActivityResource($activity))->resolve($request)
                : null,
            'school_application' => (new RcSchoolActivitySchoolResource($schoolApplication))->resolve($request),
            'is_organizer' => $activity !== null
                && $activity->organizer_type === RcSchoolActivityOrganizerType::School
                && (int) $activity->organizer_id === (int) $schoolApplication->school_id,
        ];
    }
}
