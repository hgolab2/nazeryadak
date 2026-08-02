<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Sms extends Model
{
    protected $table = 'sms';
    protected $fillable = [
        'type',
        'mobile',
        'user_id',
        'text',
        'udh'
    ];
    protected $hidden = ['created_at'];

    /**
     * کاربر ثبت کننده ملک
     *
     * @return object
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    /*برای سایت شماره 10 دوبی کاربرد دارد*/
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }
}
