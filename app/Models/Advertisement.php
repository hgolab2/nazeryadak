<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    use HasFactory;

    // نام جدول در پایگاه داده
    protected $table = 'advertisement';

    // کلید اصلی جدول
    protected $primaryKey = 'advertisementid';

    // مشخص کردن نوع کلید اصلی (در اینجا int و auto increment است)
    public $incrementing = true;
    protected $keyType = 'int';

    // غیرفعال کردن timestamps (اگر ستون‌های `created_at` و `updated_at` ندارید)
    public $timestamps = false;

    // مشخص کردن فیلدهای قابل مقداردهی
    protected $fillable = [
        'title',
        'position',
        'mediaid',
        'rows',
        'link',
        'articleid',
        'startdate',
        'enddate',
        'comment',
        'orderview',
        'hidden',
        'priority',
        'createdate',
        'site_id',
        'important',
        'mediaid2',
        'mediaid3',
        'without_title',
    ];
    public function media()
    {
        return $this->belongsTo(File::class, 'mediaid');
    }
    public function media2()
    {
        return $this->belongsTo(File::class, 'mediaid2');
    }
    public function media3()
    {
        return $this->belongsTo(File::class, 'mediaid3');
    }
}
