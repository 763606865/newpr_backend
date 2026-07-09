<?php

namespace App\Models\Oa\Biz;

use App\Models\Model;
use App\Models\Oa\Feature;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 方案功能关联模型
 *
 * @property int $id
 * @property int $plan_id 方案ID
 * @property int $feature_id 功能ID
 */
#[Table('oa_biz_plan_client_features')]
class PlanFeature extends Model
{
    /**
     * 关联的方案
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * 关联的功能点
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }
}
