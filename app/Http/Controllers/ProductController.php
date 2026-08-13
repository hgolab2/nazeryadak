<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\ProductFavorite;
use App\Enums\ProductCategory;
use App\Models\Category;
use App\Models\EshopCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\IsacoImageService;

class ProductController extends Controller
{
    /**
     * صفحه‌ی دسته‌بندی با آدرس تمیز: /shop/{slug}
     *
     * همان index است، فقط دسته از مسیر خوانده می‌شود نه از query string.
     * اسلاگ ناشناخته ۴۰۴ می‌گیرد تا آدرس‌های ساختگی، صفحه‌ی خالی و
     * بی‌محتوا تولید نکنند.
     */
    public function category(Request $request, string $category)
    {
        $slug = rawurldecode($category);

        if (! ProductCategory::fromSlug($slug)) {
            return response()->view('errors.404', [], 404);
        }

        // دسته از مسیر می‌آید و عمدا داخل query قرار نمی‌گیرد، وگرنه در
        // canonical دوباره به‌صورت ?category= ظاهر می‌شود.
        return $this->index($request, $slug);
    }

    public function index(Request $request, ?string $categorySlug = null)
    {
        /*
        | آدرس قدیمی /shop?category=X دیگر کانونیکال نیست. اگر کاربر یا
        | خزنده مستقیم آن را صدا بزند، با 301 به /shop/X می‌رود تا فقط
        | یک نسخه از این صفحه ایندکس شود. سایر فیلترها (جستجو، مدل
        | خودرو، صفحه) حفظ می‌شوند.
        */
        if ($categorySlug === null && $request->filled('category')) {
            $enum = ProductCategory::fromSlug($request->category);
            if ($enum) {
                $query = $request->except('category');

                return redirect(
                    '/shop/' . rawurlencode($enum->slug()) . ($query ? '?' . http_build_query($query) : ''),
                    301
                );
            }
        }

        $selectedCategoryIds = [];
        if ($categorySlug !== null) {
            $enum = ProductCategory::fromSlug($categorySlug);
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
        // categories همراه محصول لود می‌شود تا تشخیص «قطعه‌ی استعلامی» در کارت‌ها
        // به ازای هر محصول یک کوئری جدا نزند.
        $query = Product::with('categories')->where('is_active', 1);
        if ($request->filled('title')) {
            // Search part name, SKU, and car model together.
            $query->searchText($request->title);
        }
        if ($request->filled('car_model')) {
            if (ctype_digit((string) $request->car_model)) {
                $query->whereHas('categories', function ($q) use ($request) {
                    $q->where('category_id', (int) $request->car_model);
                });
            } else {
                $query->searchCarModel($request->car_model);
            }
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
        // فقط ستون‌ها و جهت‌های مجاز. قبلا ورودی مستقیم به orderBy می‌رفت و
        // هر مقدار نامعتبر (مثلا از یک لینک خراب یا خزنده) صفحه‌ی ۵۰۰ می‌داد.
        $sortable = ['id', 'price', 'title', 'created_at'];
        $orderColumn = $request->get('order', 'id');
        $orderColumn = in_array($orderColumn, $sortable, true) ? $orderColumn : 'id';

        $orderDirection = strtolower((string) $request->get('orderby', 'desc'));
        $orderDirection = in_array($orderDirection, ['asc', 'desc'], true) ? $orderDirection : 'desc';

        // حدود ۳۶٪ محصولات هنوز عکس ندارند و کارت «بدون تصویر» می‌گیرند؛ در
        // مرتب‌سازی پیش‌فرض، عکس‌دارها جلو می‌افتند. اگر کاربر خودش ستون
        // مرتب‌سازی را انتخاب کرده باشد، دخالتی نمی‌کنیم.
        if ($orderColumn === 'id') {
            $query->orderByRaw("CASE WHEN file_path IS NULL OR file_path = '' OR file_path = '/images/no-image.svg' THEN 1 ELSE 0 END");
        }

        $query->orderBy($orderColumn, $orderDirection);
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
        $carCategories = EshopCategory::orderBy('name')->get();
        return view('product.list', compact('model', 'totalCount', 'categories', 'categoryCounts', 'selectedCategoryIds', 'title', 'carModel', 'carCategories', 'categorySlug'));
    }

    public function getProduct($count)
    {
        return Product::with('categories')->orderBy('id' , 'desc')->where('is_active' , '1')->where('file_path' ,'!=', '')->paginate($count);
    }

    function show($id, $slug = null)
    {
        if(!$id){
            return response()->view('errors.404', [], 404);
        }
        $model = Product::with('images')->where('is_active' , 1)->where('id' , $id)->first();
        if(!$model)
        {
            return response()->view('errors.404', [], 404);
        }
        /*
        | تنها یک آدرس معتبر برای هر محصول: /product/{id}/{slug}
        | آدرس بدون اسلاگ یا با اسلاگ قدیمی با 301 به نسخه‌ی کانونیکال منتقل
        | می‌شود تا اعتبار لینک‌ها بین چند نسخه‌ی یک صفحه پخش نشود.
        */
        $currentPath = rawurldecode(request()->path());
        if ($currentPath !== ltrim($model->url(), '/')) {
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
            ->with(['categories', 'favorites' => function ($q) use ($user) {
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

