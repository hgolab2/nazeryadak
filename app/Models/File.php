<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $table = 'files'; // نام جدول در دیتابیس
    protected $primaryKey = 'fileId';
    public $timestamps = false;
    protected $fillable = [
        'fileId',
        'title',
        'description',
        'filetype',
        'extension',
        'filepath',
        'savedate',
        'savedby',
        'filesize',
        'grouptype',
        'width',
        'height'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function getPath()
    {


        $fileGroup = getFileGroup($this->grouptype);
        switch($this->grouptype)
        {
            case 1: //article
            case 3: //sound
                return '/' . $fileGroup . '/upload/' . substr($this->savedate, 0, 4) . '/' . substr($this->savedate, 5, 2) . '/' . $this->fileId . '_' . $this->filepath;
                break;


        }
        if(($this->grouptype == 3 || $this->grouptype == 8) && $this->fileId > 157500)
        {
            return '/' . $fileGroup . '/upload/' . substr($this->savedate, 0, 4) . '/' . substr($this->savedate, 5, 2) . '/' . $this->fileId . '_' . $this->filepath;
        }
        elseif($this->grouptype == 4 || $this->grouptype == 7 || $this->grouptype == 9 || $this->grouptype == 10 || $this->grouptype == 2)
        {
            return '/' . $fileGroup . '/upload/' . substr($this->savedate, 0, 4) . '/' . substr($this->savedate, 5, 2) . '/' . $this->fileId . '_' . $this->filepath;
        }
        elseif($this->grouptype == 1 && $this->fileId > 157000)
        {
            return '/' . $fileGroup . '/upload/' . substr($this->savedate, 0, 4) . '/' . substr($this->savedate, 5, 2) . '/' . $this->fileId . '_' . $this->filepath;
        }
        else
        {
            return '/' . $fileGroup . '/upload/' . substr($this->savedate, 0, 4) . '/' . substr($this->savedate, 5, 2) . '/' . $this->fileId . '_' . $this->filepath;
        }



    }

}
