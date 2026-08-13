<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductCategory;
use App\Http\Controllers\Controller;
use App\Models\NotFoundLog;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Redirect;
use App\Models\SeoTerm;
use App\Models\Setting;
use App\Support\CarModels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * مدیریت ریدایرکت‌ها و مانیتور خطاهای ۴۰۴ — همان دو ابزاری که در
 * Rank Math زیر منوی «Redirections» و «404 Monitor» هستند.
 */
class SeoAdminController extends Controller
{
    private const PER_PAGE = 30;

    /**
     * مسیرهایی که ساختن ریدایرکت رویشان ممنوع است.
     * بدون این محافظ، یک قاعده‌ی اشتباه روی /admin مدیر را از پنل بیرون
     * می‌انداخت و راه برگشتی جز دیتابیس نمی‌ماند.
     */
    private const PROTECTED_PREFIXES = ['/admin', '/loginAdmin', '/dashboardAdmin', '/logout'];

    private function guard()
    {
        if (! Auth::user()) {
            return redirect('/loginAdmin');
        }
        access(83);

        return null;
    }

    /* ---------------------------------------------------------- ریدایرکت‌ها */

    public function redirects(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $query = Redirect::query();

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('source_path', 'like', "%{$search}%")
                  ->orWhere('target_path', 'like', "%{$search}%");
            });
        }

        $model = $query->orderByDesc('id')->paginate(self::PER_PAGE)->withQueryString();

        // از صفحه‌ی ۴۰۴ با ?source=... به اینجا لینک می‌دهیم تا فرم پر بیاید
        $prefillSource = $request->input('source');

        return view('seo.admin.redirects', compact('model', 'prefillSource'));
    }

    public function redirectStore(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $data = $this->validateRedirect($request);
        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }

        if (Redirect::where('source_path', $data['source_path'])->exists()) {
            return back()->withErrors(['source_path' => 'برای این مسیر قبلا ریدایرکت ثبت شده است.'])->withInput();
        }

        Redirect::create($data);

        return redirect('/admin/seo/redirects')->with('success', 'ریدایرکت ثبت شد.');
    }

    public function redirectUpdate(Request $request, $id)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $redirect = Redirect::findOrFail($id);

        $data = $this->validateRedirect($request);
        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }

        $duplicate = Redirect::where('source_path', $data['source_path'])
            ->where('id', '!=', $redirect->id)
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['source_path' => 'برای این مسیر قبلا ریدایرکت ثبت شده است.'])->withInput();
        }

        $redirect->update($data);

        return redirect('/admin/seo/redirects')->with('success', 'ریدایرکت به‌روزرسانی شد.');
    }

    public function redirectDestroy($id)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        Redirect::findOrFail($id)->delete();

        return redirect('/admin/seo/redirects')->with('success', 'ریدایرکت حذف شد.');
    }

    /**
     * اعتبارسنجی مشترک ثبت و ویرایش. خروجی یا آرایه‌ی آماده‌ی ذخیره است
     * یا پاسخ بازگشت به فرم.
     */
    private function validateRedirect(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'source_path' => 'required|string|max:191',
            // مقصد فقط برای 410 (حذف دائمی) اختیاری است
            'target_path' => 'required_unless:status_code,410|nullable|string|max:500',
            'status_code' => 'required|integer|in:' . implode(',', array_keys(Redirect::STATUS_CODES)),
            'note'        => 'nullable|string|max:255',
        ], [
            'source_path.required' => 'مسیر مبدا را وارد کنید.',
            'target_path.required_unless' => 'مسیر مقصد را وارد کنید.',
            'status_code.in'       => 'نوع ریدایرکت معتبر نیست.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $source = Redirect::normalize($request->input('source_path'));
        $status = (int) $request->input('status_code');
        $target = $status === 410 ? null : trim((string) $request->input('target_path'));

        foreach (self::PROTECTED_PREFIXES as $prefix) {
            if (strcasecmp($source, $prefix) === 0 || str_starts_with(strtolower($source), strtolower($prefix) . '/')) {
                return back()->withErrors([
                    'source_path' => 'ساخت ریدایرکت روی مسیرهای مدیریتی مجاز نیست.',
                ])->withInput();
            }
        }

        // حلقه‌ی بی‌پایان: مبدا و مقصد یکی باشند
        if ($target !== null && ! preg_match('~^https?://~i', $target) && Redirect::normalize($target) === $source) {
            return back()->withErrors([
                'target_path' => 'مسیر مقصد نمی‌تواند با مسیر مبدا یکی باشد؛ حلقه‌ی ریدایرکت می‌سازد.',
            ])->withInput();
        }

        return [
            'source_path' => $source,
            'target_path' => $target,
            'status_code' => $status,
            'is_active'   => $request->boolean('is_active'),
            'note'        => trim((string) $request->input('note')) ?: null,
        ];
    }

    /* -------------------------------------------------- گزارش سلامت سئو */

    /**
     * ایرادهایی که یک صفحه‌ی محصول را از دیده‌شدن باز می‌دارند.
     * کلید هر ایراد، همان مقداری است که در ?issue= می‌آید.
     */
    private const HEALTH_ISSUES = [
        'no_description' => [
            'label' => 'بدون توضیحات',
            'hint'  => 'صفحه‌ی بدون متن، «محتوای نازک» است و اغلب ایندکس نمی‌شود.',
        ],
        'no_image' => [
            'label' => 'بدون تصویر',
            'hint'  => 'بدون تصویر، محصول در Google Images و کارت‌های خرید دیده نمی‌شود.',
        ],
        'no_car_model' => [
            'label' => 'بدون خودرو مناسب',
            'hint'  => 'خودرو مناسب، محصول را وارد صفحات فرود «قطعات {خودرو}» می‌کند.',
        ],
        'no_sku' => [
            'label' => 'بدون کد فنی',
            'hint'  => 'بخش بزرگی از جستجوهای این بازار با کد فنی انجام می‌شود.',
        ],
        'noindex' => [
            'label' => 'noindex',
            'hint'  => 'این محصولات عمدا از ایندکس خارج شده‌اند و در نقشه‌ی سایت نیستند.',
        ],
    ];

    /** فیلتر مربوط به هر ایراد؛ همین‌جا تا شمارش و فهرست از یک منطق بیایند. */
    private function applyHealthIssue($query, string $issue)
    {
        return match ($issue) {
            'no_description' => $query->where(function ($q) {
                $q->whereNull('description')->orWhereRaw('CHAR_LENGTH(description) < 20');
            }),
            'no_image' => $query->where(function ($q) {
                $q->whereNull('file_path')->orWhere('file_path', '')->orWhere('file_path', '/images/no-image.svg');
            }),
            'no_car_model' => $query->where(function ($q) {
                $q->whereNull('car_model')->orWhere('car_model', '');
            }),
            'no_sku' => $query->where(function ($q) {
                $q->whereNull('sku')->orWhere('sku', '');
            }),
            'noindex' => $query->where('robots_index', 0),
            default   => $query,
        };
    }

    public function health(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $issue = (string) $request->input('issue', 'no_description');
        if (! isset(self::HEALTH_ISSUES[$issue])) {
            $issue = 'no_description';
        }

        $active = Product::where('is_active', 1);
        $totalActive = (clone $active)->count();

        $counts = [];
        foreach (array_keys(self::HEALTH_ISSUES) as $key) {
            $counts[$key] = $this->applyHealthIssue(Product::where('is_active', 1), $key)->count();
        }

        $query = $this->applyHealthIssue(Product::where('is_active', 1), $issue);

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $model = $query->orderByDesc('id')->paginate(self::PER_PAGE)->withQueryString();

        return view('seo.admin.health', [
            'model'       => $model,
            'issue'       => $issue,
            'issues'      => self::HEALTH_ISSUES,
            'counts'      => $counts,
            'totalActive' => $totalActive,
            'reviewCount' => ProductReview::where('status', ProductReview::STATUS_PENDING)->count(),
            'termCount'   => SeoTerm::count(),
        ]);
    }

    /**
     * ساخت پیش‌نویس توضیحات برای محصولاتِ همین صفحه از گزارش.
     *
     * عمدا فقط روی صفحه‌ی جاری کار می‌کند نه کل ۲۵۰۰ محصول: نتیجه بلافاصله
     * قابل بازبینی است و اگر لحن متن مناسب نبود، اشتباه در مقیاس تکرار
     * نمی‌شود. برای اجرای انبوه، دستور «php artisan products:describe» هست.
     */
    public function healthGenerate(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));

        if (! $ids) {
            return back()->withErrors(['ids' => 'محصولی انتخاب نشده است.']);
        }

        $written = 0;
        foreach (Product::with('categories')->whereIn('id', $ids)->get() as $product) {
            $text = $product->generateDescription();
            if ($text === '') {
                continue;
            }

            // بدون دست‌زدن به updated_at؛ آن ستون در lastmod نقشه‌ی سایت است
            \Illuminate\Support\Facades\DB::table('products')
                ->where('id', $product->id)
                ->update(['description' => $text]);
            $written++;
        }

        return back()->with('success', $written . ' پیش‌نویس توضیحات ساخته شد. حتما بازبینی و ویرایششان کنید.');
    }

    /* -------------------------------------------------------- تنظیمات سئو */

    /**
     * گروه‌بندی کلیدهای قابل ویرایش برای نمایش در فرم.
     * ترتیب و برچسب فارسی اینجاست تا ویو منطق نداشته باشد.
     */
    private const SETTING_GROUPS = [
        'هویت سایت' => [
            'default_title'       => ['label' => 'عنوان پیش‌فرض صفحات', 'type' => 'text'],
            'default_description' => ['label' => 'توضیحات متای پیش‌فرض', 'type' => 'textarea'],
            'default_keywords'    => ['label' => 'کلمات کلیدی پیش‌فرض', 'type' => 'textarea'],
        ],
        'تأیید مالکیت' => [
            'verification.google' => ['label' => 'Google Search Console', 'type' => 'text', 'ltr' => true],
            'verification.bing'   => ['label' => 'Bing Webmaster', 'type' => 'text', 'ltr' => true],
            'verification.yandex' => ['label' => 'Yandex Webmaster', 'type' => 'text', 'ltr' => true],
            'verification.enamad' => ['label' => 'نماد اعتماد الکترونیکی', 'type' => 'text', 'ltr' => true],
        ],
        'ابزارهای تحلیلی' => [
            'analytics.ga4'     => ['label' => 'Google Analytics 4 (G-…)', 'type' => 'text', 'ltr' => true],
            'analytics.gtm'     => ['label' => 'Google Tag Manager (GTM-…)', 'type' => 'text', 'ltr' => true],
            'analytics.clarity' => ['label' => 'Microsoft Clarity', 'type' => 'text', 'ltr' => true],
        ],
        'اطلاعات کسب‌وکار (سئوی محلی)' => [
            'business.phone'       => ['label' => 'تلفن', 'type' => 'text'],
            'business.email'       => ['label' => 'ایمیل', 'type' => 'text', 'ltr' => true],
            'business.street'      => ['label' => 'نشانی', 'type' => 'text'],
            'business.city'        => ['label' => 'شهر', 'type' => 'text'],
            'business.region'      => ['label' => 'استان', 'type' => 'text'],
            'business.postal_code' => ['label' => 'کد پستی', 'type' => 'text'],
            'business.latitude'    => ['label' => 'عرض جغرافیایی', 'type' => 'text', 'ltr' => true],
            'business.longitude'   => ['label' => 'طول جغرافیایی', 'type' => 'text', 'ltr' => true],
        ],
    ];

    public function settings()
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $values = [];
        foreach (self::SETTING_GROUPS as $fields) {
            foreach (array_keys($fields) as $key) {
                $values[$key] = [
                    'saved'    => Setting::get('seo_' . str_replace('.', '_', $key), ''),
                    'fallback' => config('seo.' . $key, ''),
                ];
            }
        }

        return view('seo.admin.settings', [
            'groups' => self::SETTING_GROUPS,
            'values' => $values,
        ]);
    }

    public function settingsUpdate(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $input = (array) $request->input('seo', []);

        foreach (self::SETTING_GROUPS as $fields) {
            foreach (array_keys($fields) as $key) {
                // کلیدها در فرم با _ می‌آیند چون نقطه در name آرایه‌ی HTML مشکل‌ساز است
                $formKey = str_replace('.', '_', $key);
                Setting::put('seo_' . $formKey, trim((string) ($input[$formKey] ?? '')));
            }
        }

        // متاتگ‌ها و اسکیماها از seo_config می‌آیند و در نقشه‌ی سایت کش شده‌اند
        \Illuminate\Support\Facades\Cache::forget('sitemap:index');

        return redirect('/admin/seo/settings')->with('success', 'تنظیمات سئو ذخیره شد.');
    }

    /* -------------------------------------------------------- مدیریت نظرات */

    public function reviews(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $status = $request->input('status', ProductReview::STATUS_PENDING);
        $query = ProductReview::with('product');

        if (isset(ProductReview::STATUSES[$status])) {
            $query->where('status', $status);
        }

        $model = $query->orderByDesc('id')->paginate(self::PER_PAGE)->withQueryString();

        $counts = ProductReview::selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->all();

        return view('seo.admin.reviews', compact('model', 'status', 'counts'));
    }

    public function reviewStatus(Request $request, $id)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $status = (string) $request->input('status');
        if (! isset(ProductReview::STATUSES[$status])) {
            return back()->withErrors(['status' => 'وضعیت نامعتبر است.']);
        }

        // رویداد saved مدل، میانگین امتیاز محصول را از نو حساب می‌کند
        ProductReview::findOrFail($id)->update(['status' => $status]);

        return back()->with('success', 'وضعیت نظر تغییر کرد.');
    }

    public function reviewDestroy($id)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        ProductReview::findOrFail($id)->delete();

        return back()->with('success', 'نظر حذف شد.');
    }

    /* -------------------------------------------------------- صفحات فرود سئو */

    /**
     * فهرست صفحات فرود.
     *
     * ترکیب دسته × خودرو ۲۳۱ حالت دارد؛ همه‌شان فهرست نمی‌شوند. فقط
     * ترکیب‌هایی که مدیر قبلا برایشان متن نوشته نمایش داده می‌شوند و بقیه
     * از فرم «ساخت ترکیب تازه» ساخته می‌شوند.
     */
    public function terms(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $saved = SeoTerm::all()->keyBy(fn ($term) => $term->type . ':' . $term->slug);

        $categories = [];
        foreach (ProductCategory::cases() as $category) {
            $categories[] = [
                'slug'  => $category->slug(),
                'name'  => $category->label(),
                'url'   => '/shop/' . rawurlencode($category->slug()),
                'term'  => $saved[SeoTerm::TYPE_CATEGORY . ':' . $category->slug()] ?? null,
            ];
        }

        $cars = [];
        foreach (CarModels::all() as $slug => $car) {
            $cars[] = [
                'slug'  => $slug,
                'name'  => $car['name'],
                'count' => $car['count'],
                'url'   => '/car/' . rawurlencode($slug),
                'term'  => $saved[SeoTerm::TYPE_CAR . ':' . $slug] ?? null,
            ];
        }

        $combos = $saved->filter(fn ($term) => $term->type === SeoTerm::TYPE_CAR_CATEGORY)->values();

        return view('seo.admin.terms', [
            'categories'    => $categories,
            'cars'          => $cars,
            'combos'        => $combos,
            'carOptions'    => CarModels::all(),
            'categoryCases' => ProductCategory::cases(),
        ]);
    }

    /** فرم ویرایش؛ اگر ترم هنوز ساخته نشده باشد یک نمونه‌ی خالی برمی‌گردد. */
    public function termEdit(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $type = (string) $request->input('type');
        $slug = (string) $request->input('slug');

        // فرم «ساخت ترکیب تازه» خودرو و دسته را جدا می‌فرستد
        if ($type === SeoTerm::TYPE_CAR_CATEGORY && $slug === '' && $request->filled(['car', 'category'])) {
            $slug = $request->input('car') . '/' . $request->input('category');
        }

        if (! isset(SeoTerm::TYPES[$type]) || ! $this->termTargetExists($type, $slug)) {
            return redirect('/admin/seo/terms')->withErrors(['slug' => 'صفحه‌ی فرود موردنظر پیدا نشد.']);
        }

        $term = SeoTerm::where('type', $type)->where('slug', $slug)->first()
            ?: new SeoTerm(['type' => $type, 'slug' => $slug, 'name' => $this->termDefaultName($type, $slug), 'robots_index' => true, 'is_active' => true]);

        return view('seo.admin.term-edit', [
            'term'      => $term,
            'targetUrl' => $this->termUrl($type, $slug),
            'autoName'  => $this->termDefaultName($type, $slug),
        ]);
    }

    public function termSave(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'type'            => 'required|string|in:' . implode(',', array_keys(SeoTerm::TYPES)),
            'slug'            => 'required|string|max:160',
            'name'            => 'nullable|string|max:255',
            'heading'         => 'nullable|string|max:255',
            'intro'           => 'nullable|string|max:20000',
            'body'            => 'nullable|string|max:20000',
            'seo_title'       => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'focus_keyword'   => 'nullable|string|max:255',
        ], [
            'slug.required' => 'اسلاگ صفحه مشخص نیست.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $type = (string) $request->input('type');
        $slug = (string) $request->input('slug');

        if (! $this->termTargetExists($type, $slug)) {
            return back()->withErrors(['slug' => 'صفحه‌ی فرود موردنظر وجود ندارد.'])->withInput();
        }

        SeoTerm::updateOrCreate(
            ['type' => $type, 'slug' => $slug],
            [
                'name'            => trim((string) $request->input('name')) ?: $this->termDefaultName($type, $slug),
                'heading'         => trim((string) $request->input('heading')) ?: null,
                'intro'           => trim((string) $request->input('intro')) ?: null,
                'body'            => trim((string) $request->input('body')) ?: null,
                'seo_title'       => trim((string) $request->input('seo_title')) ?: null,
                'seo_description' => trim((string) $request->input('seo_description')) ?: null,
                'focus_keyword'   => trim((string) $request->input('focus_keyword')) ?: null,
                'robots_index'    => $request->boolean('robots_index'),
                'is_active'       => $request->boolean('is_active'),
            ]
        );

        return redirect('/admin/seo/terms')->with('success', 'محتوای صفحه‌ی فرود ذخیره شد.');
    }

    public function termDestroy($id)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        SeoTerm::findOrFail($id)->delete();

        return redirect('/admin/seo/terms')->with('success', 'محتوای صفحه‌ی فرود حذف شد؛ از این پس متن خودکار استفاده می‌شود.');
    }

    /** آیا صفحه‌ای که ترم به آن اشاره می‌کند واقعا وجود دارد؟ */
    private function termTargetExists(string $type, string $slug): bool
    {
        return match ($type) {
            SeoTerm::TYPE_CATEGORY => ProductCategory::fromSlug($slug) !== null,
            SeoTerm::TYPE_CAR      => CarModels::fromSlug($slug) !== null,
            SeoTerm::TYPE_CAR_CATEGORY => (function () use ($slug) {
                $parts = explode('/', $slug);

                return count($parts) === 2
                    && CarModels::fromSlug($parts[0]) !== null
                    && ProductCategory::fromSlug($parts[1]) !== null;
            })(),
            default => false,
        };
    }

    private function termDefaultName(string $type, string $slug): string
    {
        if ($type === SeoTerm::TYPE_CATEGORY) {
            return ProductCategory::fromSlug($slug)?->label() ?? $slug;
        }

        if ($type === SeoTerm::TYPE_CAR) {
            return 'قطعات ' . (CarModels::fromSlug($slug) ?? $slug);
        }

        $parts = explode('/', $slug);
        if (count($parts) === 2) {
            $category = ProductCategory::fromSlug($parts[1])?->label() ?? $parts[1];

            return $category . ' ' . (CarModels::fromSlug($parts[0]) ?? $parts[0]);
        }

        return $slug;
    }

    private function termUrl(string $type, string $slug): string
    {
        return match ($type) {
            SeoTerm::TYPE_CATEGORY => '/shop/' . rawurlencode($slug),
            SeoTerm::TYPE_CAR      => '/car/' . rawurlencode($slug),
            default => '/car/' . implode('/', array_map('rawurlencode', explode('/', $slug))),
        };
    }

    /* ------------------------------------------------------------ مانیتور ۴۰۴ */

    public function notFound(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $sort = $request->input('sort') === 'hits' ? 'hits' : 'last_seen_at';

        $query = NotFoundLog::query();

        if ($search = trim((string) $request->input('q'))) {
            $query->where('path', 'like', "%{$search}%");
        }

        $model = $query->orderByDesc($sort)->paginate(self::PER_PAGE)->withQueryString();

        return view('seo.admin.not-found', compact('model', 'sort'));
    }

    public function notFoundDestroy($id)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        NotFoundLog::findOrFail($id)->delete();

        return back()->with('success', 'ردیف حذف شد.');
    }

    public function notFoundClear()
    {
        if ($response = $this->guard()) {
            return $response;
        }

        NotFoundLog::query()->delete();

        return redirect('/admin/seo/404')->with('success', 'فهرست خطاهای ۴۰۴ پاک شد.');
    }
}
