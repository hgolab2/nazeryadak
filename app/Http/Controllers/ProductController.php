<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\ProductFavorite;
use App\Enums\ProductCategory;
use App\Models\Category;
use App\Models\EshopCategory;
use App\Models\ProductReview;
use App\Support\CarModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
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

    /**
     * صفحه‌ی فرود مدل خودرو: /car/{slug}
     *
     * جای «/shop?car_model=پژو 206» را می‌گیرد. آدرسی که کلمه‌ی کلیدی را در
     * مسیر دارد، یک صفحه‌ی مستقل به حساب می‌آید نه نسخه‌ای فیلترشده از
     * فروشگاه — و «قطعات پژو ۲۰۶» یکی از پرحجم‌ترین کوئری‌های این بازار است.
     */
    public function car(Request $request, string $car)
    {
        $carName = CarModels::fromSlug(rawurldecode($car));

        if ($carName === null) {
            return response()->view('errors.404', [], 404);
        }

        return $this->index($request, null, $carName);
    }

    /**
     * صفحه‌ی ترکیبی دسته × خودرو: /car/{car}/{category}
     *
     * «لنت ترمز پژو ۲۰۶» — دقیق‌ترین شکل کوئری کاربر. اگر ترکیب هیچ محصولی
     * نداشته باشد، صفحه ساخته می‌شود ولی noindex می‌گیرد (در ویو، از روی
     * تعداد نتایج) تا صفحه‌ی خالی وارد ایندکس نشود.
     */
    public function carCategory(Request $request, string $car, string $category)
    {
        $carName = CarModels::fromSlug(rawurldecode($car));
        $categorySlug = rawurldecode($category);

        if ($carName === null || ! ProductCategory::fromSlug($categorySlug)) {
            return response()->view('errors.404', [], 404);
        }

        return $this->index($request, $categorySlug, $carName);
    }

    public function index(Request $request, ?string $categorySlug = null, ?string $carLanding = null)
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

        /*
        | همان قاعده برای مدل خودرو: «/shop?car_model=پژو 206» با 301 به
        | «/car/پژو-206» می‌رود. فقط نام‌های شناخته‌شده ریدایرکت می‌شوند؛
        | car_model عددی، شناسه‌ی EshopCategory است که از فیلتر سایدبار
        | می‌آید و صفحه‌ی فرود ندارد.
        */
        if ($carLanding === null && $request->filled('car_model') && ! $request->ajax() && ! $request->filled('ajaxi')) {
            $known = ctype_digit((string) $request->car_model)
                ? null
                : CarModels::fromSlug($request->car_model);

            if ($known !== null) {
                $query = $request->except('car_model');
                $target = '/car/' . rawurlencode(CarModels::slugFor($known));
                if ($categorySlug !== null) {
                    $target .= '/' . rawurlencode($categorySlug);
                }

                return redirect($target . ($query ? '?' . http_build_query($query) : ''), 301);
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
        // فیلتر خودرو یا از مسیر صفحه‌ی فرود می‌آید یا از سایدبار فروشگاه
        if ($carLanding !== null) {
            $query->searchCarModel($carLanding);
        } elseif ($request->filled('car_model')) {
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
        $carModel = $carLanding ?: $request->get('car_model', '');
        $carCategories = EshopCategory::orderBy('name')->get();

        // صفحه‌ی فرود: اسلاگ خودرو برای ساخت canonical و لینک‌های داخلی
        $carLandingSlug = $carLanding !== null ? CarModels::slugFor($carLanding) : null;

        return view('product.list', compact(
            'model', 'totalCount', 'categories', 'categoryCounts', 'selectedCategoryIds',
            'title', 'carModel', 'carCategories', 'categorySlug', 'carLanding', 'carLandingSlug'
        ));
    }

    /**
     * پیشنهاد زنده‌ی جستجوی هدر.
     *
     * همان عبارتی که کاربر تایپ می‌کند (بدون تغییر یا حدس زدن) با همان
     * scopeSearchText صفحه‌ی فروشگاه جستجو می‌شود، تا نتیجه‌ی این لیست و
     * نتیجه‌ی صفحه‌ی /shop هرگز با هم فرق نکنند. کنار محصول‌ها، مدل خودرو
     * و دسته‌بندیِ متناظر هم پیشنهاد می‌شود چون بخش بزرگی از جستجوها
     * به‌جای نام قطعه، نام خودرو است.
     */
    public function suggest(Request $request)
    {
        $raw  = trim((string) $request->get('q', ''));
        $term = Product::normalizeTerm($raw);

        // یک حرف تنها تقریبا همه‌ی محصولات را برمی‌گرداند؛ نه برای کاربر
        // مفید است و نه برای دیتابیس ارزان.
        if (mb_strlen($term) < 2) {
            return $this->suggestResponse(['q' => $raw, 'products' => [], 'terms' => [], 'total' => 0, 'url' => null]);
        }

        $payload = \Cache::remember('search_suggest:' . md5($term), 300, function () use ($term) {
            $products = Product::with(['images', 'categories'])
                ->where('is_active', 1)
                ->searchText($term)
                // مثل صفحه‌ی فروشگاه، محصولات عکس‌دار جلوتر می‌آیند؛ ردیف
                // بدون تصویر در یک لیست کوچک بیشتر به چشم می‌آید.
                ->orderByRaw("CASE WHEN file_path IS NULL OR file_path = '' OR file_path = '/images/no-image.svg' THEN 1 ELSE 0 END")
                ->orderByDesc('id')
                ->limit(7)
                ->get()
                ->map(fn (Product $product) => [
                    'title' => (string) $product->title,
                    'url'   => $product->url(),
                    'image' => $product->image(),
                    'sku'   => (string) $product->sku,
                    'car'   => (string) $product->car_model,
                    'price' => $product->isContactPrice()
                        ? contactPriceLabel()
                        : ((int) $product->price > 0 ? toPersianNumbers($product->price) . ' تومان' : ''),
                ])
                ->all();

            $terms = [];
            foreach (ProductCategory::cases() as $case) {
                if (count($terms) >= 3) {
                    break;
                }
                if (mb_strpos(Product::normalizeTerm($case->label()), $term) !== false) {
                    $terms[] = [
                        'label' => 'دسته‌بندی: ' . $case->label(),
                        'url'   => '/shop/' . rawurlencode($case->slug()),
                        'icon'  => 'fa-th-large',
                    ];
                }
            }

            $carModels = Product::where('is_active', 1)
                ->searchCarModel($term)
                ->whereNotNull('car_model')
                ->where('car_model', '<>', '')
                ->distinct()
                ->orderBy('car_model')
                ->limit(4)
                ->pluck('car_model');

            foreach ($carModels as $carModel) {
                $terms[] = [
                    'label' => 'قطعات ' . $carModel,
                    'url'   => '/shop?car_model=' . urlencode($carModel),
                    'icon'  => 'fa-car',
                ];
            }

            return [
                'products' => $products,
                'terms'    => $terms,
                'total'    => Product::where('is_active', 1)->searchText($term)->count(),
            ];
        });

        return $this->suggestResponse($payload + [
            'q'   => $raw,
            'url' => '/shop?title=' . urlencode($raw),
        ]);
    }

    private function suggestResponse(array $payload)
    {
        return response()
            ->json($payload, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Cache-Control', 'public, max-age=120');
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
        // approvedReviews همراه محصول لود می‌شود چون هم در صفحه نمایش
        // داده می‌شود و هم seo_product_schema از آن اسکیمای Review می‌سازد.
        $model = Product::with(['images', 'approvedReviews'])->where('is_active' , 1)->where('id' , $id)->first();
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
        $priceHistory = $this->priceHistoryPoints($model);

        return view('product.show' , compact('model','products','priceHistory'));
    }

    /**
     * نقطه‌های نمودار تاریخچه‌ی قیمت.
     *
     * فقط برای قطعه‌ای که قیمتش روی سایت اعلام می‌شود؛ قطعه‌ی استعلامی قیمت
     * نمایش‌دادنی ندارد و نمودارش هم معنا ندارد.
     *
     * با یک نقطه چیزی برای نشان‌دادن نیست (خطی که از جایی به جایی نمی‌رود)،
     * پس زیر دو نقطه خالی برمی‌گردد و بخش نمودار اصلا رندر نمی‌شود.
     *
     * @return array<int,array{price:int,date:string}>
     */
    private function priceHistoryPoints(Product $model): array
    {
        if ($model->isContactPrice()) {
            return [];
        }

        // روی محیطی که هنوز مایگریشن نخورده، صفحه‌ی محصول نباید ۵۰۰ بدهد.
        if (! Schema::hasTable('product_price_history')) {
            return [];
        }

        // آخرین ۲۴ تغییر کافی است؛ با نقطه‌های بیشتر نمودار در عرض موبایل
        // به هم می‌چسبد و خواندنی نیست.
        //
        // reorder() لازم است: رابطه‌ی priceHistory خودش ترتیب صعودی دارد و
        // orderByDesc به آن «اضافه» می‌شد نه جایگزینش. نتیجه این بود که
        // limit قدیمی‌ترین ۲۴ نقطه را برمی‌داشت و نمودار هم برعکس رسم می‌شد.
        $rows = $model->priceHistory()
            ->reorder('created_at', 'desc')
            ->orderByDesc('id')
            ->limit(24)
            ->get()
            ->reverse()
            ->values();

        if ($rows->count() < 2) {
            return [];
        }

        return $rows->map(fn ($row) => [
            'price' => (int) $row->price,
            'date'  => toPersianDate($row->created_at, false, true, 'Y/m/d'),
        ])->all();
    }

    /**
     * ثبت نظر و امتیاز روی محصول.
     *
     * نظر بی‌درنگ منتشر می‌شود و بدون انتظارِ تأیید روی صفحه‌ی محصول
     * می‌نشیند. مدیر همچنان می‌تواند از پنل نظر را رد کند تا از سایت
     * برداشته شود.
     */
    public function storeReview(Request $request, $id)
    {
        $product = Product::where('is_active', 1)->where('id', $id)->first();

        if (! $product) {
            return response()->view('errors.404', [], 404);
        }

        $customer = Auth::guard('customer')->user();

        $validator = Validator::make($request->all(), [
            'name'       => $customer ? 'nullable|string|max:100' : 'required|string|max:100',
            'rating'     => 'required|integer|min:1|max:5',
            'title'      => 'nullable|string|max:255',
            'comment'    => 'required|string|min:10|max:2000',
            // امتیاز معیارها اختیاری است؛ کسی که فقط ستاره‌ی کلی می‌دهد هم
            // باید بتواند نظرش را ثبت کند.
            'criteria'   => 'nullable|array',
            'criteria.*' => 'nullable|integer|min:1|max:5',
        ], [
            'name.required'    => 'نام خود را وارد کنید.',
            'rating.required'  => 'امتیاز خود را انتخاب کنید.',
            'rating.min'       => 'امتیاز باید بین ۱ تا ۵ ستاره باشد.',
            'rating.max'       => 'امتیاز باید بین ۱ تا ۵ ستاره باشد.',
            'comment.required' => 'متن نظر را بنویسید.',
            'comment.min'      => 'متن نظر باید حداقل ۱۰ کاراکتر باشد.',
            'criteria.*.min'   => 'امتیاز هر معیار باید بین ۱ تا ۵ ستاره باشد.',
            'criteria.*.max'   => 'امتیاز هر معیار باید بین ۱ تا ۵ ستاره باشد.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->withFragment('reviews');
        }

        // یک نظر از هر کاربر برای هر محصول کافی است. نظرِ ردشده به حساب
        // نمی‌آید تا کسی که نظرش برداشته شده بتواند دوباره بنویسد.
        $duplicate = ProductReview::where('product_id', $product->id)
            ->where('status', '!=', ProductReview::STATUS_REJECTED)
            ->where(fn ($q) => $customer
                ? $q->where('customer_id', $customer->id)
                : $q->where('ip', $request->ip()))
            ->exists();

        if ($duplicate) {
            return back()->with('review_notice', 'شما قبلا برای این محصول نظر ثبت کرده‌اید.')->withFragment('reviews');
        }

        $attributes = [
            'product_id'  => $product->id,
            'customer_id' => $customer->id ?? null,
            'name'        => trim((string) $request->input('name')) ?: ($customer->name ?? 'کاربر ناظر یدک'),
            'rating'      => (int) $request->input('rating'),
            'title'       => trim((string) $request->input('title')) ?: null,
            'comment'     => trim((string) $request->input('comment')),
            'status'      => ProductReview::STATUS_APPROVED,
            'is_buyer'    => (bool) $customer,
            'ip'          => $request->ip(),
        ];

        // روی محیطی که مایگریشن criteria هنوز اجرا نشده، ستون وجود ندارد و
        // درج با این کلید خطای SQL می‌دهد؛ نظر باید بدون ریزامتیاز ثبت شود.
        if (ProductReview::supportsCriteria()) {
            $attributes['criteria'] = ProductReview::sanitizeCriteria($request->input('criteria'));
        }

        ProductReview::create($attributes);

        return back()->with('review_notice', 'نظر شما ثبت شد و روی صفحه‌ی محصول نمایش داده می‌شود.')->withFragment('reviews');
    }

    public function favorite(Request $request)
    {
        $user = Auth::guard('customer')->user();
        if (! $user) {
            return redirect('/login');
        }
        /*
        | خواندن مستقیم از رابطه‌ی pivot؛ تازه‌ترین علاقه‌مندی بالاتر می‌آید.
        | محصولات غیرفعال نمایش داده نمی‌شوند تا کاربر روی قطعه‌ای که دیگر
        | در فروشگاه نیست کلیک نکند.
        */
        $products = $user->favoriteProducts()
            ->where('products.is_active', 1)
            ->with('categories')
            ->orderByDesc('product_favorites.created_at')
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

