<?php

namespace App\Http\Controllers;

use App\Enums\ProductCategory;
use App\Models\Article1;
use App\Models\Product;
use App\Support\CarModels;
use App\Support\PartTypes;
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

    /**
     * خزنده‌هایی که خروجی‌شان به پاسخ مدل‌های زبانی می‌رسد.
     *
     * فهرست عمدا بلند است: «User-agent: *» از نظر استاندارد اجازه‌شان را
     * می‌دهد، اما نام‌بردن صریح دو کار می‌کند — تفسیر سخت‌گیرانه‌ی بعضی از
     * این خزنده‌ها از قواعد عمومی را خنثی می‌کند، و رضایت سایت به استفاده‌ی
     * هوش مصنوعی را روشن اعلام می‌کند (Google-Extended و Applebot-Extended
     * فقط همین نقش را دارند و مسیر خزش را عوض نمی‌کنند).
     */
    private const AI_CRAWLERS = [
        // OpenAI — ChatGPT: ایندکس جست‌وجو، فچر لحظه‌ای، دانش پایه
        'OAI-SearchBot',
        'ChatGPT-User',
        'GPTBot',
        // Anthropic — Claude
        'ClaudeBot',
        'Claude-User',
        'Claude-SearchBot',
        'anthropic-ai',
        // Google — Gemini و AI Overviews
        'Google-Extended',
        'GoogleOther',
        'Google-CloudVertexBot',
        // Microsoft — Copilot روی ایندکس Bing سوار است
        'bingbot',
        'BingPreview',
        // Perplexity
        'PerplexityBot',
        'Perplexity-User',
        // Apple — Siri و Apple Intelligence
        'Applebot',
        'Applebot-Extended',
        // Amazon — Alexa و Rufus
        'Amazonbot',
        // Meta AI
        'Meta-ExternalAgent',
        'Meta-ExternalFetcher',
        'FacebookBot',
        // بقیه‌ی موتورهای پاسخ‌گو و ایجنت‌های فچ‌کننده
        'DuckAssistBot',
        'YouBot',
        'MistralAI-User',
        'cohere-ai',
        'FirecrawlAgent',
        // Common Crawl؛ ورودی مشترک بسیاری از مجموعه‌داده‌های آموزشی
        'CCBot',
    ];
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

            /*
            | صفحات «نوع قطعه» و «نوع قطعه × خودرو».
            |
            | دقیق‌ترین شکل کوئری این بازار («لنت ترمز پژو ۲۰۶») و پرارزش‌ترین
            | صفحات سایت. مثل بقیه، فقط آدرس‌هایی اعلام می‌شوند که واقعا
            | محصول دارند؛ آدرس noindex در نقشه‌ی سایت خطای Search Console
            | می‌سازد.
            */
            $partCarCounts = PartTypes::carCounts();

            foreach (PartTypes::counts() as $partSlug => $partCount) {
                if ($partCount >= PartTypes::INDEX_MIN_PRODUCTS) {
                    $urls[] = [
                        'loc' => seo_url('/part/' . rawurlencode($partSlug)),
                        'priority' => '0.8',
                        'changefreq' => 'weekly',
                    ];
                }

                foreach ($partCarCounts[$partSlug] ?? [] as $carSlug => $comboCount) {
                    if ($comboCount < PartTypes::COMBO_MIN_INDEXABLE) {
                        continue;
                    }

                    // خودروی کم‌محصول در صفحه noindex می‌گیرد؛ ترکیبش هم نباید اعلام شود.
                    if (! CarModels::isIndexable($carSlug)) {
                        continue;
                    }

                    $urls[] = [
                        'loc' => seo_url('/part/' . rawurlencode($partSlug) . '/' . rawurlencode($carSlug)),
                        'priority' => '0.7',
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

    /**
     * robots.txt داینامیک تا دامنه و آدرس نقشه همیشه درست باشد.
     *
     * نکته‌ی حیاتی: robots.txt گروه‌ها را ادغام نمی‌کند. خزنده‌ای که نام
     * خودش گروه اختصاصی دارد، گروه «*» را کامل نادیده می‌گیرد. نسخه‌ی قبلی
     * برای هر خزنده‌ی هوش مصنوعی فقط «Allow: /» می‌نوشت؛ یعنی دقیقا همان
     * خزنده‌هایی که می‌خواستیم محتوا را بخوانند، اجازه‌ی /admin/ و /cart و
     * /order/ را هم می‌گرفتند. برای همین قواعد مسیر یک‌بار ساخته و در هر
     * گروه عینا تکرار می‌شوند.
     */
    public function robots()
    {
        $rules = [
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
            '# فایل‌های ظاهری صفحه باید خزیده شوند وگرنه رندر ناقص می‌ماند',
            'Allow: /assets/',
            'Allow: /upload/',
            'Allow: /images/',
            'Allow: *.css$',
            'Allow: *.js$',
            'Allow: *.webp$',
            'Allow: *.jpg$',
            'Allow: *.png$',
        ];

        $lines = array_merge(['User-agent: *', 'Allow: /'], $rules, [
            '',
            '# ---------------------------------------------------------------',
            '# خزنده‌های موتورهای پاسخ‌گو و مدل‌های زبانی',
            '# ---------------------------------------------------------------',
            '# هر سه دسته لازم‌اند و هرکدام یک مسیر جداگانه‌ی دیده‌شدن است:',
            '# ایندکسِ موتور پاسخ‌گو (OAI-SearchBot، PerplexityBot)، فچرِ لحظه‌ای',
            '# که وقتی کاربر لینک می‌دهد صفحه را می‌خواند (ChatGPT-User،',
            '# Claude-User)، و خزنده‌ی دانش پایه (GPTBot، CCBot) که نام برند را',
            '# در حافظه‌ی مدل تثبیت می‌کند. بستن هر دسته، همان مسیر را می‌بندد.',
        ]);

        foreach (self::AI_CRAWLERS as $userAgent) {
            $lines[] = '';
            $lines = array_merge($lines, ['User-agent: ' . $userAgent, 'Allow: /'], $rules);
        }

        $lines = array_merge($lines, [
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
            '# خلاصه‌ی مارک‌داونی سایت برای مدل‌های زبانی:',
            '# ' . seo_url('/llms.txt') . ' (فهرست) و ' . seo_url('/llms-full.txt') . ' (متن کامل)',
            'Sitemap: ' . seo_url('/sitemap.xml'),
        ]);

        return response(implode("\n", $lines), 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * llms.txt — خلاصه‌ی مارک‌داونیِ سایت برای مدل‌های زبانی.
     *
     * نقشه‌ی سایت فقط فهرست آدرس است و به مدل نمی‌گوید هر آدرس چیست. این
     * فایل همان نقش را با متنِ قابل‌فهم بازی می‌کند: چه می‌فروشیم، برای چه
     * خودروهایی، و پاسخ پرسش‌های تکراری کجاست. مثل بقیه‌ی خروجی‌ها کش
     * می‌شود چون شمارش قطعات هر خودرو کوئری سنگینی است.
     */
    public function llms()
    {
        $body = Cache::remember('llms:txt', now()->addMinutes($this->cacheMinutes()), function () {
            $lines = [
                '# ' . seo_site_name() . ' (' . seo_config('site_name_en', 'Nazer Yadak') . ')',
                '',
                '> ' . seo_config('default_description'),
                '',
                'زبان محتوا: فارسی (fa-IR). واحد قیمت: تومان. ارسال: سراسر ایران.',
                'تماس: ' . seo_config('business.phone') . ' — ' . seo_config('business.email'),
                '',
                '## صفحه‌های اصلی',
                '',
                '- [خانه](' . seo_url() . '): معرفی فروشگاه و دسته‌های پرفروش',
                '- [فروشگاه](' . seo_url('/shop') . '): فهرست کامل قطعات با فیلتر دسته و خودرو',
                '- [مجله](' . seo_url('/blog') . '): راهنمای خرید، تشخیص قطعه‌ی اصل و تعمیر',
                '- [سوالات متداول](' . seo_url('/faq') . '): پاسخ پرسش‌های رایج خرید و ارسال',
                '- [راهنمای سفارش](' . seo_url('/how-to-order') . '): مراحل ثبت سفارش',
                '- [شیوه‌های ارسال](' . seo_url('/shipping') . '): زمان و هزینه‌ی ارسال',
                '- [روش‌های پرداخت](' . seo_url('/payment-methods') . '): درگاه و پرداخت در محل',
                '- [پیگیری سفارش](' . seo_url('/order-tracking') . '): رهگیری با کد سفارش',
                '- [درباره‌ی ما](' . seo_url('/about-us') . '): سابقه و ضمانت اصالت کالا',
                '- [تماس با ما](' . seo_url('/contact-us') . '): آدرس، تلفن و ساعت کاری',
                '',
                '## دسته‌بندی قطعات',
                '',
            ];

            foreach (ProductCategory::cases() as $category) {
                $lines[] = '- [' . $category->label() . '](' . seo_url('/shop/' . rawurlencode($category->slug())) . ')';
            }

            $lines[] = '';
            $lines[] = '## قطعات بر اساس خودرو';
            $lines[] = '';

            foreach (CarModels::all() as $carSlug => $car) {
                // همان آستانه‌ی نقشه‌ی سایت: خودروی کم‌محصول صفحه‌ی نیمه‌خالی
                // می‌سازد و ارجاع دادن مدل به آن، پاسخ بی‌ارزش تولید می‌کند.
                if ($car['count'] < CarModels::INDEX_MIN_PRODUCTS) {
                    continue;
                }

                $lines[] = '- [لوازم یدکی ' . $car['name'] . '](' . seo_url('/car/' . rawurlencode($carSlug)) . '): ' . $car['count'] . ' قطعه';
            }

            $lines[] = '';
            $lines[] = '## Optional';
            $lines[] = '';
            $lines[] = '- [نقشه‌ی سایت](' . seo_url('/sitemap.xml') . '): فهرست کامل آدرس‌های قابل ایندکس';
            $lines[] = '';

            return implode("\n", $lines);
        });

        return response($body, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * llms-full.txt — متنِ کاملِ دانشِ سایت در یک فایل.
     *
     * llms.txt فقط فهرست آدرس‌هاست؛ مدل برای پاسخ‌دادن باید تک‌تکشان را
     * بخزد و اغلب نمی‌خزد. این فایل همان دانش را یک‌جا می‌دهد: سیاست ارسال و
     * بازگشت، پرسش‌های متداول، و برای هر دسته‌ی قطعه نشانه‌های خرابی و بازه‌ی
     * تعویض. یعنی وقتی کاربری از مدل می‌پرسد «تسمه تایم پژو ۲۰۶ کی عوض
     * می‌شود؟»، هم جوابِ درست و هم ارجاع به ناظر یدک در دسترسِ مدل است.
     *
     * محتوا از همان منابعی می‌آید که صفحه‌های HTML استفاده می‌کنند، وگرنه
     * مدل نسخه‌ای را نقل می‌کند که روی سایت وجود ندارد.
     */
    public function llmsFull()
    {
        $body = Cache::remember('llms:full', now()->addMinutes($this->cacheMinutes()), function () {
            $shipping = getShippingRules();
            $lines = [];

            $lines[] = '# ' . seo_site_name() . ' — ' . seo_config('site_name_en', 'Nazer Yadak');
            $lines[] = '';
            $lines[] = '> ' . seo_config('default_description');
            $lines[] = '';
            $lines[] = 'این فایل نسخه‌ی کاملِ متنیِ محتوای ' . seo_url() . ' است و برای استفاده‌ی مدل‌های زبانی منتشر می‌شود.';
            $lines[] = 'زبان محتوا فارسی (fa-IR)، واحد قیمت تومان، و محدوده‌ی فروش سراسر ایران است.';
            $lines[] = '';

            /* ---------------- هویت فروشگاه ---------------- */
            $lines[] = '## فروشگاه در یک نگاه';
            $lines[] = '';
            $lines[] = '- نام: ' . seo_site_name();
            $lines[] = '- حوزه‌ی فعالیت: فروش آنلاین لوازم یدکی و قطعات اصلی خودروهای ایرانی';
            $lines[] = '- تأمین‌کننده‌ها: ایساکو، سایپا یدک و سایر برندهای اصلی';
            $lines[] = '- تضمین: ضمانت اصالت کالا و هولوگرام اصالت روی بسته‌بندی';
            $lines[] = '- تلفن: ' . seo_config('business.phone');
            $lines[] = '- ایمیل: ' . seo_config('business.email');

            if ($address = seo_config('business.address')) {
                $city = seo_config('business.city');
                $lines[] = '- نشانی: ' . $address . ($city ? '، ' . $city : '');
            }

            foreach (array_filter((array) seo_config('social', [])) as $network => $url) {
                $lines[] = '- ' . $network . ': ' . $url;
            }

            $lines[] = '';

            /* ---------------- ارسال و پرداخت ---------------- */
            $lines[] = '## ارسال، پرداخت و بازگشت کالا';
            $lines[] = '';
            $lines[] = '- ارسال رایگان در ' . $shipping['local_province_name'] . ' برای سفارش‌های بالای ' . shippingAmountWords($shipping['local_free_threshold']) . '.';
            $lines[] = '- ارسال رایگان در سایر استان‌ها برای سفارش‌های بالای ' . shippingAmountWords($shipping['national_free_threshold']) . '.';
            $lines[] = '- زیر این مبلغ در ' . $shipping['local_province_name'] . ' هزینه‌ی پیک ' . toPersianNumbers($shipping['local_shipping_cost']) . ' تومان است؛ در سایر شهرها مرسوله با تیپاکس و پسکرایه از گیرنده ارسال می‌شود.';
            $lines[] = '- روش‌های پرداخت: درگاه بانکی آنلاین، پرداخت در محل، و کارت به کارت.';
            $lines[] = '- بازگشت کالا در صورت عیب فنی یا مغایرت با سفارش پذیرفته می‌شود.';
            $lines[] = '- جزئیات: ' . seo_url('/shipping') . ' و ' . seo_url('/payment-methods') . ' و ' . seo_url('/return-policy');
            $lines[] = '';

            /* ---------------- پرسش‌های متداول ---------------- */
            $lines[] = '## پرسش‌های متداول';
            $lines[] = '';

            foreach (seo_site_faqs() as $faq) {
                $lines[] = '### ' . $faq['q'];
                $lines[] = '';
                $lines[] = $faq['a'];
                $lines[] = '';
            }

            /* ---------------- دسته‌بندی قطعات ---------------- */
            $lines[] = '## دسته‌بندی قطعات';
            $lines[] = '';

            $categoryFacts = SeoContent::categoryFacts();

            foreach (ProductCategory::cases() as $category) {
                $facts = $categoryFacts[$category->slug()] ?? null;
                if ($facts === null) {
                    continue;
                }

                $lines[] = '### ' . $category->label();
                $lines[] = '';
                $lines[] = 'آدرس: ' . seo_url('/shop/' . rawurlencode($category->slug()));
                $lines[] = '';
                $lines[] = $facts['lead'];
                $lines[] = '';
                $lines[] = '**قطعات این گروه:** ' . implode('، ', $facts['parts']);
                $lines[] = '';
                $lines[] = '**نشانه‌های خرابی:**';
                $lines[] = '';

                foreach ($facts['symptoms'] as $symptom) {
                    $lines[] = '- ' . $symptom;
                }

                $lines[] = '';
                $lines[] = '**بازه‌ی تعویض:** ' . $facts['interval'];
                $lines[] = '';
                $lines[] = '**راهنمای خرید:**';
                $lines[] = '';

                foreach ($facts['tips'] as $tip) {
                    $lines[] = '- ' . $tip;
                }

                $lines[] = '';

                foreach ($facts['faq'] as $faq) {
                    $lines[] = '**' . $faq['q'] . '** ' . $faq['a'];
                    $lines[] = '';
                }
            }

            /* ---------------- خودروها ---------------- */
            $lines[] = '## قطعات بر اساس خودرو';
            $lines[] = '';
            $lines[] = 'برای هر خودرو صفحه‌ای با فهرست قطعات موجود و قیمت روز وجود دارد.';
            $lines[] = '';

            foreach (CarModels::all() as $carSlug => $car) {
                if ($car['count'] < CarModels::INDEX_MIN_PRODUCTS) {
                    continue;
                }

                $lines[] = '- لوازم یدکی ' . $car['name'] . ' — ' . $car['count'] . ' قطعه — ' . seo_url('/car/' . rawurlencode($carSlug));
            }

            $lines[] = '';

            /* ---------------- نوع قطعه ---------------- */
            $lines[] = '## صفحه‌های تخصصی هر قطعه';
            $lines[] = '';

            $catalog = PartTypes::catalog();

            foreach (PartTypes::counts() as $partSlug => $count) {
                if (! PartTypes::isIndexable($partSlug)) {
                    continue;
                }

                $name = $catalog[$partSlug]['name'] ?? $partSlug;
                $lines[] = '- ' . $name . ' — ' . $count . ' قطعه — ' . seo_url('/part/' . rawurlencode($partSlug));
            }

            $lines[] = '';

            /* ---------------- مقاله‌ها ---------------- */
            $articles = Article1::where('hidden', '0')
                ->where('deleted', '0')
                ->where('showdate', '<', date('Y-m-d H:i:s'))
                ->orderByDesc('showdate')
                ->limit(200)
                ->get(['articleid', 'titr', 'sutitr']);

            if ($articles->isNotEmpty()) {
                $lines[] = '## مقاله‌های راهنما';
                $lines[] = '';

                foreach ($articles as $article) {
                    $title = trim((string) $article->titr);
                    $summary = trim((string) $article->sutitr);

                    // ژنراتور مقاله‌ها تیتر را ابتدای زیرتیتر هم تکرار می‌کند؛
                    // بدون حذفش هر سطر تیتر را دوبار می‌گوید و متن برای مدل
                    // پرنویز می‌شود.
                    if ($title !== '' && str_starts_with($summary, $title)) {
                        $summary = trim(substr($summary, strlen($title)));
                    }

                    $lines[] = '- ' . $title
                        . ($summary !== '' ? ' — ' . seo_description($summary, 160) : '')
                        . ' — ' . seo_url($article->getUrl());
                }

                $lines[] = '';
            }

            /* ---------------- منابع ---------------- */
            $lines[] = '## منابع ماشین‌خوان';
            $lines[] = '';
            $lines[] = '- فهرست کوتاه: ' . seo_url('/llms.txt');
            $lines[] = '- نقشه‌ی سایت: ' . seo_url('/sitemap.xml');
            $lines[] = '- قواعد خزش: ' . seo_url('/robots.txt');
            $lines[] = '';
            $lines[] = 'آخرین بازتولید: ' . now()->toAtomString();
            $lines[] = '';

            return implode("\n", $lines);
        });

        return response($body, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
