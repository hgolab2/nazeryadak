<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\ProductFavorite;
use App\Enums\ProductCategory;
use App\Models\Category;
use App\Models\EshopCategory;
use App\Models\ProductReview;
use App\Models\ProductView;
use App\Models\SearchTerm;
use App\Support\CarModels;
use App\Support\PartTypes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use App\Services\BaleNotifier;
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

    /**
     * صفحه‌ی فرود نوع قطعه: /part/{slug}
     *
     * «لنت ترمز» — یک پله دقیق‌تر از دسته‌بندی («چرخ، ترمز و جلوبندی») و
     * همان چیزی که کاربر واقعا جستجو می‌کند.
     */
    public function part(Request $request, string $part)
    {
        $partSlug = rawurldecode($part);

        if (! PartTypes::has($partSlug)) {
            return response()->view('errors.404', [], 404);
        }

        return $this->index($request, null, null, $partSlug);
    }

    /**
     * صفحه‌ی ترکیبی نوع قطعه × خودرو: /part/{part}/{car}
     *
     * «لنت ترمز پژو ۲۰۶» — دقیق‌ترین شکل کوئری این بازار.
     */
    public function partCar(Request $request, string $part, string $car)
    {
        $partSlug = rawurldecode($part);
        $carName  = CarModels::fromSlug(rawurldecode($car));

        if (! PartTypes::has($partSlug) || $carName === null) {
            return response()->view('errors.404', [], 404);
        }

        return $this->index($request, null, $carName, $partSlug);
    }

    public function index(Request $request, ?string $categorySlug = null, ?string $carLanding = null, ?string $partLanding = null)
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

        /*
        | نوع قطعه، برخلاف دسته و خودرو، کنترلی در سایدبار ندارد؛ پس
        | صفحه‌بندی ایجکسی آن را به‌صورت «?part=» می‌فرستد وگرنه با رفتن به
        | صفحه‌ی دوم، فیلتر قطعه گم می‌شد و همه‌ی محصولات برمی‌گشت.
        |
        | همان آدرس اگر مستقیم (غیرایجکسی) صدا زده شود با 301 به /part/{slug}
        | می‌رود — همان قاعده‌ای که برای ?category= و ?car_model= برقرار است،
        | تا از هر صفحه فقط یک نسخه ایندکس شود.
        */
        if ($partLanding === null && $request->filled('part') && PartTypes::has($request->part)) {
            if ($request->ajax() || $request->filled('ajaxi')) {
                $partLanding = (string) $request->part;
            } else {
                $rest = $request->except('part');

                return redirect(
                    '/part/' . rawurlencode($request->part) . ($rest ? '?' . http_build_query($rest) : ''),
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
        // نوع قطعه فقط از مسیر /part/{slug} می‌آید و با فیلتر خودرو ترکیب می‌شود
        if ($partLanding !== null) {
            $query->partType($partLanding);
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

            /*
            | نردبانِ بازدید: قطعه‌ای که در یک روز به آستانه‌ی بازدید رسیده،
            | بالای فهرست می‌نشیند. مرتب‌سازی پیش‌فرض فروشگاه «جدیدترین» بود و
            | قطعه‌ای که همین روزها دنبالش هستند، اگر ماه پیش ثبت شده بود، در
            | صفحه‌ی پنجم نتیجه‌ی جستجو می‌ماند.
            |
            | بعد از ترتیبِ عکس‌دار می‌آید نه قبلش: کارتِ بی‌عکس در صدر فهرست،
            | حتی اگر پربازدید باشد، صفحه را بی‌اعتبار نشان می‌دهد.
            */
            $query->orderByPromotion();
        }

        $query->orderBy($orderColumn, $orderDirection);
        /*
        | فیلترهای فعال باید روی لینک‌های صفحه‌بندی بمانند. بدون این، «صفحه‌ی
        | ۲»ِ نتیجه‌ی یک جستجو به صفحه‌ی دوم کل فروشگاه می‌رفت و rel=next هدِ
        | صفحه هم به همان آدرس اشتباه اشاره می‌کرد.
        |
        | ajaxi/page کنار گذاشته می‌شوند: اولی فقط پرچم درخواست ایجکسی است و
        | در robots.txt هم Disallow شده، دومی را خود Paginator می‌سازد.
        */
        $model = $query->paginate($perPage)->appends($request->except(['page', 'ajaxi']));
        $totalCount = $model->total();
        $this->recordSearch($request, $title, $totalCount);
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

        // نام نمایشی نوع قطعه برای عنوان، H1 و مسیر راهنما
        $partName = $partLanding !== null ? PartTypes::name($partLanding) : null;

        return view('product.list', compact(
            'model', 'totalCount', 'categories', 'categoryCounts', 'selectedCategoryIds',
            'title', 'carModel', 'carCategories', 'categorySlug', 'carLanding', 'carLandingSlug',
            'partLanding', 'partName'
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
                // همان نردبانِ فهرست فروشگاه؛ هفت ردیفِ این لیست باید همان
                // چیزی را نشان بدهد که صفحه‌ی نتیجه هم اول می‌آورد.
                ->orderByPromotion()
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
                // صفحه‌ی فرود مسیری؛ «?car_model=» همین‌جا 301 می‌خورد و کاربر
                // بی‌دلیل یک پرش اضافه می‌کرد.
                $terms[] = [
                    'label' => 'قطعات ' . $carModel,
                    'url'   => car_landing_url($carModel),
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

    /**
     * ثبت جستجو: هم در جدول عبارت‌ها، هم گزارش به بله.
     *
     * سه شرطِ مشترک اینجاست چون هر دو مصرف به هر سه نیاز دارند:
     *
     * ۱) فقط درخواست غیرایجکسی — صفحه‌بندی و فیلترهای سایدبار ایجکسی‌اند و
     *    هر کدامشان همان عبارت را دوباره می‌فرستند.
     * ۲) فقط صفحه‌ی اول — رفتن به صفحه‌ی دو جستجوی تازه نیست.
     * ۳) خزنده نه — لینک‌های «جستجوهای پرتکرار» خودشان به /shop?title= اشاره
     *    می‌کنند و بدون این فیلتر، گوگل با خزیدنشان همان چند عبارت را
     *    برای همیشه در صدر نگه می‌داشت.
     */
    private function recordSearch(Request $request, string $title, int $totalCount): void
    {
        $term = trim($title);

        if ($term === '' || $request->ajax() || $request->filled('ajaxi')) {
            return;
        }

        if ((int) $request->get('page', 1) > 1) {
            return;
        }

        if (ProductView::isBot((string) $request->userAgent())) {
            return;
        }

        SearchTerm::record($term, $totalCount);

        $this->reportSearch($request, $term, $totalCount);
    }

    /**
     * گزارش جستجوی کاربر به بله.
     *
     * چیزی که کاربرها در جعبه‌ی جستجو می‌نویسند، مستقیم‌ترین فهرستِ «چه
     * قطعه‌ای را باید بیاوریم» است؛ مخصوصا جستجوهای بی‌نتیجه که تا امروز
     * هیچ‌جا دیده نمی‌شدند و کاربرشان بی‌صدا از سایت می‌رفت.
     *
     * جدا از محافظ‌های recordSearch، یک عبارت در بازه‌ی throttle فقط یک بار
     * فرستاده می‌شود — Cache::add اتمیک است، پس دو درخواست هم‌زمان هم دو
     * پیام نمی‌سازند. ثبت در جدول اما throttle ندارد: شمارنده باید همه‌ی
     * جستجوها را بشمارد وگرنه «پرتکرار» معنایش را از دست می‌دهد.
     */
    private function reportSearch(Request $request, string $term, int $totalCount): void
    {
        try {
            if (! BaleNotifier::enabled('product_search')) {
                return;
            }

            $ttl = (int) config('bale.search_throttle', 900);
            $key = 'bale_search:' . md5(Product::normalizeTerm($term));

            if ($ttl > 0 && ! \Cache::add($key, true, $ttl)) {
                return;
            }

            $customer = Auth::guard('customer')->user();

            BaleNotifier::send('product_search', [
                'عبارت'  => $term,
                'نتیجه'  => $totalCount > 0 ? $totalCount . ' قطعه' : 'هیچ نتیجه‌ای نداشت',
                'مشتری'  => $customer ? trim(($customer->fullName() ?: '') . ' ' . ($customer->phone ?? '')) : '',
                'آدرس'   => url('/shop?title=' . urlencode($term)),
            ]);
        } catch (\Throwable $e) {
            // اطلاع‌رسانی نباید صفحه‌ی نتیجه‌ی جستجو را بشکند.
        }
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

    /**
     * قطعه‌های مرتبط با همین محصول برای کاروسل پایین صفحه.
     *
     * قبلا اینجا getProduct() صدا زده می‌شد که فقط «۸ محصول آخر سایت» را
     * می‌داد: زیر واشر سرسیلندر سمند، آینه‌ی تارا و سپر پژو پارس می‌نشست.
     * نه برای کاربر معنا داشت و نه لینک داخلی مرتبطی به گوگل می‌رساند.
     *
     * ترتیب اولویت: هم‌خودرو و هم‌دسته > هم‌خودرو > هم‌دسته. اگر باز هم کم
     * بود، با تازه‌ترین محصول‌ها پر می‌شود تا کاروسل هیچ‌وقت خالی نماند.
     *
     * paginate() هم حذف شد: یک COUNT(*) اضافه روی چهار هزار ردیف می‌زد در
     * حالی که این کاروسل اصلا صفحه‌بندی ندارد.
     */
    private function relatedProducts(Product $model, int $count)
    {
        $base = fn () => Product::with('categories')
            ->where('is_active', 1)
            ->where('file_path', '!=', '')
            ->where('id', '!=', $model->id);

        $related = collect();

        /*
        | کلید یکتاسازی عنوان.
        |
        | یک قطعه اغلب چند بار با عنوان تقریبا یکسان ثبت شده و فقط نام خودرو
        | یا کد فنی به تهش چسبیده: «شمع … میلی متر»، «… میلی متر سمند»،
        | «… میلی متر سمند 1040300817». مقایسه‌ی رشته‌ای دقیق این‌ها را جدا
        | می‌بیند و کاروسل سه کارت عملا یکسان کنار هم نشان می‌داد. با حذف
        | رقم‌ها و نشانه‌ها و مقایسه‌ی ابتدای عنوان، هر سه یکی حساب می‌شوند.
        */
        $titleKey = function ($title) {
            $t = preg_replace('/[\d\.\-_،,()\[\]]+/u', ' ', (string) $title);
            $t = trim(preg_replace('/\s+/u', ' ', $t));

            return mb_substr($t, 0, 40);
        };
        $seenTitles = [];

        $categoryId = $model->category_id;

        $tiers = [];
        if (! empty($model->car_model) && $categoryId) {
            $tiers[] = fn () => $base()->where('car_model', $model->car_model)->where('category_id', $categoryId);
        }
        if (! empty($model->car_model)) {
            $tiers[] = fn () => $base()->where('car_model', $model->car_model);
        }
        if ($categoryId) {
            $tiers[] = fn () => $base()->where('category_id', $categoryId);
        }
        $tiers[] = fn () => $base();

        foreach ($tiers as $tier) {
            if ($related->count() >= $count) {
                break;
            }

            $rows = $tier()
                ->when($related->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $related->pluck('id')->all()))
                ->orderByDesc('id')
                // چند برابر لازم گرفته می‌شود چون بخشی از ردیف‌ها در مرحله‌ی
                // بعد به‌خاطر هم‌عنوان بودن کنار می‌روند
                ->limit(($count - $related->count()) * 3)
                ->get();

            foreach ($rows as $row) {
                if ($related->count() >= $count) {
                    break;
                }

                $key = $titleKey($row->title);
                if ($key === '' || isset($seenTitles[$key])) {
                    continue;
                }

                $seenTitles[$key] = true;
                $related->push($row);
            }
        }

        return $related;
    }

    function show($id, $slug = null)
    {
        if(!$id){
            return response()->view('errors.404', [], 404);
        }
        // approvedReviews همراه محصول لود می‌شود چون هم در صفحه نمایش
        // داده می‌شود و هم seo_product_schema از آن اسکیمای Review می‌سازد.
        // categories هم لازم است: قطعه‌ای که ستون category_id‌اش خالی است،
        // دسته‌اش را از جدول واسط می‌گیرد و بدون آن، حلقه‌ی دسته در مسیر
        // راهنما و لینک داخلی به صفحه‌ی دسته‌بندی ساخته نمی‌شود.
        $model = Product::with(['images', 'approvedReviews', 'categories'])->where('is_active' , 1)->where('id' , $id)->first();
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
        /*
        | ثبت بازدید بعد از بررسی کانونیکال انجام می‌شود: درخواستی که 301
        | می‌خورد صفحه‌ی محصول را ندیده و شمردنش هر بازدید را دو بار حساب
        | می‌کرد (یک بار روی آدرس بدون اسلاگ، یک بار روی مقصد).
        */
        ProductView::record($model, request());

        $products = $this->relatedProducts($model, 8);
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

        // نظر بدون تأیید روی صفحه می‌نشیند، پس مدیر باید همان لحظه ببیندش
        BaleNotifier::send('product_review', [
            'محصول'  => $product->title ?? ('#' . $product->id),
            'امتیاز' => $attributes['rating'] . ' از ۵',
            'نویسنده' => $attributes['name'],
            'خریدار' => $attributes['is_buyer'] ? 'بله' : 'خیر',
            'متن'    => mb_substr($attributes['comment'], 0, 300),
            'صفحه'   => url('/product/' . $product->id),
        ]);

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

