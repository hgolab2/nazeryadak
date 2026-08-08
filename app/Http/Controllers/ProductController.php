<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\ProductFavorite;
use App\Enums\ProductCategory;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\IsacoImageService;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $selectedCategoryIds = [];
        if ($request->filled('category')) {
            $enum = ProductCategory::fromSlug($request->category);
            if ($enum) {
                $selectedCategoryIds[] = $enum->value;
            }
        }
        $title = '';
        if ($request->filled('title')) {
            $title = $request->title;
        }

        $perPage = 12;
        $categories = ProductCategory::cases();
        $categoryCounts = \DB::table('product_in_category')
            ->join('products', 'products.id', '=', 'product_in_category.product_id')
            ->where('products.is_active', 1)
            ->select('product_in_category.category_id', \DB::raw('count(*) as cnt'))
            ->groupBy('product_in_category.category_id')
            ->pluck('cnt', 'product_in_category.category_id');
        $query = Product::where('is_active', 1);
        if ($request->filled('title')) {
            // نام قطعه، کد فنی و خودرو را با هم می‌گردد و ک/ی عربی و فارسی را یکسان می‌گیرد
            $query->searchText($request->title);
        }
        if ($request->filled('car_model')) {
            $query->searchCarModel($request->car_model);
        }
        if ($request->filled('categories')) {
            $categoryIds = explode(',', $request->categories);
            $query->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds);
            });
        }
        if (!$request->ajax() && count($selectedCategoryIds) > 0) {
            $query->whereHas('categories', function ($q) use ($selectedCategoryIds) {
                $q->whereIn('category_id', $selectedCategoryIds);
            });
        }
        $query->orderBy(
            $request->get('order', 'id'),
            $request->get('orderby', 'desc')
        );
        $model = $query->paginate($perPage);
        $totalCount = $model->total();
        if ($request->ajax() || $request->ajaxi) {
            $view = view('product.list_type', compact('model', 'totalCount' ))->render();
            return response()->json([
                'html'       => $view,
                'totalCount' => $totalCount,
                'hasPage'    => $model->hasMorePages(),
            ]);
        }
        $carModel = $request->get('car_model', '');
        return view('product.list', compact('model', 'totalCount', 'categories', 'categoryCounts', 'selectedCategoryIds', 'title', 'carModel'));
    }

    public function getProduct($count)
    {
        return Product::orderBy('id' , 'desc')->where('is_active' , '1')->where('file_path' ,'!=', '')->paginate($count);
    }

    function show($id, $slug = null)
    {
        if(!$id){
            return view('errors.404');
        }
        $model = Product::where('is_active' , 1)->where('id' , $id)->first();
        if(!$model)
        {
            return view('errors.404');
        }
                if ($slug !== null && request()->path() !== ltrim($model->url(), '/')) {
            return redirect($model->url(), 301);
        }
        $products = $this->getProduct(8);
        return view('product.show' , compact('model','products'));
    }

    public function favorite(Request $request)
    {
        $user = Auth::guard('customer')->user();
        if (! $user) {
            return redirect('/login');
        }
        $products = Product::whereHas('favorites', function ($q) use ($user) {
                $q->where('product_favorites.user_id', $user->id);
            })
            ->with(['favorites' => function ($q) use ($user) {
                $q->where('product_favorites.user_id', $user->id);
            }])
            ->latest()
            ->get();
        return view('product.favorite', compact('products'));
    }

    public function fetchImage($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['image' => '/images/no-image.svg']);
        }

        $hasImage = $product->hasImage();
        $hasDesc = !empty($product->description);

        if ($hasImage && $hasDesc) {
            return response()->json(['image' => $product->image()]);
        }

        $service = new IsacoImageService();
        $result = $service->fetchForProduct($product);

        return response()->json([
            'image' => $result['image'] ?: ($hasImage ? $product->image() : '/images/no-image.svg'),
            'description' => $result['description'],
        ]);
    }

    public function addToFavorite($product_id)
    {
        $user = Auth::guard('customer')->user();
        if (!$user) {
            return response(['status' => 'error', 'result' => 'authentication failed!'], config('StatusCode.UNAUTHORIZED'));
        }
        $validator = Validator::make(['id' => $product_id], [
            'id' => 'required|exists:products,id',
        ]);
        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'result' => $validator->errors()
            ], config('StatusCode.INVALID_INPUT'));
        }
        $ef = ProductFavorite::where('product_id', $product_id)->where('user_id', $user->id)->first();
        if (!$ef) {
            $user->favoriteProducts()->attach($product_id);
            $status = 1;
        } else {
            $user->favoriteProducts()->detach($product_id);
            $status = 0;
        }
        return response([
            'status' => 'ok',
            'result' => $status
        ], config('StatusCode.SUCCESS'));
    }
}
