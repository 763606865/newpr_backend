<?php

namespace App\Libs\Amap\Data;

readonly class GeocodeResult
{
    /**
     * @param  array{lng: float|null, lat: float|null}  $location
     */
    public function __construct(
        public string $formattedAddress,
        public string $country,
        public string $province,
        public string $city,
        public string $district,
        public string $adcode,
        public array $location,
        public string $level,
    ) {}

    public static function fromArray(array $data): self
    {
        $location = self::parseLocation($data['location'] ?? null);

        return new self(
            formattedAddress: (string) ($data['formatted_address'] ?? ''),
            country: (string) ($data['country'] ?? ''),
            province: (string) ($data['province'] ?? ''),
            city: self::normalizeStringValue($data['city'] ?? ''),
            district: (string) ($data['district'] ?? ''),
            adcode: (string) ($data['adcode'] ?? ''),
            location: $location,
            level: (string) ($data['level'] ?? ''),
        );
    }

    /**
     * @return array{lng: float|null, lat: float|null}
     */
    private static function parseLocation(mixed $location): array
    {
        if (! is_string($location) || ! str_contains($location, ',')) {
            return ['lng' => null, 'lat' => null];
        }

        [$longitude, $latitude] = array_pad(explode(',', $location, 2), 2, null);

        return [
            'lng' => is_numeric($longitude) ? (float) $longitude : null,
            'lat' => is_numeric($latitude) ? (float) $latitude : null,
        ];
    }

    private static function normalizeStringValue(mixed $value): string
    {
        if (is_array($value)) {
            return '';
        }

        return (string) $value;
    }
}
