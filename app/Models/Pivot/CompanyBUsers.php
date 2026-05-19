<?php

namespace App\Models\Pivot;

use App\Enums\CompanyBUserStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table(name: 'company_b_users', incrementing: true)]
#[Fillable(['company_id', 'b_user_id', 'status', 'last_login_ip', 'last_login_at'])]
class CompanyBUsers extends Pivot
{
    protected $attributes = [
        'status' => CompanyBUserStatus::Enabled,
    ];

    public function casts()
    {
        return [
            'status' => CompanyBUserStatus::class,
        ];
    }
}
