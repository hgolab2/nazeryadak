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
        'phone_verified_at' => 'datetime',
    ];

    /**
     * آیا شماره‌ی این حساب با کد پیامکی تأیید شده؟
     *
     * داشتن حساب دلیل تأیید نیست: حسابی که ادمین از پنل می‌سازد هم
     * شماره دارد، ولی هیچ‌کس بررسی نکرده که آن شماره دست همین آدم است.
     */
    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    /** ثبت تأیید شماره؛ تأییدِ قبلی دوباره تاریخ‌خوردن ندارد. */
    public function markPhoneVerified(): void
    {
        if ($this->hasVerifiedPhone()) {
            return;
        }

        $this->forceFill(['phone_verified_at' => now()])->save();
    }

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
