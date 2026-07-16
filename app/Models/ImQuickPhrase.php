<?php

namespace App\Models;

use App\Models\Rc\UserIm;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * IM 常用快捷短语表
 *
 * @property int $id
 * @property int $user_im_id
 * @property string|null $title
 * @property string $content
 * @property int $sort
 * @property bool $is_enabled
 * @property int $used_count
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read UserIm $userIm
 */
#[Table('im_quick_phrases')]
#[Fillable([
    'user_im_id',
    'title',
    'content',
    'sort',
    'is_enabled',
    'used_count',
    'last_used_at',
])]
class ImQuickPhrase extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'user_im_id' => 'integer',
            'sort' => 'integer',
            'is_enabled' => 'boolean',
            'used_count' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }

    public function userIm(): BelongsTo
    {
        return $this->belongsTo(UserIm::class, 'user_im_id');
    }
}
