<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Articleinf;
use App\Models\ArticleFile;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Google;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
class BlogController extends Controller
{
    public function lists(Request $request)
    {
        $categoryid = 17;
        $category = Category::find($categoryid);
        if(!$category)
        {
            return view('errors.404');
        }
        $class = 'App\Models\Article1';
        $article = new $class;
        $model = $article::orderBy('showdate', 'desc')->where('hidden' , '0')->where('deleted' , '0');
        if($categoryid > 0)
        {
            $model = $model->select(['article1.*'])
            ->join('articleincategory', 'articleincategory.articleid', '=', 'article1.articleid')
            ->where('articleincategory.siteid',  1)
            ->where('articleincategory.categoryid' , 17);
        }
        else
        {
            return view('errors.404');
        }
        $totalCount = $model->count();
        $model = $model->paginate(20);
        if ($request->ajax() || $request->ajaxi)
        {
            $couter=$totalCount/20;
            $counter1= round($couter);
            if($counter1 >= $couter)
                $couter = $counter1;
            else
                $couter = $counter1+1;
            $hasPage = ($couter == $request->page)? false : true;
            $view = view('article.list_type', compact('model','category'))->render();
            return response()->json(['html' => $view, 'hasPage' => $hasPage, 'totalCount' => $totalCount]);
        }
        else
        {
            return View('article.list' , compact('model' , 'categoryid' , 'totalCount' , 'category' ));
        }
    }

    public function view(Request $request , $articleid)
    {
        if (!ctype_digit($articleid)) {
            return response()->view('errors.404', [], 404);
        }
        $result = $this->viewShow($request , $articleid);
        if(is_array($result))
        {
            return View('article.show' , $result);
        }
        else
        {
            return $result;
        }
    }

    function cleanInvalidLinks($content) {
        // الگوی کلی برای تگ a
        return preg_replace_callback(
            '#<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#is',
            function ($matches) {
                $href = $matches[1];
                $text = $matches[2];
                // فقط اگر لینک معتبر است، نگه‌داریم
                // لینک معتبر = فقط https:// و http:// با دامین معتبر
                if (preg_match('#^https?://[a-z0-9\-\.]+\.[a-z]{2,}#i', $href)) {
                    return $matches[0]; // تگ a را دست‌نخورده برگردان
                } else {
                    return $text; // تگ a حذف شود ولی متن داخلش باقی بماند
                }
            },
            $content
        );
    }

    public function viewShow(Request $request , $articleid)
    {
        $visit_cnt = 1;
        if (!$articleid) {
            return view('errors.404');
        }
        $class = 'App\Models\Article1';
        $article = new $class;
        $info = $article::where('articleid' , $articleid)->first();
        if(!$info || $info == null || $info->hidden == 1 ||  $info->deleted == 1)
        {
            return view('errors.404');
        }
        $ImgStr = '';
        if($info->images){
            $ImgStr = $info->images->getPath();
        }
        $info_arr = array(
            "articleid" => $info->articleid,
            "modifyDate" => str_replace(' ', 'T', $info->createdate) . '+03:30',
            "publishDate" => str_replace(' ', 'T', $info->showdate) . '+03:30',
            "title" => str_replace(array('‎', '&not;', '&rlm;', chr(194) . chr(173), '&shy;'), array('‌', '&zwnj;', '‌', '‌', ' ', ' '), $info->titr),
            "content" => str_replace(array('‎', '&not;', '&rlm;', chr(194) . chr(173), '&shy;'), array('‌', '&zwnj;', '‌', '‌', ' ', ' '), $info->text),
            "sutitr" => $info->sutitr,
            "rutitr" => $info->rutitr,
            'image_url' => $ImgStr,
            "createdate" => ($info->showdate != '0000-00-00 00:00:00' ? ($info->showdate) : '- - -'),
            "create_name" => $info->createid,
            "source" => $info->source,
            "sourceurl" => $info->urlsource,
            "imageId" => $info->imageId,
            "image" => $info->image
        );
        //$inf = Articleinf::where('articleid', $articleid)->where('siteid', langid($lang))->first();
        //Files of this article
        $article_files_arr = array();
        //Cat Path
        $catid = DB::select("select * from `category`, `articleincategory` where articleincategory.type = 'cat' and  articleincategory.siteid = '1'  and articleincategory.articleid =  '".$articleid."' and articleincategory.categoryid = category.categoryid and category.deleted = 0" );
        $myCat = -1;
        foreach ($catid as $cc) {
            if ($myCat < 0 || $myCat > $cc->categoryid) {
                $myCat = $cc->categoryid;
            }
        }
        $CatInfo = Category::find($myCat);

        $rel_arr3 = array();

        $tags = [];
        $facebook_image = $info_arr['image_url'];
        $category = $myCat;
        $article_files = $article_files_arr;
        $GoogleResult = $rel_arr3;
        $text = $info->text;
        if(isset($text))
        {
            $text = str_replace('­','‌',$text);
            global $link;
            global $count;
            $count = 0;
            $text = preg_replace_callback(
                '|<h([2-3]{1})>([^>]{1,})</h([2-3]{1})>|',
                function ($matches) {
                    global $count;
                    global $link;
                    $count++;
                    if($matches[1] == 2)
                        $link .= '<li><a class="linkn" href="#n'.$count.'">'.$matches[2].'</a></li>';
                    else
                        $link .= '<li><a class="linkn3" href="#n'.$count.'">'.$matches[2].'</a></li>';
                    return '<a name="n'.$count.'"></a>'.$matches[0];
                },
                $text
            );
            $text = str_replace("¬" , "‌" , str_replace('"  rtl;"', '" ', $text));
        }
        $model = $article::orderBy('showdate', 'desc')->where('hidden' , '0')->where('deleted' , '0');
        $model = $model->select(['article1.*'])
        ->join('articleincategory', 'articleincategory.articleid', '=', 'article1.articleid')
        ->where('articleincategory.siteid',  1)
        ->where('articleincategory.categoryid' , 17);
        $model = $model->paginate(4);
        $result =  array(
            'model' => $model,
            'info' => $info ,
            'facebook_image' => $facebook_image ,
            'category' => $category ,
            'article_files' => $article_files ,
            'tags' => $tags,
            'lang' => 1 ,
            'text' => $this->cleanInvalidLinks($text) ,
            'link' => $link ,
            'info_arr' => $info_arr ,
        );
        return $result;
    }
}
