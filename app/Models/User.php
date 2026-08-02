<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use  Notifiable;
    protected $table = 'users'; // نام جدول در دیتابیس
    protected $primaryKey = 'user_id';
    protected $fillable = [
        'username', 'name', 'family', 'password','group_id','ip', 'last_login','active', 'role_id','site_id', 'deleted_at'
    ];

    protected $hidden = [
        'pass', 'remember_token',
    ];
    public function setRememberToken($value){}
    public function fullname()
    {
        return $this->name . ' ' . $this->family;
    }
    public function photo()
    {
        $img = '/upload/images/avatar_man.png';
        return $img;
    }
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
