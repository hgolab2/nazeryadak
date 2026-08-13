<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerNotification extends Model
{
    protected $table = 'customer_notifications';

    protected $fillable = [
        'customer_id',
        'type',
        'title',
        'body',
        'url',
        'icon',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * تعداد خوانده‌نشده‌های مشتری جاری؛ در هدر هر صفحه صدا زده می‌شود، پس
     * نتیجه در همان درخواست کش می‌شود.
     */
    public static function unreadCountFor(?int $customerId): int
    {
        if (! $customerId) {
            return 0;
        }

        static $cache = [];

        if (! array_key_exists($customerId, $cache)) {
            $cache[$customerId] = static::where('customer_id', $customerId)->unread()->count();
        }

        return $cache[$customerId];
    }
}
