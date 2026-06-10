<?php

namespace App\Libs\Ocr\Data;

readonly class RecognizeResult
{
    /**
     * @param  array<int, WordInfo>  $words
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $content,
        public array $words,
        public ?int $width,
        public ?int $height,
        public ?int $originalWidth,
        public ?int $originalHeight,
        public string $requestId,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromAliyunData(string $requestId, array $data): self
    {
        $words = [];

        foreach ($data['prism_wordsInfo'] ?? [] as $word) {
            if (! is_array($word)) {
                continue;
            }

            $words[] = WordInfo::fromArray($word);
        }

        return new self(
            content: (string) ($data['content'] ?? ''),
            words: $words,
            width: self::nullableInt($data['width'] ?? null),
            height: self::nullableInt($data['height'] ?? null),
            originalWidth: self::nullableInt($data['orgWidth'] ?? null),
            originalHeight: self::nullableInt($data['orgHeight'] ?? null),
            requestId: $requestId,
            raw: $data,
        );
    }

    private static function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
