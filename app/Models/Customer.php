<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    protected $table = 'customers';
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'password',
        'status',
        'otp_code',
        'otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'otp_code',
    ];

    protected $casts = [
        'status' => 'boolean',
        'last_login_at' => 'datetime',
        'otp_expires_at' => 'datetime',
    ];

    public function address()
    {
        return $this->hasOne(CustomerAddress::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function fullName()
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function photo()
    {
        $img = '/upload/images/avatar_man.png';
        return $img;
    }

    public function favoriteProducts()
    {
        return $this->belongsToMany(Product::class, 'product_favorites', 'user_id', 'product_id')->withPivot('pin')->withTimestamps();
    }
}
