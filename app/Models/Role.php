<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles'; // نام جدول در دیتابیس
    protected $primaryKey = 'role_id';
    public $timestamps = false;
    protected $fillable = [
        'title'
    ];

}
