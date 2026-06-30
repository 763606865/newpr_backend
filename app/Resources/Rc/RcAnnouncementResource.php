<?php

namespace App\Resources\Rc;

use App\Models\Area;
use App\Models\Rc\Announcement;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcAnnouncementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Announcement) {
            return (array) $this->resource;
        }

        $announcement = $this->resource;

        $data = [
            'id' => $announcement->id,
            'title' => $announcement->title,
            'sub_title' => $announcement->sub_title,
            'publisher_name' => $announcement->publisher_name,
            'publisher_type' => $announcement->publisher_type?->value,
            'publisher_type_label' => $announcement->publisher_type?->getLabel(),
            'cover' => $announcement->cover,
            'display_cover' => $announcement->cover,
            'summary' => $announcement->summary,
            'link_url' => $announcement->link_url,
            'employment_types' => $announcement->employment_types ?? [],
            'employment_type_labels' => $announcement->employmentTypeLabels(),
            'graduation_years' => $announcement->graduation_years ?? [],
            'graduation_year_labels' => $announcement->graduationYearLabels(),
            'education_level' => $announcement->education_level?->value,
            'education_level_label' => $announcement->education_level?->getLabel(),
            'major_requirement' => $announcement->major_requirement,
            'is_nationwide' => $announcement->is_nationwide,
            'apply_start_at' => $this->formatDateTime($announcement->apply_start_at),
            'apply_end_at' => $this->formatDateTime($announcement->apply_end_at),
            'apply_deadline_type' => $announcement->apply_deadline_type?->value,
            'apply_deadline_type_label' => $announcement->apply_deadline_type?->getLabel(),
            'apply_status' => $announcement->applyStatusLabel(),
            'published_at' => $this->formatDateTime($announcement->published_at),
            'expired_at' => $this->formatDateTime($announcement->expired_at),
            'is_top' => $announcement->is_top,
            'source_name' => $announcement->source_name,
            'source_url' => $announcement->source_url,
            'read_count' => $announcement->read_count,
            'created_at' => $this->formatDateTime($announcement->created_at),
            'updated_at' => $this->formatDateTime($announcement->updated_at),
        ];

        if ($announcement->is_nationwide) {
            $data['location_label'] = '全国';
            $data['city_codes'] = [];
            $data['city_names'] = [];
        } elseif ($announcement->relationLoaded('cities')) {
            $cityCodes = $announcement->cities->pluck('city_code')->filter()->values()->all();
            $cityNames = $announcement->cities
                ->pluck('cityArea.name')
                ->filter()
                ->values()
                ->all();

            if ($cityNames === [] && $cityCodes !== []) {
                $cityNames = Area::query()
                    ->whereIn('code', $cityCodes)
                    ->pluck('name')
                    ->values()
                    ->all();
            }

            $data['city_codes'] = $cityCodes;
            $data['city_names'] = $cityNames;
            $data['location_label'] = $cityNames === [] ? null : implode('、', $cityNames);
        }

        if ($announcement->relationLoaded('majors')) {
            $data['major_codes'] = $announcement->majors->pluck('major_code')->filter()->values()->all();
            $data['major_names'] = $announcement->majors->pluck('major.name')->filter()->values()->all();
        }

        if ($announcement->relationLoaded('tags')) {
            $data['tags'] = $announcement->tags->map(static fn ($tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'category' => $tag->category,
            ])->values()->all();
        }

        return $data;
    }

    private function formatDateTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }
}
