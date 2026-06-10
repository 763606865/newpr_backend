<?php

namespace App\Libs\Ocr\Data;

readonly class BusinessLicenseResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $creditCode,
        public string $companyName,
        public string $companyType,
        public string $businessAddress,
        public string $legalPerson,
        public string $businessScope,
        public string $registeredCapital,
        public string $registrationDate,
        public string $validPeriod,
        public string $validFromDate,
        public string $validToDate,
        public string $companyForm,
        public string $requestId,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromAliyunData(string $requestId, array $payload): self
    {
        /** @var array<string, mixed> $data */
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

        return new self(
            creditCode: self::stringValue($data['creditCode'] ?? null),
            companyName: self::stringValue($data['companyName'] ?? null),
            companyType: self::stringValue($data['companyType'] ?? null),
            businessAddress: self::stringValue($data['businessAddress'] ?? null),
            legalPerson: self::stringValue($data['legalPerson'] ?? null),
            businessScope: self::stringValue($data['businessScope'] ?? null),
            registeredCapital: self::stringValue($data['registeredCapital'] ?? null),
            registrationDate: self::stringValue($data['RegistrationDate'] ?? $data['registrationDate'] ?? null),
            validPeriod: self::stringValue($data['validPeriod'] ?? null),
            validFromDate: self::stringValue($data['validFromDate'] ?? null),
            validToDate: self::stringValue($data['validToDate'] ?? null),
            companyForm: self::stringValue($data['companyForm'] ?? null),
            requestId: $requestId,
            raw: $data,
        );
    }

    private static function stringValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }
}
