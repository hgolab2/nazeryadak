<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Product;
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
    private const SPECIAL_OFFER_MIN_DISCOUNT = 3;

    /** پنجره‌ی «پربازدید» برای همان ریل: امروز و دیروز. */
    private const SPECIAL_OFFER_VIEW_DAYS = 2;

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
    public function home(Request $request)
	{
        $articles = Article1::orderBy('showdate', 'desc')
            ->where('hidden', '0')
            ->where('deleted', '0')
            ->where('showdate', '<', date('Y-m-d H:i:s'))
            ->take(4)
            ->get();
        $products = $this->getProduct(12);
        $specialProducts = $this->getSpecialOfferProducts(12);
        $specialHasDiscount = $specialProducts->isNotEmpty();
        if (!$specialHasDiscount) {
            // تا وقتی تخفیفی ثبت نشده، ریل «پیشنهاد ویژه» با منتخب قطعات پر می‌شود
            $specialProducts = Product::with(['images', 'categories'])
                ->where('is_active', 1)
                ->where('file_path', '!=', '')
                ->orderByDesc('id')
                ->skip(12)
                ->take(12)
                ->get();
        }
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
        $carCategories = \App\Models\EshopCategory::withCount('products')
            ->where('is_featured', 1)
            ->orderByDesc('products_count')
            ->take(10)
            ->get();
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
     * ۱) تخفیف بالای SPECIAL_OFFER_MIN_DISCOUNT درصد — تخفیف ۱ یا ۲ درصدی
     *    روی قطعه‌ی چند میلیونی چند هزار تومان است و «شگفت‌انگیز» نامیدنش
     *    اعتماد کاربر را می‌برد.
     *
     * ۲) عکس‌دار بودن — کارت بدون عکس در اسلایدر یک قاب خالی است و کل ریل را
     *    بی‌ریخت می‌کند؛ اینجا از همه‌جا مهم‌تر است چون بالای صفحه‌ی اصلی است.
     *
     * ۳) ترتیب بر اساس مجموع بازدیدِ SPECIAL_OFFER_VIEW_DAYS روز اخیر —
     *    یعنی همان قطعه‌هایی که این روزها دنبالشان هستند، نه قدیمی‌ترین
     *    تخفیف‌های ثبت‌شده. محصولی که در این بازه بازدید ندارد حذف نمی‌شود،
     *    فقط ته صف می‌رود (مجموعِ خالی در MySQL نال است و در ORDER BY DESC
     *    آخر می‌نشیند)؛ وگرنه ریل تا انباشته‌شدن آمار بازدید خالی می‌ماند.
     */
    public function getSpecialOfferProducts($count)
    {
        $since = today()->subDays(self::SPECIAL_OFFER_VIEW_DAYS - 1)->toDateString();

        return Product::with(['images', 'categories'])
            ->where('is_active', 1)
            ->where('discount_percent', '>', self::SPECIAL_OFFER_MIN_DISCOUNT)
            ->withImage()
            ->withSum(
                ['dailyViews as recent_views' => fn ($q) => $q->where('viewed_on', '>=', $since)],
                'hits'
            )
            ->orderByDesc('recent_views')
            // تساوی بازدید (مثلا وقتی همه صفر هستند) نباید ترتیب تصادفی بدهد؛
            // تخفیف بیشتر جلوتر، و بعد تازه‌ترین تغییر.
            ->orderByDesc('discount_percent')
            ->latest('updated_at')
            ->take($count)
            ->get();
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

