<?php

namespace App\Models\Pivot;

use Illuminate\Database\Eloquent\Attributes\Table;

#[Table(name: 'company_b_users', incrementing: true)]
class CompanyBUsers extends Pivot {}
