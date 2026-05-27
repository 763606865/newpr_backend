<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CmsArticleContentType: int implements HasLabel
{
    case Html = 1;
    case Markdown = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Html => 'HTML',
            self::Markdown => 'Markdown',
        };
    }
}
