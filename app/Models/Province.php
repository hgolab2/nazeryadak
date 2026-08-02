<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $fillable = [
        'name',
        'name_en',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
