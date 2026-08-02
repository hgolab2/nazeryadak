<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'gateway',
        'amount',
        'status',
        'ref_id',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    /* Relations */

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
