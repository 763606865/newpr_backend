<?php

namespace App\Models\Rc\Concerns;

use App\Models\Rc\Position;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait SyncsPositionFromCode
{
    protected static function bootSyncsPositionFromCode(): void
    {
        static::saving(function (self $model): void {
            if ($model->isDirty('position_code') && filled($model->position_code)) {
                $positionName = Position::query()
                    ->where('code', $model->position_code)
                    ->value('name');

                if (filled($positionName)) {
                    $model->position = $positionName;
                }
            }
        });
    }

    public function rcPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_code', 'code');
    }
}
