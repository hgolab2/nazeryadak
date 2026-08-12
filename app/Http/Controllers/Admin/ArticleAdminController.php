<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article1;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ArticleAdminController extends Controller
{
    private int $blogCategoryId = 17;

    public function admin_list(Request $request)
    {
        if (!Auth::user()) return redirect('/loginAdmin');

        $query = Article1::orderBy($request->order ?? 'articleid', $request->orderby ?? 'desc')
            ->where('deleted', '0')
            ->select('article1.*')
            ->join('articleincategory', 'articleincategory.articleid', '=', 'article1.articleid')
            ->where('articleincategory.siteid', 1)
            ->where('articleincategory.type', 'cat')
            ->where('articleincategory.categoryid', $this->blogCategoryId)
            ->distinct();

        if ($request->filled('title')) {
            $query->where('article1.titr', 'like', '%' . $request->title . '%');
        }

        if ($request->filled('status')) {
            $query->where('article1.hidden', $request->status == 'hidden' ? 1 : 0);
        }

        $totalCount = (clone $query)->count();
        $model = $query->paginate($request->showcount ?? 20);

        if ($request->ajax()) {
            $view = view('article.admin.list_type', compact('model', 'totalCount'))->render();
            return response()->json(['html' => $view, 'totalCount' => $totalCount]);
        }

        return view('article.admin.list', compact('model'));
    }

    public function admin_create()
    {
        if (!Auth::user()) return redirect('/loginAdmin');

        $categories = Category::where('deleted', 0)->orderBy('title')->get();
        return view('article.admin.create', compact('categories'));
    }

    public function admin_store(Request $request)
    {
        if (!Auth::user()) return redirect('/loginAdmin');

        $validator = $this->validator($request);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $article = Article1::create($this->articleData($request, true));
        $this->syncCategory($article->articleid, $request->categoryid ?: $this->blogCategoryId);
        $this->storeImage($request, $article);

        return redirect('/admin/article/list')->with('success', 'Ù…Ø·Ù„Ø¨ Ø¨Ø§ Ù…ÙˆÙÙ‚ÛŒØª Ø«Ø¨Øª Ø´Ø¯');
    }

    public function admin_edit($id)
    {
        if (!Auth::user()) return redirect('/loginAdmin');

        $model = Article1::where('deleted', '0')->findOrFail($id);
        $categories = Category::where('deleted', 0)->orderBy('title')->get();
        $selectedCategory = DB::table('articleincategory')
            ->where('articleid', $id)
            ->where('siteid', 1)
            ->where('type', 'cat')
            ->value('categoryid');

        return view('article.admin.create', compact('model', 'categories', 'selectedCategory'));
    }

    public function admin_update(Request $request, $id)
    {
        if (!Auth::user()) return redirect('/loginAdmin');

        $article = Article1::where('deleted', '0')->findOrFail($id);
        $validator = $this->validator($request);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $article->update($this->articleData($request, false));
        $this->syncCategory($article->articleid, $request->categoryid ?: $this->blogCategoryId);
        $this->storeImage($request, $article);

        return redirect('/admin/article/list')->with('success', 'Ù…Ø·Ù„Ø¨ Ø¨Ø§ Ù…ÙˆÙÙ‚ÛŒØª ÙˆÛŒØ±Ø§ÛŒØ´ Ø´Ø¯');
    }

    public function toggleStatus($id)
    {
        if (!Auth::user()) return redirect('/loginAdmin');

        $article = Article1::where('deleted', '0')->findOrFail($id);
        $article->update([
            'hidden' => $article->hidden ? 0 : 1,
            'updateid' => Auth::id(),
            'updatetime' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        if (!Auth::user()) return redirect('/loginAdmin');

        Article1::where('articleid', $id)->update([
            'deleted' => 1,
            'deletedate' => now(),
            'updateid' => Auth::id(),
            'updatetime' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    private function validator(Request $request)
    {
        return Validator::make($request->all(), [
            'titr' => 'required|string|max:255',
            'sutitr' => 'nullable|string|max:500',
            'rutitr' => 'nullable|string|max:255',
            'text' => 'nullable|string',
            'keywords' => 'nullable|string|max:500',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'focus_keyword' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|url|max:500',
            'robots_index' => 'nullable|boolean',
            'robots_follow' => 'nullable|boolean',
            'source' => 'nullable|string|max:255',
            'urlsource' => 'nullable|string|max:500',
            'showdate' => 'nullable|date',
            'hidden' => 'required|boolean',
            'categoryid' => 'nullable|integer',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:4096',
        ]);
    }

    private function articleData(Request $request, bool $creating): array
    {
        $now = now();
        $data = $request->only([
            'titr',
            'sutitr',
            'rutitr',
            'text',
            'keywords',
            'seo_title',
            'seo_description',
            'focus_keyword',
            'canonical_url',
            'source',
            'urlsource',
            'hidden',
        ]);
        $data['robots_index'] = $request->boolean('robots_index', true);
        $data['robots_follow'] = $request->boolean('robots_follow', true);
        $data['showdate'] = $request->showdate ?: $now;
        $data['withoutCategiry'] = 0;
        $data['countword'] = str_word_count(strip_tags($request->text ?? ''));
        $data['updateid'] = Auth::id();
        $data['updatetime'] = $now;

        if ($creating) {
            $data['createdate'] = $now;
            $data['createid'] = Auth::id();
            $data['deleted'] = 0;
            $data['elasticId'] = '';
            $data['slider'] = 0;
            $data['isElastic'] = 0;
        }

        return $data;
    }

    private function syncCategory(int $articleId, int $categoryId): void
    {
        DB::table('articleincategory')
            ->where('articleid', $articleId)
            ->where('siteid', 1)
            ->where('type', 'cat')
            ->delete();

        DB::table('articleincategory')->insert([
            'articleid' => $articleId,
            'categoryid' => $categoryId,
            'siteid' => 1,
            'type' => 'cat',
        ]);
    }

    private function storeImage(Request $request, Article1 $article): void
    {
        if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
            return;
        }

        $baseDir = public_path('imgArticle/upload');
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        $fileId = uploader($request->file('file'), [
            'grouptype' => 1,
            'description' => $article->titr,
            'width' => 0,
            'height' => 0,
        ]);

        if ($fileId) {
            $article->update(['image' => $fileId, 'imageId' => $fileId]);
        }
    }
}

