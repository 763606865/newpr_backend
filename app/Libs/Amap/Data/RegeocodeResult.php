<?php

namespace App\Libs\Amap\Data;

readonly class RegeocodeResult
{
    /**
     * @param  array<string, mixed>  $addressComponent
     */
    public function __construct(
        public string $formattedAddress,
        public array $addressComponent,
    ) {}

    public static function fromArray(array $data): self
    {
        $addressComponent = $data['addressComponent'] ?? [];

        return new self(
            formattedAddress: (string) ($data['formatted_address'] ?? ''),
            addressComponent: is_array($addressComponent) ? $addressComponent : [],
        );
    }

    public function province(): string
    {
        return $this->stringFromAddressComponent('province');
    }

    public function city(): string
    {
        return $this->stringFromAddressComponent('city');
    }

    public function district(): string
    {
        return $this->stringFromAddressComponent('district');
    }

    public function adcode(): string
    {
        return $this->stringFromAddressComponent('adcode');
    }

    private function stringFromAddressComponent(string $key): string
    {
        $value = $this->addressComponent[$key] ?? '';

        if (is_array($value)) {
            return '';
        }

        return (string) $value;
    }
}
