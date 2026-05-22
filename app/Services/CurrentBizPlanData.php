<?php

namespace App\Services;

class CurrentBizPlanData
{
    /**
     * @param  array<int, array<string, mixed>>  $menus
     * @param  array<int, array<string, mixed>>  $features
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly ?string $code,
        public readonly ?string $startTime,
        public readonly ?string $endTime,
        public readonly array $menus,
        public readonly array $features,
    ) {}

    public static function empty(): self
    {
        return new self(
            id: null,
            name: null,
            code: null,
            startTime: null,
            endTime: null,
            menus: [],
            features: [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'menus' => $this->menus,
            'features' => $this->features,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function planPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function modulePlanPayload(): array
    {
        return [
            'id' => $this->id,
            'plan_name' => $this->name,
            'plan_code' => $this->code,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
        ];
    }
}
