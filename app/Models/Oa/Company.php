<?php

namespace App\Models\Oa;

use App\Enums\CompanyStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Model;

#[Table('oa_companies')]
#[Fillable(['name', 'credit_code', 'legal_person', 'contact_phone', 'address', 'status'])]
class Company extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'status' => CompanyStatus::Enabled
    ];

    /**
     * 部门
     *
     * @return HasMany
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class, 'company_id');
    }

    /**
     * 职位
     *
     * @return HasMany
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class, 'company_id');
    }
}
