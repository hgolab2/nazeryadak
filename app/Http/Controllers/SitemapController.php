<?php

namespace App\Http\Controllers;

use App\Enums\ProductCategory;
use App\Models\Article1;
use App\Models\Product;
use App\Support\CarModels;
use App\Support\SeoContent;
use Illuminate\Support\Facades\Cache;

/**
 * نقشه‌ی سایت به‌صورت Sitemap Index تقسیم شده است تا با رشد تعداد محصولات
 * از سقف ۵۰ هزار آدرس / ۵۰ مگابایتِ هر فایل عبور نکند و گوگل بتواند هر
 * بخش را جداگانه بخزد. خروجی‌ها کش می‌شوند چون کوئری محصولات سنگین است.
 */
class SitemapController extends Controller
{
    /** حداقل تعداد قطعه‌ی یک خودرو، برای اینکه صفحات ترکیبی‌اش هم اعلام شوند. */
    private const COMBO_MIN_PRODUCTS = 30;

    private function cacheMinutes(): int
    {
        return (int) config('seo.sitemap.cache_minutes', 180);
    }

    private function perMap(): int
    {
        return (int) config('seo.sitemap.products_per_map', 2000);
    }

    /**
     * محصولاتی که اجازه‌ی ایندکس دارند.
     *
     * محصولی که مدیر در تب سئو تیک Index را برداشته نباید در نقشه‌ی سایت
     * بیاید؛ فرستادن آدرسی که خودش noindex است، سیگنال متناقض به گوگل می‌دهد.
     * هر سه خروجی (شمارش، محصولات، تصاویر) باید از همین کوئری بیایند وگرنه
     * تعداد چانک‌ها با محتوایشان جور در نمی‌آید.
     */
    private function indexableProducts()
    {
        return Product::where('is_active', 1)->where('robots_index', 1);
    }

    /**
     * اعلان XML از اینجا اضافه می‌شود، نه از داخل ویو. اگر `<?xml` در فایل
     * Blade بماند، سروری که short_open_tag روشن دارد آن را تگ بازِ PHP
     * می‌خواند و ویو با ParseError می‌افتد.
     */
    private function xml(string $body)
    {
        $body = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' . "\n" . ltrim($body);

        return response($body, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('X-Robots-Tag', 'noindex');
    }

    /** فهرست نقشه‌های سایت. */
    public function index()
    {
        $body = Cache::remember('sitemap:index', now()->addMinutes($this->cacheMinutes()), function () {
            $productCount = $this->indexableProducts()->count();
            $chunks = max(1, (int) ceil($productCount / $this->perMap()));

            $maps = [
                ['loc' => seo_url('/sitemap-static.xml')],
                ['loc' => seo_url('/sitemap-categories.xml')],
                ['loc' => seo_url('/sitemap-articles.xml')],
                ['loc' => seo_url('/sitemap-images.xml')],
            ];

            for ($i = 1; $i <= $chunks; $i++) {
                $maps[] = ['loc' => seo_url('/sitemap-products-' . $i . '.xml')];
            }

            $lastmod = now()->toAtomString();
            foreach ($maps as &$map) {
                $map['lastmod'] = $lastmod;
            }

            return view('sitemap.index', ['maps' => $maps])->render();
        });

        return $this->xml($body);
    }

    /** صفحات ثابت و پرارزش سایت. */
    public function static()
    {
        $body = Cache::remember('sitemap:static', now()->addMinutes($this->cacheMinutes()), function () {
            $urls = [
                ['loc' => seo_url(), 'priority' => '1.0', 'changefreq' => 'daily'],
                ['loc' => seo_url('/shop'), 'priority' => '0.9', 'changefreq' => 'daily'],
                ['loc' => seo_url('/blog'), 'priority' => '0.7', 'changefreq' => 'weekly'],
                ['loc' => seo_url('/about-us'), 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => seo_url('/contact-us'), 'priority' => '0.6', 'changefreq' => 'monthly'],
                ['loc' => seo_url('/faq'), 'priority' => '0.6', 'changefreq' => 'monthly'],
                ['loc' => seo_url('/how-to-order'), 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => seo_url('/order-tracking'), 'priority' => '0.6', 'changefreq' => 'monthly'],
                ['loc' => seo_url('/shipping'), 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => seo_url('/payment-methods'), 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => seo_url('/return-policy'), 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => seo_url('/terms'), 'priority' => '0.3', 'changefreq' => 'yearly'],
                ['loc' => seo_url('/privacy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ];

            return view('sitemap.urlset', ['urls' => $urls])->render();
        });

        return $this->xml($body);
    }

    /** صفحات دسته‌بندی و صفحات پرجستجوی «قطعات فلان خودرو». */
    public function categories()
    {
        $body = Cache::remember('sitemap:categories', now()->addMinutes($this->cacheMinutes()), function () {
            $urls = [];

            foreach (ProductCategory::cases() as $category) {
                $urls[] = [
                    'loc' => seo_url('/shop/' . rawurlencode($category->slug())),
                    'priority' => '0.8',
                    'changefreq' => 'daily',
                ];
            }

            /*
            | صفحات مدل خودرو و ترکیب دسته × خودرو.
            |
            | فهرست خودروها از خود دیتابیس می‌آید نه از یک آرایه‌ی هاردکد،
            | پس هر مدل تازه‌ای که وارد انبار شود خودبه‌خود به نقشه اضافه
            | می‌شود. آدرس‌ها مسیری‌اند (/car/...) نه «?car_model=»؛ نسخه‌ی
            | قدیمی 301 می‌خورد و فرستادن آدرس ریدایرکت‌شونده در نقشه‌ی
            | سایت، خطای Search Console می‌سازد.
            */
            $comboCounts = CarModels::comboCounts();

            foreach (CarModels::all() as $carSlug => $car) {
                // خودروی کم‌محصول در صفحه noindex می‌گیرد؛ اعلام آدرس noindex
                // در نقشه‌ی سایت، در Search Console خطای «ارسال‌شده اما
                // ایندکس نشده» می‌سازد.
                if ($car['count'] < CarModels::INDEX_MIN_PRODUCTS) {
                    continue;
                }

                $urls[] = [
                    'loc' => seo_url('/car/' . rawurlencode($carSlug)),
                    'priority' => '0.75',
                    'changefreq' => 'daily',
                ];

                // ترکیب فقط برای خودروهای پرمحصول؛ ترکیبِ کم‌محصول اغلب
                // صفحه‌ی خالی می‌شود و ارزش خزش ندارد.
                if ($car['count'] < self::COMBO_MIN_PRODUCTS) {
                    continue;
                }

                foreach (ProductCategory::cases() as $category) {
                    /*
                    | تا پیش از این، برای هر خودروی پرمحصول هر ۱۱ دسته اعلام
                    | می‌شد، حتی ترکیب‌هایی که یک قطعه هم نداشتند. آن صفحات
                    | در ویو noindex می‌گیرند و بودجه‌ی خزش را هدر می‌دهند.
                    */
                    if (($comboCounts[$carSlug][$category->value] ?? 0) < SeoContent::COMBO_MIN_INDEXABLE) {
                        continue;
                    }

                    $urls[] = [
                        'loc' => seo_url('/car/' . rawurlencode($carSlug) . '/' . rawurlencode($category->slug())),
                        'priority' => '0.6',
                        'changefreq' => 'weekly',
                    ];
                }
            }

            return view('sitemap.urlset', ['urls' => $urls])->render();
        });

        return $this->xml($body);
    }

    /** محصولات، تکه‌تکه. */
    public function products(int $page = 1)
    {
        $page = max(1, $page);

        $body = Cache::remember('sitemap:products:' . $page, now()->addMinutes($this->cacheMinutes()), function () use ($page) {
            $urls = [];

            $this->indexableProducts()
                ->select(['id', 'title', 'sku', 'updated_at'])
                ->orderBy('id')
                ->forPage($page, $this->perMap())
                ->get()
                ->each(function ($product) use (&$urls) {
                    $urls[] = [
                        'loc' => seo_url($product->url()),
                        'lastmod' => optional($product->updated_at)->toAtomString(),
                        'priority' => '0.8',
                        'changefreq' => 'weekly',
                    ];
                });

            return view('sitemap.urlset', ['urls' => $urls])->render();
        });

        return $this->xml($body);
    }

    /** مقالات مجله. */
    public function articles()
    {
        $body = Cache::remember('sitemap:articles', now()->addMinutes($this->cacheMinutes()), function () {
            $urls = [];

            Article1::where('hidden', '0')
                ->where('deleted', '0')
                ->where('showdate', '<', date('Y-m-d H:i:s'))
                ->orderByDesc('showdate')
                ->limit(5000)
                ->get(['articleid', 'titr', 'showdate', 'updatetime'])
                ->each(function ($article) use (&$urls) {
                    $lastmod = null;
                    try {
                        $stamp = $article->updatetime ?: $article->showdate;
                        $lastmod = $stamp ? \Carbon\Carbon::parse($stamp)->toAtomString() : null;
                    } catch (\Throwable $e) {
                        // تاریخ خراب در دیتابیس نباید کل نقشه را از کار بیندازد.
                    }

                    $urls[] = [
                        'loc' => seo_url($article->getUrl()),
                        'lastmod' => $lastmod,
                        'priority' => '0.7',
                        'changefreq' => 'monthly',
                    ];
                });

            return view('sitemap.urlset', ['urls' => $urls])->render();
        });

        return $this->xml($body);
    }

    /**
     * نقشه‌ی تصاویر: باعث می‌شود عکس محصولات در Google Images ایندکس شوند،
     * که برای کوئری‌های تصویریِ قطعات خودرو ترافیک قابل توجهی می‌آورد.
     */
    public function images()
    {
        $body = Cache::remember('sitemap:images', now()->addMinutes($this->cacheMinutes()), function () {
            $entries = [];

            $this->indexableProducts()
                ->with('images')
                ->select(['id', 'title', 'sku', 'file_path'])
                ->orderByDesc('id')
                ->limit(5000)
                ->chunk(500, function ($products) use (&$entries) {
                    foreach ($products as $product) {
                        $images = [];

                        foreach ($product->images as $image) {
                            $images[] = ['loc' => seo_image_url($image->path), 'title' => $product->title];
                        }
                        if (! $images && $product->hasImage()) {
                            $images[] = ['loc' => seo_image_url($product->image()), 'title' => $product->title];
                        }
                        if (! $images) {
                            continue;
                        }

                        $entries[] = ['loc' => seo_url($product->url()), 'images' => $images];
                    }
                });

            return view('sitemap.images', ['entries' => $entries])->render();
        });

        return $this->xml($body);
    }

    /** robots.txt داینامیک تا دامنه و آدرس نقشه همیشه درست باشد. */
    public function robots()
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            '# بخش‌های شخصی و تراکنشی؛ ارزش ایندکس ندارند',
            'Disallow: /admin/',
            'Disallow: /dashboard',
            'Disallow: /dashboardAdmin',
            'Disallow: /loginAdmin',
            'Disallow: /login',
            'Disallow: /logout',
            'Disallow: /cart',
            'Disallow: /favorite',
            'Disallow: /profile/',
            'Disallow: /order/',
            'Disallow: /payment/',
            'Disallow: /address/',
            'Disallow: /auth/',
            'Disallow: /product/fetch-image/',
            'Disallow: /products/favorite/',
            '',
            '# پارامترهای فیلتر و مرتب‌سازی، محتوای تکراری می‌سازند',
            'Disallow: /*?*order=',
            'Disallow: /*?*orderby=',
            'Disallow: /*?*categories=',
            'Disallow: /*?*ajaxi=',
            'Disallow: /*?*utm_',
            'Disallow: /*?*fbclid=',
            'Disallow: /*?*gclid=',
            '',
            '# فایل‌های ظاهری صفحه باید خزیده شوند وگرنه رندر گوگل ناقص می‌ماند',
            'Allow: /assets/',
            'Allow: /upload/',
            'Allow: /images/',
            'Allow: *.css$',
            'Allow: *.js$',
            'Allow: *.webp$',
            'Allow: *.jpg$',
            'Allow: *.png$',
            '',
            '# خزنده‌های تجاری پرمصرف که سود سئویی ندارند',
            'User-agent: AhrefsBot',
            'Crawl-delay: 10',
            '',
            'User-agent: SemrushBot',
            'Crawl-delay: 10',
            '',
            'User-agent: MJ12bot',
            'Disallow: /',
            '',
            'Sitemap: ' . seo_url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines), 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
