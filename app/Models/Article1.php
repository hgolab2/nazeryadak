<?php
namespace App\Models;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Article1 extends Model
{
    protected $table = 'article1'; // نام جدول در دیتابیس
    protected $primaryKey = 'articleid';
    public $timestamps = false;
    protected $fillable = [
        'articleid',
        'countword',
        'createdate',
        'createid',
        'deleted',
        'deletedate',
        'elasticId',
        'hidden',
        'image',
        'imageId',
        'isElastic',
        'keywords',
        'seo_title',
        'seo_description',
        'focus_keyword',
        'canonical_url',
        'robots_index',
        'robots_follow',
        'mainid',
        'rutitr',
        'selective',
        'showdate',
        'showdateDayIsl',
        'showdateDayPer',
        'showdateMonthIsl',
        'showdateMonthPer',
        'showDateServer',
        'slider',
        'source',
        'sutitr',
        'text',
        'titr',
        'updateid',
        'updatetime',
        'urlsource',
        'withoutCategiry'
    ];
    public function categories()
    {
        return DB::select("select * from `category`, `articleincategory` where articleincategory.type = 'cat' and  articleincategory.siteid = '1'  and articleincategory.articleid =  ".$this->articleid." and articleincategory.categoryid = category.categoryid and category.deleted = 0" );
    }
    public function articleInCategories()
    {
		return $this->hasMany(ArticleInCategory::class, 'articleid' , 'articleid');
	}
    public function creator()
    {
        return $this->belongsTo(User::class, 'createid' , 'user_id');
    }
    public function updator()
    {
        return $this->belongsTo(User::class, 'updateid' , 'user_id');
    }
    public function images()
    {
        return $this->belongsTo(File::class, 'image');
    }
    public function getUrl()
    {
        $slug = seo_slug($this->titr ?: ('article-' . $this->articleid), (string) $this->articleid);
        return '/blog/' . $this->articleid . '/' . $slug;
    }
    public function get_full_tags()
    {
        return ArticleInCategory::select(['category.title','category.categoryid'])->join('category', 'articleincategory.categoryid', '=', 'category.categoryid')->where('articleid', '=', $this->articleid)->where('category.title' , '!=' , '')->where('category.parent_id' , '=' , '2')->where('articleincategory.siteid', '=', 1)->where('type', '=', 'tag')->get();
    }
}
