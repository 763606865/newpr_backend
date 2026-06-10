<?php

namespace App\Libs\Ocr\Data;

readonly class WordInfo
{
    /**
     * @param  array<int, array{x: int, y: int}>  $positions
     */
    public function __construct(
        public string $word,
        public int $probability,
        public int $x,
        public int $y,
        public int $width,
        public int $height,
        public int $angle,
        public int $direction,
        public array $positions,
    ) {}

    public static function fromArray(array $data): self
    {
        $positions = [];

        foreach ($data['pos'] ?? [] as $position) {
            if (! is_array($position)) {
                continue;
            }

            $positions[] = [
                'x' => (int) ($position['x'] ?? 0),
                'y' => (int) ($position['y'] ?? 0),
            ];
        }

        return new self(
            word: (string) ($data['word'] ?? ''),
            probability: (int) ($data['prob'] ?? 0),
            x: (int) ($data['x'] ?? 0),
            y: (int) ($data['y'] ?? 0),
            width: (int) ($data['width'] ?? 0),
            height: (int) ($data['height'] ?? 0),
            angle: (int) ($data['angle'] ?? 0),
            direction: (int) ($data['direction'] ?? 0),
            positions: $positions,
        );
    }
}
