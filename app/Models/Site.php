<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $table = 'site'; // نام جدول در دیتابیس
    protected $primaryKey = 'site_id';
    public $timestamps = false;
    protected $fillable = [
        'site_title',
        'site_path',
        'lang',
        'theme',
    ];

}
