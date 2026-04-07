<?php

namespace App\Models;

use App\Models\Concerns\DateTimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as BaseModel;
class Model extends BaseModel
{
    use HasFactory, DateTimeFormat;
}
