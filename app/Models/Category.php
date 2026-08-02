<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'category'; // نام جدول در دیتابیس
    protected $primaryKey = 'categoryid';
    public $timestamps = false;
    protected $fillable = [
        'categoryid',
        'title',
        'text',
        'lft',
        'rgt',
        'createdate',
        'userid',
        'xmlField',
        'deleted',
        'parent_id',
        'lvl',
        'scope',
        'siteId',
        'order',
        'fileid'
    ];
    public function getUrl()
    {
        return '/'.langname($this->siteId).'/article/lists/'.$this->categoryid;
    }
    public function files()
    {
        return $this->belongsTo(File::class, 'fileid');
    }
}
