<?php

namespace App\Discovery\Recommendation;

use App\Models\User;

final class AnnouncementRecommendationContext
{
    public function __construct(
        public readonly ?User $user = null,
        public readonly ?string $cityHint = null,
    ) {}

    public function resolvedCityCode(): ?string
    {
        if (filled($this->cityHint)) {
            return (string) $this->cityHint;
        }

        if (! $this->user instanceof User) {
            return null;
        }

        $resume = $this->user->resumes()
            ->orderByDesc('is_primary')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        return filled($resume?->current_city_code) ? (string) $resume->current_city_code : null;
    }
}
