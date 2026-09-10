<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SpecialOfferFeature;
use App\Models\Order;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Advertisement;
use App\Models\Article1;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DOMDocument;
use DOMXPath;


class HomeController extends Controller
{
    /** کمینه‌ی درصد تخفیف برای راه‌یافتن به ریل «پیشنهاد ویژه» (اکید: بالاتر از این عدد). */
    private const SPECIAL_OFFER_MIN_DISCOUNT = 0;

    /** پنجره‌ی نخستِ «پربازدید» برای همان ریل: امروز و دیروز. */
    private const SPECIAL_OFFER_VIEW_DAYS = 2;

    /** پنجره‌ی جایگزین، وقتی دو روز اخیر ریل را پر نمی‌کند: یک هفته. */
    private const SPECIAL_OFFER_WIDE_VIEW_DAYS = 7;

    /**
     * سهم «تازه‌واردها» از ریل: نصف جاها.
     *
     * تمامِ ریل به تازه‌واردها نمی‌رسد چون آن‌وقت تخفیف‌دارِ واقعا پربازدید
     * با یک روز کم‌ترافیک از صفحه بیرون می‌افتاد؛ نصف، هم چرخش روزانه را
     * تضمین می‌کند و هم صدرِ ریل را دست‌نخورده نگه می‌دارد.
     */
    private const SPECIAL_OFFER_NEWCOMER_SHARE = 2;

    public function fetchPage($url)
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml',
                'Accept-Language: fa-IR,fa;q=0.9,en;q=0.8'
            ],
        ]);

        $html = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        }

        curl_close($ch);

        return $html;
    }
    /**
     * طول عمر کش بخش‌های مشترک صفحه‌ی اصلی، برحسب ثانیه.
     *
     * دو ریل صفحه‌ی اصلی روی جدول چندهزارتایی محصولات، با زیرکوئری
     * همبسته روی بازدیدها، مرتب می‌شوند. خروجیِ هر دو برای همه‌ی
     * بازدیدکننده‌ها یکسان است، ولی بدون کش برای هر بازدید از نو اجرا می‌شدند و
     * بیشترین سهم را در زمان پاسخ داشتند.
     *
     * پنج دقیقه آن‌قدر کوتاه هست که چرخش ریل‌ها را از بین نبرد.
     */
    private const HOME_RAILS_TTL = 300;

    public function home(Request $request)
	{
        $articles = Cache::remember('home:articles', self::HOME_RAILS_TTL, fn () => Article1::orderBy('showdate', 'desc')
            ->where('hidden', '0')
            ->where('deleted', '0')
            ->where('showdate', '<', date('Y-m-d H:i:s'))
            ->take(4)
            ->get());

        /*
        | ریل دوم بعد از ریل ویژه ساخته می‌شود و محصول‌های آن را کنار
        | می‌گذارد؛ وگرنه یک کارت دو بار در یک صفحه دیده می‌شد. قبلا این کار
        | با skip(12) روی ریل ویژه انجام می‌شد که فقط تا وقتی درست بود که هر
        | دو ریل با «جدیدترین» مرتب می‌شدند.
        */
        ['special' => $specialProducts, 'hasDiscount' => $specialHasDiscount, 'recent' => $products]
            = Cache::remember('home:rails', self::HOME_RAILS_TTL, function () {
                $special = $this->getSpecialOfferProducts(12);
                $hasDiscount = $special->isNotEmpty();

                if (! $hasDiscount) {
                    // تا وقتی تخفیفی ثبت نشده، ریل «پیشنهاد ویژه» با منتخب قطعات پر می‌شود
                    $special = Product::with(['images', 'categories'])
                        ->where('is_active', 1)
                        ->withImage()
                        ->orderByDesc('id')
                        ->take(12)
                        ->get();
                }

                return [
                    'special'     => $special,
                    'hasDiscount' => $hasDiscount,
                    'recent'      => $this->getRecentlyViewedProducts(12, $special->pluck('id')->all()),
                ];
            });
        /*
        | علاقه‌مندی‌های کاربر واردشده، بالای صفحه‌ی اصلی.
        |
        | قطعه‌ای که کاربر نشان کرده معمولا همان چیزی است که برای خریدش برگشته؛
        | تا حالا برای دیدنش باید از منو به «علاقه‌مندی‌ها» می‌رفت.
        */
        $favoriteProducts = collect();
        if ($customer = Auth::guard('customer')->user()) {
            $favoriteProducts = $customer->favoriteProducts()
                ->with(['images', 'categories'])
                ->where('products.is_active', 1)
                ->orderByDesc('product_favorites.created_at')
                ->take(12)
                ->get();
        }

        $advertisements = $this->getAdvertisement('farsi');
        $carCategories = Cache::remember('home:car-categories', self::HOME_RAILS_TTL, fn () => \App\Models\EshopCategory::withCount('products')
            ->where('is_featured', 1)
            ->orderByDesc('products_count')
            ->take(10)
            ->get());
        return View('index' , compact('articles','products','specialProducts','specialHasDiscount','favoriteProducts','advertisements','carCategories'));
	}

    public function getAdvertisement($lang)
    {
        if( app('request')->input('test') == 'ok'){
            Cache::forget("AdvertisementFirstPage-" .$lang);
        }
        return Cache::remember("AdvertisementFirstPage-".$lang , 10000, function() use ($lang){
            return Advertisement::where('position' , 5)->where('hidden' , 0)->where('site_id' , langid($lang))->where('startdate'  , '<=' , date('Y-m-d') . ' 00:00:00')->where('enddate'  , '>=' , date('Y-m-d') . ' 23:59:59')->orderBy('priority', 'desc')->orderBy('advertisementid', 'desc')->get();
        });

    }

    /**
     * ریل «پیشنهاد ویژه»: پربازدیدترین قطعه‌های تخفیف‌دار.
     *
     * سه شرط، هر سه لازم:
     *
     * ۱) تخفیف داشتن — هر درصدی، حتی ۱ درصد. ملاکْ ثبت‌شدن تخفیف است نه
     *    بزرگی‌اش؛ تصمیمِ اینکه چه تخفیفی ارزش نشستن در این ریل را دارد با
     *    ادمین است و در پنل گرفته می‌شود، نه با آستانه‌ای سفت در کد.
     *
     * ۲) عکس‌دار بودن — کارت بدون عکس در اسلایدر یک قاب خالی است و کل ریل را
     *    بی‌ریخت می‌کند؛ اینجا از همه‌جا مهم‌تر است چون بالای صفحه‌ی اصلی است.
     *
     * ۳) ترتیب بر اساس مجموع بازدیدِ روزهای اخیر — یعنی همان قطعه‌هایی که این
     *    روزها دنبالشان هستند، نه قدیمی‌ترین تخفیف‌های ثبت‌شده.
     *
     * ریل دو تکه است و همین تکه‌شدن است که هر روز عوضش می‌کند:
     *
     *   • نصف بالایی، تازه‌واردها — پربازدیدهای امروز که دیروز در ریل نبودند.
     *   • نصف پایینی، قاعده‌ی همیشگی — پربازدیدهای پنجره‌ی چندروزه.
     *
     * پنجره‌ی بازدیدِ تکه‌ی دوم کشسان است: اول دو روز اخیر، و اگر در آن بازه
     * کمتر از ظرفیتِ ریل قطعه‌ی بازدیدشده پیدا شود، همان کوئری روی یک هفته‌ی
     * اخیر تکرار می‌شود. بدون این عقب‌نشینی، ریل در روزهای کم‌ترافیک عملا فقط
     * با درصد تخفیف مرتب می‌شد.
     *
     * محصولِ بدون بازدید حتی در پنجره‌ی هفتگی هم حذف نمی‌شود، فقط ته صف
     * می‌رود (مجموعِ خالی نال است و در ORDER BY DESC آخر می‌نشیند)؛ وگرنه
     * ریل تا انباشته‌شدن آمار بازدید خالی می‌ماند.
     *
     * هیچ‌کدام از این کوئری‌ها کش نمی‌شوند: ریل باید هر بار وضعیت لحظه‌ای
     * جدول بازدید را نشان بدهد.
     */
    public function getSpecialOfferProducts($count)
    {
        $newcomers = $this->specialOfferNewcomers(
            (int) ceil($count / self::SPECIAL_OFFER_NEWCOMER_SHARE)
        );

        $rail = $newcomers->concat(
            $this->specialOfferRegulars($count - $newcomers->count(), $newcomers->pluck('id')->all())
        );

        // ترکیب امروز ثبت می‌شود تا فردا بشود پرسید «دیروز چه کسانی اینجا بودند».
        SpecialOfferFeature::remember($rail->pluck('id')->all());

        return $rail;
    }

    /**
     * تازه‌واردهای ریل: پربازدیدهای امروز که دیروز در ریل نبوده‌اند.
     *
     * این بخش همان چیزی است که ریل را می‌چرخاند. با قاعده‌ی قبلی، ترتیبْ
     * فقط از مجموع بازدید و درصد تخفیف می‌آمد و هر دو کند تغییر می‌کنند؛
     * نتیجه این بود که همان چند قطعه هفته‌ها بالای صفحه‌ی اصلی می‌ماندند و
     * کاربرِ برگشته دقیقا همان ریل دیروز را می‌دید.
     *
     * «پربازدید امروز» یعنی دست‌کم یک بازدیدِ واقعی در همین روز؛ آستانه‌ی
     * بالاتر روی سایتی با ترافیک متوسط عملا هیچ‌وقت برآورده نمی‌شد و ریل
     * باز هم بی‌حرکت می‌ماند.
     */
    private function specialOfferNewcomers(int $limit)
    {
        if ($limit < 1) {
            return collect();
        }

        $today = today()->toDateString();
        $featuredYesterday = SpecialOfferFeature::idsFeaturedOn(today()->subDay()->toDateString());

        return $this->specialOfferCandidates($today)
            ->with(['images', 'categories'])
            ->when($featuredYesterday !== [], fn ($q) => $q->whereNotIn('id', $featuredYesterday))
            ->whereHas('dailyViews', fn ($q) => $q
                ->where('viewed_on', '>=', $today)
                ->where('hits', '>', 0))
            ->orderByDesc('recent_views')
            ->orderByDesc('discount_percent')
            ->latest('updated_at')
            ->take($limit)
            ->get();
    }

    /**
     * باقی ریل، با همان قاعده‌ی همیشگی: پربازدیدترین تخفیف‌دارهای روزهای اخیر.
     *
     * @param  int[]  $exclude  شناسه‌ی تازه‌واردهایی که جایشان گرفته شده
     */
    private function specialOfferRegulars(int $count, array $exclude = [])
    {
        if ($count < 1) {
            return collect();
        }

        $days = self::SPECIAL_OFFER_VIEW_DAYS;

        if ($this->countViewedSpecialOffers($days) < $count) {
            $days = self::SPECIAL_OFFER_WIDE_VIEW_DAYS;
        }

        return $this->specialOfferCandidates($this->viewWindowStart($days))
            ->with(['images', 'categories'])
            ->when($exclude !== [], fn ($q) => $q->whereNotIn('id', $exclude))
            ->orderByDesc('recent_views')
            // تساوی بازدید (مثلا وقتی همه صفر هستند) نباید ترتیب تصادفی بدهد؛
            // تخفیف بیشتر جلوتر، و بعد تازه‌ترین تغییر.
            ->orderByDesc('discount_percent')
            ->latest('updated_at')
            ->take($count)
            ->get();
    }

    /**
     * ریل «داغ‌ترین قطعات»: هرچه تازه‌تر دیده شده، جلوتر.
     *
     * قبلا اینجا دوازده محصول آخرِ ثبت‌شده بود؛ ریلی که فقط با افزودن
     * محصول تازه عوض می‌شد و به رفتار امروزِ کاربرها هیچ ربطی نداشت. حالا
     * ملاک زمانِ آخرین بازدید است: قطعه‌ای که همین حالا دیده شد، در رفرش
     * بعدی صفحه‌ی اصلی بالای ریل است.
     *
     * محصولِ هرگز دیده‌نشده حذف نمی‌شود، فقط ته صف می‌رود (بیشینه‌ی خالی نال
     * است و در ORDER BY DESC آخر می‌نشیند) و بینشان جدیدترین‌ها جلوترند؛
     * پس ریل روی سایتِ تازه هم پر است.
     *
     * @param  int[]  $exclude  شناسه‌هایی که ریل بالاتر گرفته است
     */
    public function getRecentlyViewedProducts($count, array $exclude = [])
    {
        return Product::with(['images', 'categories'])
            ->where('is_active', 1)
            ->withImage()
            ->when($exclude !== [], fn ($q) => $q->whereNotIn('id', $exclude))
            ->withLastViewedAt()
            ->orderByDesc('last_viewed_at')
            ->orderByDesc('id')
            ->take($count)
            ->get();
    }

    /** نخستین روزِ پنجره‌ی بازدید؛ خودِ امروز یکی از $days روز است. */
    private function viewWindowStart(int $days): string
    {
        return today()->subDays($days - 1)->toDateString();
    }

    /**
     * چند قطعه‌ی واجد شرایط در این پنجره واقعا بازدید خورده‌اند؟
     *
     * ملاکِ کشسان‌شدن پنجره همین عدد است، نه تعداد کلِ قطعه‌های تخفیف‌دار:
     * تعداد کل به بازه‌ی زمانی ربطی ندارد و اگر ملاک می‌شد، پنجره‌ی دو روزه
     * هیچ‌وقت به کار نمی‌افتاد.
     */
    private function countViewedSpecialOffers(int $days): int
    {
        return $this->specialOfferCandidates()
            ->whereHas('dailyViews', fn ($q) => $q
                ->where('viewed_on', '>=', $this->viewWindowStart($days))
                ->where('hits', '>', 0))
            ->count();
    }

    /**
     * قطعه‌های واجد شرایطِ ریل، بدون ترتیب.
     *
     * با $since، مجموع بازدیدِ همان پنجره هم زیر نام recent_views می‌آید.
     */
    private function specialOfferCandidates(?string $since = null)
    {
        $query = Product::where('is_active', 1)
            ->where('discount_percent', '>', self::SPECIAL_OFFER_MIN_DISCOUNT)
            ->withImage();

        if ($since !== null) {
            $query->withSum(
                ['dailyViews as recent_views' => fn ($q) => $q->where('viewed_on', '>=', $since)],
                'hits'
            );
        }

        return $query;
    }

    public function getProduct($count)
    {
        return Product::with(['images', 'categories'])->orderBy('id' , 'desc')->where('is_active' , '1')->where('file_path' ,'!=', '')->paginate($count);
    }

    public function getArticle($categoryid, $count, $lang = 'farsi', $sort = 'showdate')
    {
        Config::set('app.locale' , $lang);
        $category = Category::find($categoryid);
        if(!$category)
        {
            return response()->view('errors.404', [], 404);
        }
        $class = 'App\Models\Article1';
        $article = new $class;
        $model = $article::orderBy($sort , 'desc')->where('hidden' , '0')->where('deleted' , '0');
        $model = $model->where('showdate' , '<' , date('Y-m-d H:i:s'));
        if($categoryid > 0)
        {
            $catlist[] = (int)$categoryid;
            $childs = Category::where('parent_id' , $categoryid)->where('deleted', '=', '0')->where('siteId', '=', 1)->get();
            foreach($childs as $child)
            {
                $catlist[] = $child->categoryid;
            }
            $model = $model->select(['article1.*'])->join('articleincategory', 'articleincategory.articleid', '=', 'article1.articleid')->where('articleincategory.siteid',  1)->whereIn('articleincategory.categoryid' , $catlist)->distinct();
        }
        // مثل getProduct: این خروجی فقط در اسلایدر صفحه‌ی اول استفاده می‌شود و
        // صفحه‌بندی ندارد، پس کوئری count(*) روی join لازم نیست.
        return $model->limit($count)->get();
    }
    public function view(Request $request)
	{
        return View('shop.view');
	}
    public function search(Request $request)
	{
        return View('shop.search');
	}
    public function aboutUs(Request $request)
	{
        return View('about-us');
	}
    public function contactUs(Request $request)
	{
        return View('contact-us');
	}
    public function faq(Request $request)
	{
        return View('pages.faq');
	}
    public function terms(Request $request)
	{
        return View('pages.terms');
	}
    public function privacy(Request $request)
	{
        return View('pages.privacy');
	}
    public function howToOrder(Request $request)
	{
        return View('pages.how-to-order');
	}
    public function shipping(Request $request)
	{
        return View('pages.shipping');
	}
    public function paymentMethods(Request $request)
	{
        return View('pages.payment-methods');
	}
    public function returnPolicy(Request $request)
	{
        return View('pages.return-policy');
	}
    public function blog(Request $request)
	{
        return View('blog');
	}
    public function dashboard(Request $request)
	{
        $user = Auth::guard('customer')->user();

        if (! $user) {
            return redirect('/login');
        }
        $favorites = $user->favoriteProducts()
            ->where('products.is_active', 1)
            ->orderByDesc('product_favorites.created_at')
            ->paginate(2);
        // کارت سفارش وضعیت رسید پرداخت را هم نشان می‌دهد
        $orders = Order::with(['items', 'pendingReceipt'])->where('customer_id', $user->id)->latest()->paginate(5);
        $customer = Customer::where('id', $user->id)->first();
        $address = CustomerAddress::where('customer_id', $user->id)->first();
        return View('dashboard', compact('favorites' , 'orders' , 'customer' , 'address'));
	}
}

