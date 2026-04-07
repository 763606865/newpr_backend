<?php

namespace App\Models;

use App\Models\Concerns\DateTimeFormat;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Table('staffs')]
#[Fillable(['name', 'email', 'mobile', 'password', 'gender', 'status'])]
#[Hidden(['password'])]
class Staff extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, DateTimeFormat, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
