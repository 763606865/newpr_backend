<?php

namespace App\Models\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait DateTimeFormat
{
    protected function createdAt(): Attribute
    {
        return Attribute::get(
            get: static fn (?string $value) => $value ? Carbon::parse($value)->toDateTimeString() : null,
        );
    }

    protected function updatedAt(): Attribute
    {
        return Attribute::get(
            get: static fn (?string $value) => $value ? Carbon::parse($value)->toDateTimeString() : null,
        );
    }

    protected function deletedAt(): Attribute
    {
        return Attribute::get(
            get: static fn (?string $value) => $value ? Carbon::parse($value)->toDateTimeString() : null,
        );
    }
}
