<?php
use  \Morilog\Jalali\CalendarUtils;
use  \Morilog\Jalali\Jalalian;
use App\Models\Sms;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\File;
use Illuminate\Support\Facades\Auth;

function getQuery($item){
    $query = str_replace(array('?'), array('\'%s\''), $item->toSql());
    return $query = vsprintf($query, $item->getBindings());
        //echo($query);
}
function sendSms($to_number, $text, $udh = "", $adv = "")
{
    if (strlen($to_number) != 11 || substr($to_number, 0, 2) != '09') return;
    if (trim($text) == '') return;

    date_default_timezone_set('Asia/Tehran');
    $text = str_replace('\n', "\n", $text);

    $input = [
        'text'   => $text,
        'type'   => 1,
        'mobile' => $to_number,
    ];

    // تنظیمات از config خوانده می‌شوند نه env(). با «php artisan config:cache»
    // روی سرور، env() بیرون از فایل‌های config همیشه null برمی‌گرداند و
    // ورود با پیامک بی‌سروصدا از کار می‌افتد.
    $config = config('sms');

    if (empty($config['username']) || empty($config['password'])) {
        \Illuminate\Support\Facades\Log::warning('SMS not sent: gateway is not configured', ['mobile' => $to_number]);
        return null;
    }

    // پارامترها با http_build_query انکد می‌شوند.
    // اینجا قبلا یک eval() روی رشته‌ی URL بود که متن پیامک را هم شامل می‌شد؛
    // با متن حاوی «$» یا «"» می‌شکست و در بدترین حالت اجرای کد دلخواه بود.
    $url = $config['url'] . '?' . http_build_query([
        'from'     => $config['number'],
        'to'       => $to_number,
        'username' => $config['username'],
        'password' => $config['password'],
        'message'  => $text,
    ]);

    try {
        $code_number = file_get_contents_curl($url);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('SMS send failed', ['mobile' => $to_number, 'message' => $e->getMessage()]);
        $code_number = null;
    }

    $user = Auth::guard('customer')->user();
    if ($user) {
        $input['user_id'] = $user->id;
    }
    $input['udh'] = $code_number;

    try {
        Sms::create($input);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('SMS log insert failed', ['message' => $e->getMessage()]);
    }

    return $code_number;
}
function file_get_contents_curl($url) {
    $ch = curl_init ();
    curl_setopt ( $ch, CURLOPT_HEADER, 0 );
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 ); //Set curl to return the data instead of printing it to the browser.
    curl_setopt ( $ch, CURLOPT_URL, $url );
    curl_setopt ( $ch, CURLOPT_TIMEOUT, 10 );
    $data = curl_exec ( $ch );
    curl_close ( $ch );
    return $data;
}
function getShippingSettings()
{
    if (isset($GLOBALS['__shipping_settings_cache'])) {
        return $GLOBALS['__shipping_settings_cache'];
    }

    try {
        $settings = \DB::table('shipping_settings')->pluck('setting_value', 'setting_key')->toArray();
    } catch (\Throwable $e) {
        // اگر جدول هنوز ساخته نشده باشد، مقادیر پیش‌فرض getShippingRules() به کار می‌رود.
        $settings = [];
    }

    return $GLOBALS['__shipping_settings_cache'] = $settings;
}

/**
 * پاک‌کردن کش درون‌درخواستیِ تنظیمات ارسال؛ بعد از ذخیره‌ی فرم مدیریت لازم است
 * تا همان درخواست مقادیر تازه را ببیند.
 */
function forgetShippingSettings()
{
    unset($GLOBALS['__shipping_settings_cache'], $GLOBALS['__shipping_rules_cache']);
}

/**
 * قواعد ارسال بر حسب «تومان».
 *
 * مقادیر جدول shipping_settings به ریال ذخیره شده‌اند (توضیح هر ردیف هم همین
 * را می‌گوید) ولی قیمت محصولات و مبلغ سفارش در کل سایت تومان است. بدون این
 * تبدیل، آستانه‌ی ارسال رایگان ده برابر واقعی و هزینه‌ی پیک قم به جای
 * ۵۰٬۰۰۰ تومان، ۵۰۰٬۰۰۰ تومان حساب می‌شد.
 */
function getShippingRules()
{
    if (isset($GLOBALS['__shipping_rules_cache'])) {
        return $GLOBALS['__shipping_rules_cache'];
    }

    $settings = getShippingSettings();

    $toToman = function ($key, $default) use ($settings) {
        $rial = (int) ($settings[$key] ?? $default);
        return (int) round($rial / 10);
    };

    $localProvinceId = (int) ($settings['local_province_id'] ?? 19);

    return $GLOBALS['__shipping_rules_cache'] = [
        'local_province_id'       => $localProvinceId,
        'local_province_name'     => getLocalProvinceName($localProvinceId),
        'local_free_threshold'    => $toToman('local_free_threshold', 50000000),
        'local_shipping_cost'     => $toToman('local_shipping_cost', 500000),
        'national_free_threshold' => $toToman('national_free_threshold', 200000000),
    ];
}

/**
 * آیا پرداخت آنلاین (درگاه زرین‌پال) فعال است؟
 *
 * از همان جدول shipping_settings خوانده می‌شود تا مدیر بتواند هر وقت خواست
 * از پنل تنظیمات، پرداخت اینترنتی کل سایت را روشن/خاموش کند. وقتی خاموش
 * است، سفارش ثبت می‌شود و کاربر یک «پیش‌فاکتور» می‌بیند و کارشناسان برای
 * هماهنگی پرداخت با او تماس می‌گیرند. پیش‌فرض: خاموش.
 */
function onlinePaymentEnabled(): bool
{
    $settings = getShippingSettings();

    return (string) ($settings['online_payment_enabled'] ?? '0') === '1';
}

/** متنی که به‌جای قیمتِ قطعات استعلامی (شاسی و بدنه) نشان داده می‌شود. */
function contactPriceLabel(): string
{
    return 'لطفا تماس بگیرید';
}

/** شماره‌ی تماس فروشگاه برای دکمه‌های «تماس بگیرید» (tel:). */
function shopContactPhone(): string
{
    return (string) seo_config('business.phone', '+989127471631');
}

/** همان شماره برای نمایش، با ارقام فارسی. */
function shopContactPhoneDisplay(): string
{
    return (string) seo_config('business.phone_display', '۰۹۱۲۷۴۷۱۶۳۱');
}

/**
 * نام استان محلی از جدول استان‌ها؛ اگر پیدا نشد «قم» به عنوان پیش‌فرض.
 */
function getLocalProvinceName($provinceId)
{
    try {
        $name = \App\Models\Province::where('id', $provinceId)->value('name');
    } catch (\Throwable $e) {
        $name = null;
    }

    return $name ?: 'قم';
}

/**
 * نمایش فشرده‌ی مبلغ برای جاهایی مثل نوار مزایای فوتر: ۵ میلیون → «۵M».
 * ورودی به تومان.
 */
function shippingAmountShort($toman)
{
    $toman = (int) $toman;

    if ($toman >= 1000000) {
        $millions = $toman / 1000000;
        $value    = fmod($millions, 1) == 0 ? (string) (int) $millions : number_format($millions, 1);
        return toPersianNumbers($value, false) . 'M';
    }

    if ($toman >= 1000) {
        return toPersianNumbers((string) (int) round($toman / 1000), false) . 'K';
    }

    return toPersianNumbers($toman);
}

/**
 * نمایش خوانا‌ی مبلغ برای متن‌های توضیحی: «۵ میلیون تومان».
 * ورودی به تومان.
 */
function shippingAmountWords($toman)
{
    $toman = (int) $toman;

    if ($toman >= 1000000 && $toman % 100000 === 0) {
        $millions = $toman / 1000000;
        $value    = fmod($millions, 1) == 0 ? (string) (int) $millions : number_format($millions, 1);
        return toPersianNumbers($value, false) . ' میلیون تومان';
    }

    return toPersianNumbers($toman) . ' تومان';
}

function postCalculation($orderid)
{
    $order = Order::where('id', $orderid)->first();
    $info = getShippingInfo($order);
    return $info['cost'];
}

function getShippingInfo($order)
{
    $rules = getShippingRules();
    $localProvinceId      = $rules['local_province_id'];
    $localProvinceName    = $rules['local_province_name'];
    $localFreeThreshold   = $rules['local_free_threshold'];
    $localShippingCost    = $rules['local_shipping_cost'];
    $nationalFreeThreshold = $rules['national_free_threshold'];

    $address = null;
    if ($order && $order->address_id) {
        $address = \App\Models\CustomerAddress::find($order->address_id);
    }
    if (!$address && $order && $order->customer_id) {
        $address = \App\Models\CustomerAddress::where('customer_id', $order->customer_id)
            ->where('is_default', 1)->first();
        if (!$address) {
            $address = \App\Models\CustomerAddress::where('customer_id', $order->customer_id)->first();
        }
    }

    $orderTotal = 0;
    if ($order) {
        foreach ($order->items as $item) {
            $orderTotal += $item->unit_price * $item->quantity;
        }
    }

    if (!$address) {
        return ['cost' => 0, 'type' => 'unknown', 'label' => 'پس از ثبت آدرس محاسبه می‌شود'];
    }

    $isLocal = ((int) $address->province_id) === $localProvinceId;

    if ($isLocal) {
        if ($orderTotal >= $localFreeThreshold) {
            return ['cost' => 0, 'type' => 'free', 'label' => 'ارسال رایگان (' . $localProvinceName . ')'];
        }
        return ['cost' => $localShippingCost, 'type' => 'local', 'label' => number_format($localShippingCost) . ' تومان (پیک در ' . $localProvinceName . ')'];
    }

    if ($orderTotal >= $nationalFreeThreshold) {
        return ['cost' => 0, 'type' => 'free', 'label' => 'ارسال رایگان'];
    }

    return ['cost' => 0, 'type' => 'tipax', 'label' => 'تیپاکس (پسکرایه از گیرنده)'];
}

function getFileGroup($id)
{
    $_ = array('1'=>'imgArticle' , '2'=>'advertisment' , '3'=>'sound' , '4'=>'gallery' , '5'=>'occasion' , '6'=>'product', '7'=>'gallery' , '8'=>'sound', '9'=>'category', '10'=>'lecture', '11'=>'shop');
    return $_[$id];
}

function langid($langname)
{
    // آرایه زبان‌ها و شناسه‌های آن‌ها
    $languages = [
        1 => 'farsi',
        2 => 'arabic',
        3 => 'english',
        4 => 'urdu',
        5 => 'deutsch',
        6 => 'azari',
        7 => 'french',
        8 => 'indonesian',
        9 => 'bengali',
        10 => 'hindi',
        11 => 'ru',
        12 => 'spa',
        13 => 'turkish',
        14 => 'thailand',
        20 => 'hamaseh',
        19 => 'emamsajad'
    ];

    // جستجوی زبان در آرایه
    $langid = array_search($langname, $languages);

    // بازگرداندن شناسه یا مقدار پیش‌فرض
    return $langid !== false ? $langid : 0;
}

function langname($langid)
{
    if($langid > 0)
    {
        $langname = array( 1=>'farsi',
                    13=>'turkish',
                    12=>'spa',
                    11=>'ru',
                    20=>'hamaseh',
                    10=>'hindi',
                    9=>'bengali',
                    8=>'indonesian',
                    7=>'french',
                    6=>'azari',
                    5=>'deutsch',
                    4=>'urdu',
                    3=>'english',
                    2=>'arabic',
                    14=>'thailand',
                    20 => 'hamaseh',
                    19 => 'emamsajad'
        );
        if(array_key_exists($langid , $langname))
        {
            return $langname[$langid];
        }
    }
}
function toPersianDate($date, $ago = false, $dateVisibility = true, $format = null)
{
    $format = $format ?? 'H:i Y/m/d';
    $newDate = CalendarUtils::strftime($format, strtotime($date));
    if ($ago) {
        $ago = Jalalian::forge($date)->ago();
        $newDate = $dateVisibility ? "$newDate ($ago)" : "$ago";
    }
    return toPersianNumbers($newDate, false);
}
function toPersianNumbers($string, $format_number = true)
{
    // اگر مقدار null بود
    if ($string === null) {
        return null;
    }

    // اگر رشته عددی نیست، فرمت عددی اعمال نشود
    if ($format_number && is_numeric($string)) {
        $string = number_format($string);
    }

    // اگر کشور غیر از امارات است، تبدیل انجام شود
    $farsi_array   = ["۰", "۱", "۲", "۳", "۴", "۵", "۶", "۷", "۸", "۹"];
    $english_array = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
    $persian_number = str_replace($english_array, $farsi_array, (string)$string);

    return $persian_number;
}
function gregorian_to_jalali2($date, $show_hour = false, $show_weekday = false)
{
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return '';
    }

    $months = [
        1 => "فروردين", "ارديبهشت", "خرداد", "تير", "مرداد", "شهريور",
        "مهر", "آبان", "آذر", "دى", "بهمن", "اسفند"
    ];

    $weekdays = ["یکشنبه", "دوشنبه", "سه‌شنبه", "چهارشنبه", "پنج‌شنبه", "جمعه", "شنبه"];

    $mydate0 = substr($date , 0 , 10);
    $mydate = gregorianjalali($mydate0);
    $mydate2 = explode('-', $mydate);
    $mydate3 = explode(':', substr($date , 11));

    $flag = 0;
    if($show_hour){
        if ($mydate3[0] > 12) {
            $mydate3[0] = $mydate3[0] - 12;
            $flag = 1;
        }
    }

    $return = '';

    // اگر خواستیم روز هفته را هم اضافه کنیم
    if($show_weekday){
        $weekday_index = date('w', strtotime($date)); // 0=یکشنبه، 6=شنبه
        $return .= $weekdays[$weekday_index] . ' ';
    }

    $return .= ((int)$mydate2[2]) . ' ';
    $return .= $months[(int)$mydate2[1]] . ' ';
    $return .= ((int)$mydate2[0]);

    if($show_hour){
        $return .= ' ساعت ';
        $return .= $mydate3[0] . ':' . $mydate3[1] . ' ';
        $return .= ($flag ? 'بعد از ظهر' : 'صبح');
    }

    return $return;
}
function div($a, $b)
{
    return (int)($a / $b);
}
function gregorianjalali($date, $inSplit = '-', $outSplit = '-')
{
    list($g_y, $g_m, $g_d) = explode($inSplit, $date);
    $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
    $gy = $g_y - 1600;
    $gm = $g_m - 1;
    $gd = $g_d - 1;
    $g_day_no = 365 * $gy + div($gy + 3, 4) - div($gy + 99, 100) + div($gy + 399, 400);
    for ($i = 0; $i < $gm; ++$i)
        $g_day_no += $g_days_in_month[$i];
    if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)))
        /* leap and after Feb */
        ++$g_day_no;
    $g_day_no += $gd;
    $j_day_no = $g_day_no - 79;
    $j_np = div($j_day_no, 12053);
    $j_day_no %= 12053;
    $jy = 979 + 33 * $j_np + 4 * div($j_day_no, 1461);
    $j_day_no %= 1461;
    if ($j_day_no >= 366) {
        $jy += div($j_day_no - 1, 365);
        $j_day_no = ($j_day_no - 1) % 365;
    }
    for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i) {
        $j_day_no -= $j_days_in_month[$i];
    }
    $jm = $i + 1;
    $jd = $j_day_no + 1;
    if ($outSplit == '')
        return array($jy, $jm, $jd);
    else
        return sprintf('%04s%s%02s%s%02s', $jy, $outSplit, $jm, $outSplit, $jd);
}
function is_mobile()
{
    if (isset($_REQUEST['ismobile'])) {
        return $_REQUEST['ismobile'];
    }

    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
        return false; // یا مدیریت مناسب خطا
    }

    $useragent = $_SERVER['HTTP_USER_AGENT'];
    if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) ||
        preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
        return true;
    }

    return false;
}
function access($moduleid, $redirect = true){
    $user = Auth::user();
    if($user)
    {
        return true;
        if($user->role_id == 7){
            return true;
        }
        $Flag = DB::table('role_module')->where('role_id', '=', $user->role_id)->where('module_id', '=', $moduleid)->count();
        if(!$Flag){
            if($redirect)
            {
                redirect('/login');
            }
            return false;
        }
        return true;
    }
    else
    {
        return false;;
    }
}
function l($word)
{
    if (Config::get('app.locale') != 'farsi' && false) {
        $keyword = str_replace(' ', '-', $word);
        $path = base_path('resources/lang/' . Config::get('app.locale') . '/message.php');

        if (!file_exists($path)) {
            file_put_contents($path, "<?php\nreturn [];");
        }

        $_ = include($path);

        if (!isset($_[$keyword])) {
            $w = Config::get('site_settings');

            if (!is_array($w) || !in_array($word, $w)) {
                $w[] = $word;
                Config::set('site_settings', $w);
            }

            $content = "<?php\nreturn [\n";
            if(is_array($_))
            {
                foreach ($_ as $k => $v) {
                    $content .= "'$k' => '$v',\n";
                }
            }
            foreach ($w as $word) {
                $keyword = str_replace(' ', '-', $word);
                $content .= "'$keyword' => '$word',\n";
            }
            $content .= "];";

            try {
                file_put_contents($path, $content);
            } catch (\Exception $e) {
                \Log::error('Failed to write to file: ' . $e->getMessage());
            }
        }

        return __('message.' . $keyword);
    } else {
        return $word;
    }
}
function jalaligregorian($date, $inSplit = '-', $outSplit = '-')
{
    list($j_y, $j_m, $j_d) = explode($inSplit, $date);
    $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
    $jy = $j_y - 979;
    $jm = $j_m - 1;
    $jd = $j_d - 1;
    $j_day_no = 365 * $jy + div($jy, 33) * 8 + div($jy % 33 + 3, 4);
    for ($i = 0; $i < $jm; ++$i)
        $j_day_no += $j_days_in_month[$i];
    $j_day_no += $jd;
    $g_day_no = $j_day_no + 79;
    $gy = 1600 + 400 * div($g_day_no, 146097); /* 146097 = 365*400 + 400/4 - 400/100 + 400/400 */
    $g_day_no = $g_day_no % 146097;
    $leap = true;
    if ($g_day_no >= 36525) /* 36525 = 365*100 + 100/4 */ {
        $g_day_no--;
        $gy += 100 * div($g_day_no, 36524); /* 36524 = 365*100 + 100/4 - 100/100 */
        $g_day_no = $g_day_no % 36524;
        if ($g_day_no >= 365)
            $g_day_no++;
        else
            $leap = false;
    }
    $gy += 4 * div($g_day_no, 1461); /* 1461 = 365*4 + 4/4 */
    $g_day_no %= 1461;
    if ($g_day_no >= 366) {
        $leap = false;
        $g_day_no--;
        $gy += div($g_day_no, 365);
        $g_day_no = $g_day_no % 365;
    }
    for ($i = 0; $g_day_no >= $g_days_in_month[$i] + ($i == 1 && $leap); $i++)
        $g_day_no -= $g_days_in_month[$i] + ($i == 1 && $leap);
    $gm = $i + 1;
    $gd = $g_day_no + 1;
    if ($outSplit == '')
        return array($gy, $gm, $gd);
    else
        return sprintf('%04s%s%02s%s%02s', $gy, $outSplit, $gm, $outSplit, $gd);
}
function checkInputs($inputs){

    $english = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    $persian = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    foreach($inputs as $key=>$val){
        if($val != null && !is_array($val))
        $inputs[$key] = str_replace($persian , $english, $val);
    }
    return $inputs;
}
function uploader($file,  $param = array())
{
    $superAdmin = Auth::user();
    if(!isset($superAdmin))
    {
        return redirect('/login');
    }
    if (/*$request->hasFile($field) && $request->file($field)->isValid()*/1) {
        if(array_key_exists('dropzone' , $param) && $param['dropzone'])
        {
            $file = $file->file('file');
        }
        $filename = $file->getClientOriginalName();
        /*if($param['delItem'])
        {
            delete($param['delItem']);
        }*/

        if(strpos(strtolower("a".$filename) , "php") > 0)
        {
            unlink($file["path"] . $file["file_name"]);
            exit;
        }
        $nameArray = explode('.',strtolower($filename));

        //$file = $f;
        $data = array();
        $data['description'] = $param['description'];
        $data['filetype'] = $file->getClientOriginalExtension();
        $data['savedate'] = date('Y-m-d H:i:s');
        $data['savedby'] = $superAdmin->getAuthIdentifier();
        $data['filesize'] = $file->getSize();
        $data['filepath'] = $filename;
        $data['grouptype'] = $param['grouptype'];
        $data['width'] = 0;
        $data['height'] = 0;
        $filecreeate = File::create(checkInputs($data));

        $_files_groups = getFileGroup($param['grouptype']);
        $_files_groups = $_files_groups.'/upload';

        if(!is_dir(public_path('/' . $_files_groups . '/' . substr($data['savedate'], 0, 4))))
        {
            mkdir(public_path('/' . $_files_groups . '/' . substr($data['savedate'], 0, 4)));
        }
        if(!is_dir(public_path('/' . $_files_groups . '/' . substr($data['savedate'], 0, 4) . '/' . substr($data['savedate'], 5, 2))))
        {
            mkdir(public_path('/' . $_files_groups . '/' . substr($data['savedate'], 0, 4) . '/' . substr($data['savedate'], 5, 2)));
        }
        $path = public_path('/' . $_files_groups . '/' . substr($data['savedate'], 0, 4) . '/' . substr($data['savedate'], 5, 2) . '/' . $filecreeate->fileId . '_' . $filename);
        $file->move(public_path('/' . $_files_groups . '/' . substr($data['savedate'], 0, 4) . '/' . substr($data['savedate'], 5, 2)), $filecreeate->fileId . '_' . $filename);


        if ($data['filetype'] == 'image/gif' || $data['filetype'] == 'image/jpeg' || $data['filetype'] == 'image/png') {
            if ($param['width']>0 && $param['height']>0 && $data['filetype'] == 'image/jpeg') {
                resizeMainImage($path, $param['width'], $param['height']);
            }
            $imagesize = getimagesize($path);
            $data3 = array();
            $data3['width'] = $imagesize[0];
            $data3['height'] = $imagesize[1];
            File::where('fileId', $filecreeate->fileId)->update($data3);
        }

        return $filecreeate->fileId;
    }
}
function resizeMainImage($BaseFilename, $_w, $_h, $cache = FALSE, $cache_key = '')
{
    if (is_file($BaseFilename)) {
        $cache_file = '';
        if ($cache) {
            $file = md5($BaseFilename . $cache_key) . '.' . pathinfo($BaseFilename)['extension'];
            $cache_file = public_path('/cache/imgs/' . $file);

            if (is_file($cache_file)) {
                return env('DOMAIN').'/cache/imgs/' . pathinfo($cache_file)['basename'];
            }
        }
        $filename = $BaseFilename;
        $filenameEnd = $cache ? $cache_file : $BaseFilename;
        //header('Content-type: image/jpeg');
        list($width, $height) = getimagesize($filename);
        if ($width > $_w or $height > $_h) {
            $newwidth = $width;
            $newheight = $height;
            $width1 = $width;
            $height1 = $height;
            if ($newwidth > $_w) {
                $newwidth = $_w;
                $newheight = ($_w * $height1) / $width1;
                $width1 = $newwidth;
                $height1 = $newheight;
            }
            if ($newheight > $_h) {
                $newheight = $_h;
                $newwidth = ($_h * $width1) / $height1;
            }
        } else {
            $newwidth = $width;
            $newheight = $height;
        }
        $thumb = imagecreatetruecolor($newwidth, $newheight);
        $format = '';
        if (preg_match("/.jpg/i", "$filename")) {
            $format = 'image/jpeg';
            $source = @imagecreatefromjpeg($filename);
        }
        if (preg_match("/.jpeg/i", "$filename")) {
            $format = 'image/jpeg';
            $source = imagecreatefromjpeg($filename);
        }
        if (preg_match("/.gif/i", "$filename")) {
            $format = 'image/gif';
            $source = imagecreatefromgif($filename);
        }
        if (preg_match("/.png/i", "$filename")) {
            $format = 'image/png';
            $source = imagecreatefrompng($filename);
        }
        if (!@imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height)) {
            "$BaseFilename<br>";
            return false;
        }
        switch ($format) {
            case 'image/jpeg':
            {
                imagejpeg($thumb, $filenameEnd,100);
            }
                break;
            case 'image/gif';
            {
                imagegif($thumb, $filenameEnd);
            }
                break;
            case 'image/png':
            {
                imagepng($thumb, $filenameEnd);
            }
                break;
        }
        if ($cache) return env('DOMAIN') . '/cache/imgs/' . pathinfo($filenameEnd)['basename'];
    }
}

/*
|--------------------------------------------------------------------------
| توابع سئو
|--------------------------------------------------------------------------
| همه‌ی خروجی‌های متا، Schema.org و آدرس‌های کانونیکال از این توابع می‌آیند.
| مقادیر پایه در config/seo.php نگهداری می‌شوند.
*/

function seo_config(string $key, $default = null)
{
    return config('seo.' . $key, $default);
}

function seo_site_name(): string
{
    return (string) seo_config('site_name', 'ناظر یدک');
}

function seo_base_url(): string
{
    $base = seo_config('base_url') ?: (config('app.url') ?: url('/'));

    return rtrim($base, '/');
}

/**
 * آدرس مطلق و نرمال‌شده.
 *
 * خروجی همیشه percent-encode می‌شود تا آدرسی که در canonical، og:url،
 * Schema و نقشه‌ی سایت می‌آید دقیقا یکسان باشد؛ اگر یکی خام و دیگری
 * انکدشده باشد، گوگل آن‌ها را دو صفحه‌ی جدا حساب می‌کند.
 */
function seo_url(string $path = ''): string
{
    if ($path === '' || $path === '/') {
        return seo_base_url() . '/';
    }

    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return seo_xml_url($path);
    }

    return seo_xml_url(seo_base_url() . '/' . ltrim($path, '/'));
}

/**
 * آدرس مطلقِ یک تصویر. مسیرهای نسبی به دامنه‌ی اصلی چسبانده می‌شوند تا
 * og:image و Schema هرگز آدرس نسبی نگیرند (گوگل آن را نادیده می‌گیرد).
 */
function seo_image_url(?string $path): string
{
    $path = trim((string) $path);

    if ($path === '') {
        return seo_url(seo_config('default_image', '/assets/images/logo.png'));
    }

    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    if (str_starts_with($path, '//')) {
        return 'https:' . $path;
    }

    return seo_url($path);
}

/**
 * آدرس آماده برای <loc> در نقشه‌ی سایت.
 *
 * اسلاگ‌های فارسی باید percent-encode شوند؛ استاندارد Sitemap فقط آدرس
 * escape‌شده را معتبر می‌داند و اعتبارسنج‌ها روی آدرس خام خطا می‌دهند.
 */
function seo_xml_url(string $url): string
{
    $parts = parse_url($url);
    if ($parts === false || empty($parts['host'])) {
        return $url;
    }

    $encoded = ($parts['scheme'] ?? 'https') . '://' . $parts['host'];
    if (! empty($parts['port'])) {
        $encoded .= ':' . $parts['port'];
    }

    if (! empty($parts['path'])) {
        $segments = array_map(
            fn($segment) => rawurlencode(rawurldecode($segment)),
            explode('/', $parts['path'])
        );
        $encoded .= implode('/', $segments);
    }

    if (! empty($parts['query'])) {
        // «=» و «&» ساختار کوئری‌اند و نباید انکد شوند.
        parse_str($parts['query'], $query);
        $encoded .= '?' . http_build_query($query);
    }

    // فرگمنت باید حفظ شود؛ شناسه‌های Schema (مثل #organization) روی همین
    // بخش بنا شده‌اند و حذف آن، همه‌ی @id ها را به یک آدرس یکسان تبدیل
    // می‌کند و گراف داده‌ی ساختاریافته را خراب می‌کند.
    if (isset($parts['fragment']) && $parts['fragment'] !== '') {
        $encoded .= '#' . $parts['fragment'];
    }

    return $encoded;
}

/**
 * مسیر نسخه‌ی WebP یک تصویر، در صورت وجود.
 *
 * دستور «php artisan images:webp» کنار هر jpg/png یک فایل .webp می‌سازد.
 * اینجا فقط بررسی می‌کنیم آن فایل روی دیسک هست یا نه؛ اگر نبود null
 * برمی‌گردد و قالب به تصویر اصلی بسنده می‌کند.
 */
function webp_variant(?string $path): ?string
{
    $path = trim((string) $path);

    if ($path === '' || ! preg_match('/\.(jpe?g|png)$/i', $path)) {
        return null;
    }

    // تصاویر روی دامنه‌های بیرونی را نمی‌توانیم بررسی کنیم.
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return null;
    }

    $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
    $file = public_path(ltrim(rawurldecode(parse_url($webpPath, PHP_URL_PATH) ?: $webpPath), '/'));

    // نتیجه در حافظه‌ی همین درخواست کش می‌شود تا صفحه‌ی فهرست با ۲۴ محصول
    // ده‌ها بار is_file() صدا نزند.
    static $cache = [];
    if (! array_key_exists($file, $cache)) {
        $cache[$file] = is_file($file);
    }

    return $cache[$file] ? $webpPath : null;
}

function seo_description(string $text, int $limit = 158): string
{
    $text = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ' ', $text));
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = trim(preg_replace('/\s+/u', ' ', $text));

    if (mb_strlen($text) <= $limit) {
        return $text;
    }

    // برش روی مرز کلمه تا توضیحات وسط واژه قطع نشود.
    $cut = mb_substr($text, 0, $limit - 1);
    $lastSpace = mb_strrpos($cut, ' ');
    if ($lastSpace !== false && $lastSpace > $limit * 0.6) {
        $cut = mb_substr($cut, 0, $lastSpace);
    }

    return rtrim($cut, ' ،,.-') . '…';
}

/** عنوان استاندارد: حداکثر ۶۰ کاراکتر مفید + نام برند. */
function seo_title(string $text, bool $withBrand = true): string
{
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)));
    $brand = seo_site_name();

    if ($withBrand && ! str_contains($text, $brand)) {
        $max = 60 - mb_strlen($brand) - 3;
        if (mb_strlen($text) > $max) {
            $text = rtrim(mb_substr($text, 0, $max)) . '…';
        }
        return $text . ' | ' . $brand;
    }

    return $text;
}

function seo_json_ld(array $data): string
{
    return json_encode(
        seo_array_clean($data),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
    );
}

/** حذف کلیدهای خالی تا Schema بدون فیلد null/'' تحویل گوگل شود. */
function seo_array_clean(array $data): array
{
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $value = seo_array_clean($value);
            $data[$key] = $value;
        }
        if ($value === null || $value === '' || $value === []) {
            unset($data[$key]);
        }
    }

    return $data;
}

function seo_default_keywords(): string
{
    return (string) seo_config('default_keywords', '');
}

function seo_slug(?string $text, string $fallback = 'item'): string
{
    $text = trim((string) $text);
    $text = strtr($text, [
        'ي' => 'ی', 'ك' => 'ک', 'ة' => 'ه', 'ۀ' => 'ه', 'ؤ' => 'و', 'إ' => 'ا', 'أ' => 'ا', 'آ' => 'آ',
        "\u{200C}" => '-', "\u{200F}" => '', "\u{200E}" => '',
    ]);
    $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
    $text = trim(preg_replace('/-+/u', '-', $text), '-');

    return $text !== '' ? mb_strtolower($text) : $fallback;
}

/**
 * آدرس کانونیکالِ صفحه‌ی جاری. فقط پارامترهای مجاز (صفحه‌بندی، دسته و مدل
 * خودرو) نگه داشته می‌شوند؛ بقیه‌ی فیلترها و پارامترهای ردیابی حذف می‌شوند
 * تا صفحات فیلترشده محتوای تکراری نسازند.
 */
function seo_canonical(?string $path = null, array $extraAllowed = []): string
{
    $request = request();
    $path = $path ?? '/' . ltrim($request?->path() ?? '', '/');

    if ($path === '/' || $path === '') {
        $path = '/';
    }

    $allowed = array_merge((array) seo_config('canonical_query_whitelist', []), $extraAllowed);
    $query = [];

    foreach ($allowed as $param) {
        $value = $request?->query($param);
        if ($value === null || $value === '' || is_array($value)) {
            continue;
        }
        if ($param === 'page' && (int) $value <= 1) {
            continue;
        }
        $query[$param] = $value;
    }

    $url = seo_url($path);

    return $query ? $url . '?' . http_build_query($query) : $url;
}

/** آیا صفحه‌ی جاری فیلتر/جستجوی سنگین دارد و باید noindex شود؟ */
function seo_should_noindex(array $indexableParams = []): bool
{
    $request = request();
    if (! $request) {
        return false;
    }

    $indexableParams = array_merge($indexableParams, (array) seo_config('canonical_query_whitelist', []));

    foreach ($request->query() as $key => $value) {
        if ($value === '' || $value === null) {
            continue;
        }
        if (! in_array($key, $indexableParams, true)) {
            return true;
        }
    }

    // صفحات عمیقِ صفحه‌بندی ارزش ایندکس ندارند.
    return (int) $request->query('page', 1) > 15;
}

function seo_robots_tag(bool $index = true, bool $follow = true): string
{
    return ($index ? 'index' : 'noindex') . ',' . ($follow ? 'follow' : 'nofollow')
        . ',max-image-preview:large,max-snippet:-1,max-video-preview:-1';
}

/*
|--------------------------------------------------------------------------
| Schema.org
|--------------------------------------------------------------------------
*/

function seo_organization_schema(): array
{
    $b = (array) seo_config('business', []);
    $social = array_values(array_filter((array) seo_config('social', [])));

    return [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        '@id' => seo_url('#organization'),
        'name' => seo_site_name(),
        'legalName' => $b['legal_name'] ?? null,
        'alternateName' => seo_config('alternate_name'),
        'url' => seo_url(),
        'logo' => [
            '@type' => 'ImageObject',
            '@id' => seo_url('#logo'),
            'url' => seo_image_url(seo_config('logo')),
            'width' => seo_config('default_image_width'),
            'height' => seo_config('default_image_height'),
            'caption' => seo_site_name(),
        ],
        'image' => ['@id' => seo_url('#logo')],
        'foundingDate' => $b['founding_date'] ?? null,
        'founder' => empty($b['founder']) ? null : ['@type' => 'Person', 'name' => $b['founder']],
        'email' => $b['email'] ?? null,
        'telephone' => $b['phone'] ?? null,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $b['street'] ?? null,
            'addressLocality' => $b['city'] ?? null,
            'addressRegion' => $b['region'] ?? null,
            'postalCode' => $b['postal_code'] ?? null,
            'addressCountry' => $b['country'] ?? 'IR',
        ],
        'contactPoint' => [[
            '@type' => 'ContactPoint',
            'telephone' => $b['phone'] ?? null,
            'contactType' => 'customer service',
            'areaServed' => $b['area_served'] ?? 'IR',
            'availableLanguage' => ['fa', 'Persian'],
            'email' => $b['email'] ?? null,
        ]],
        'sameAs' => $social,
    ];
}

function seo_store_schema(): array
{
    $b = (array) seo_config('business', []);
    $social = array_values(array_filter((array) seo_config('social', [])));

    $hours = [];
    foreach ((array) ($b['opening_hours'] ?? []) as $spec) {
        $hours[] = [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => $spec['days'],
            'opens' => $spec['opens'],
            'closes' => $spec['closes'],
        ];
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'AutoPartsStore',
        '@id' => seo_url('#store'),
        'name' => seo_site_name(),
        'alternateName' => seo_config('alternate_name'),
        'description' => 'فروشگاه تخصصی لوازم یدکی خودرو با تمرکز ویژه بر قطعات اصلی ایساکو و ارسال سراسر کشور.',
        'url' => seo_url(),
        'logo' => seo_image_url(seo_config('logo')),
        'image' => seo_image_url(seo_config('default_image')),
        'telephone' => $b['phone'] ?? null,
        'email' => $b['email'] ?? null,
        'priceRange' => $b['price_range'] ?? '$$',
        'currenciesAccepted' => $b['currency'] ?? 'IRR',
        'paymentAccepted' => 'کارت بانکی، پرداخت آنلاین، پرداخت در محل',
        'areaServed' => ['@type' => 'Country', 'name' => 'ایران'],
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $b['street'] ?? null,
            'addressLocality' => $b['city'] ?? null,
            'addressRegion' => $b['region'] ?? null,
            'postalCode' => $b['postal_code'] ?? null,
            'addressCountry' => $b['country'] ?? 'IR',
        ],
        'geo' => empty($b['latitude']) ? null : [
            '@type' => 'GeoCoordinates',
            'latitude' => $b['latitude'],
            'longitude' => $b['longitude'],
        ],
        'openingHoursSpecification' => $hours,
        'parentOrganization' => ['@id' => seo_url('#organization')],
        'sameAs' => $social,
    ];
}

/** WebSite + SearchAction برای فعال‌شدن Sitelinks Searchbox در گوگل. */
function seo_website_schema(): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        '@id' => seo_url('#website'),
        'name' => seo_site_name(),
        'alternateName' => seo_config('site_name_en'),
        'url' => seo_url(),
        'inLanguage' => seo_config('language', 'fa-IR'),
        'publisher' => ['@id' => seo_url('#organization')],
        'potentialAction' => [[
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => seo_url('/shop') . '?title={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ]],
    ];
}

function seo_webpage_schema(string $name, string $description, ?string $url = null, string $type = 'WebPage'): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => $type,
        '@id' => ($url ?? seo_canonical()) . '#webpage',
        'url' => $url ?? seo_canonical(),
        'name' => $name,
        'description' => $description,
        'inLanguage' => seo_config('language', 'fa-IR'),
        'isPartOf' => ['@id' => seo_url('#website')],
        'about' => ['@id' => seo_url('#organization')],
        'publisher' => ['@id' => seo_url('#organization')],
    ];
}

function seo_breadcrumb_schema(array $items): array
{
    $items = array_values($items);

    return [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_map(function ($item, $index) {
            $element = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
            ];
            // آخرین مورد (صفحه‌ی جاری) طبق مستندات گوگل نباید item داشته باشد.
            if (! empty($item['url'])) {
                $element['item'] = $item['url'];
            }
            return $element;
        }, $items, array_keys($items)),
    ];
}

/** تبدیل قیمت تومانِ دیتابیس به ریال (واحد ISO) برای Schema. */
function seo_price(?int $toman): ?string
{
    if ($toman === null || $toman <= 0) {
        return null;
    }

    return (string) ($toman * (int) seo_config('business.currency_multiplier', 10));
}

/**
 * Schema کامل محصول شامل Offer، برند، کد فنی، گالری تصاویر و سیاست
 * مرجوعی؛ همان چیزی که برای Rich Result محصول در گوگل لازم است.
 */
function seo_product_schema($product, array $images = [], ?array $breadcrumb = null): array
{
    $url = seo_url($product->url());
    // قطعات استعلامی (شاسی و بدنه) قیمت نمایشی ندارند، پس Offer هم برایشان
    // ساخته نمی‌شود؛ وگرنه گوگل قیمتی را نشان می‌داد که در سایت دیده نمی‌شود.
    $contactPrice = method_exists($product, 'isContactPrice') && $product->isContactPrice();
    $price = $contactPrice ? null : seo_price((int) $product->price);
    $inStock = ! isset($product->stock) || $product->stock === null
        ? true
        : ((int) $product->stock) > 0;

    $imageUrls = [];
    foreach ($images as $image) {
        $path = is_string($image) ? $image : ($image->path ?? null);
        if ($path) {
            $imageUrls[] = seo_image_url($path);
        }
    }
    if (! $imageUrls) {
        $imageUrls[] = seo_image_url($product->image());
    }
    $imageUrls = array_values(array_unique($imageUrls));

    $description = seo_description(
        $product->description ?: ($product->short_description ?: ($product->title . ' اصل، مناسب خرید از فروشگاه ناظر یدک')),
        300
    );

    $brand = ! empty($product->isaco_code) ? 'ایساکو' : seo_site_name();

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        '@id' => $url . '#product',
        'name' => $product->title,
        'description' => $description,
        'image' => $imageUrls,
        'url' => $url,
        'sku' => $product->sku ?: (string) $product->id,
        'mpn' => $product->isaco_code ?: ($product->sku ?: null),
        'productID' => (string) $product->id,
        'category' => method_exists($product, 'categoryLabel') ? $product->categoryLabel() : null,
        'brand' => ['@type' => 'Brand', 'name' => $brand],
        'manufacturer' => ['@type' => 'Organization', 'name' => $brand],
        'itemCondition' => 'https://schema.org/NewCondition',
        'isAccessoryOrSparePartFor' => empty($product->car_model) ? null : [
            '@type' => 'Product',
            'name' => $product->car_model,
        ],
        'weight' => empty($product->weight) ? null : [
            '@type' => 'QuantitativeValue',
            'value' => (string) $product->weight,
            'unitCode' => 'GRM',
        ],
    ];

    if ($price !== null) {
        $schema['offers'] = seo_array_clean([
            '@type' => 'Offer',
            '@id' => $url . '#offer',
            'url' => $url,
            'price' => $price,
            'priceCurrency' => seo_config('business.currency', 'IRR'),
            'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
            'priceValidUntil' => now()->addMonths(3)->format('Y-m-d'),
            'seller' => ['@id' => seo_url('#organization')],
            'hasMerchantReturnPolicy' => [
                '@type' => 'MerchantReturnPolicy',
                'applicableCountry' => 'IR',
                'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                'merchantReturnDays' => 7,
                'returnMethod' => 'https://schema.org/ReturnByMail',
                'returnFees' => 'https://schema.org/ReturnShippingFees',
                'merchantReturnLink' => seo_url('/return-policy'),
            ],
            'shippingDetails' => [
                '@type' => 'OfferShippingDetails',
                'shippingDestination' => ['@type' => 'DefinedRegion', 'addressCountry' => 'IR'],
                'deliveryTime' => [
                    '@type' => 'ShippingDeliveryTime',
                    'handlingTime' => ['@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => 1, 'unitCode' => 'DAY'],
                    'transitTime' => ['@type' => 'QuantitativeValue', 'minValue' => 1, 'maxValue' => 5, 'unitCode' => 'DAY'],
                ],
            ],
        ]);
    }

    if ($breadcrumb) {
        $schema['breadcrumb'] = ['@id' => $url . '#breadcrumb'];
    }

    return $schema;
}

/** فهرست محصولات/مقالات به‌صورت ItemList برای صفحات آرشیو. */
function seo_itemlist_schema(string $name, iterable $items, ?string $url = null): array
{
    $elements = [];
    $position = 1;

    foreach ($items as $item) {
        $itemUrl = $item['url'] ?? null;
        if (! $itemUrl) {
            continue;
        }
        $elements[] = array_filter([
            '@type' => 'ListItem',
            'position' => $position++,
            'url' => $itemUrl,
            'name' => $item['name'] ?? null,
            'image' => $item['image'] ?? null,
        ]);
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        '@id' => ($url ?? seo_canonical()) . '#itemlist',
        'name' => $name,
        'url' => $url ?? seo_canonical(),
        'numberOfItems' => count($elements),
        'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
        'itemListElement' => $elements,
    ];
}

function seo_faq_schema(array $faqs): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        '@id' => seo_canonical() . '#faq',
        'mainEntity' => array_map(function ($faq) {
            return [
                '@type' => 'Question',
                'name' => $faq['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['a'],
                ],
            ];
        }, array_values($faqs)),
    ];
}

function seo_article_schema(array $data): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => $data['type'] ?? 'BlogPosting',
        '@id' => $data['url'] . '#article',
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $data['url']],
        'headline' => mb_substr($data['title'], 0, 110),
        'name' => $data['title'],
        'description' => $data['description'] ?? null,
        'image' => empty($data['image']) ? null : [
            '@type' => 'ImageObject',
            'url' => $data['image'],
        ],
        'datePublished' => $data['published'] ?? null,
        'dateModified' => $data['modified'] ?? ($data['published'] ?? null),
        'inLanguage' => seo_config('language', 'fa-IR'),
        'articleSection' => $data['section'] ?? null,
        'keywords' => $data['keywords'] ?? null,
        'wordCount' => $data['word_count'] ?? null,
        'author' => [
            '@type' => 'Organization',
            'name' => $data['author'] ?? seo_site_name(),
            'url' => seo_url(),
        ],
        'publisher' => ['@id' => seo_url('#organization')],
        'isPartOf' => ['@id' => seo_url('#website')],
        'speakable' => [
            '@type' => 'SpeakableSpecification',
            'cssSelector' => ['h1', '.blog-article-title'],
        ],
    ];
}

/** بسته‌ی پایه‌ای که در همه‌ی صفحات تکرار می‌شود. */
function seo_base_schema(): array
{
    return [seo_organization_schema(), seo_website_schema(), seo_store_schema()];
}

/**
 * کاور اختصاصی مقاله برای صفحه‌ی اول.
 * عکس‌های قدیمیِ نامرتبط حذف شده‌اند؛ به جای آن‌ها بر اساس موضوعِ عنوان
 * یکی از تصاویر برداریِ طراحی‌شده‌ی سایت انتخاب می‌شود تا چیدمان صفحه حفظ شود.
 */
function article_cover_map(): array
{
    // ترتیب کلیدها مهم است: موضوع‌های خاص‌تر اول بررسی می‌شوند.
    return [
        'brake'      => ['ترمز', 'لنت', 'کاسه چرخ'],
        'cooling'    => ['رادیاتور', 'واتر پمپ', 'ترموستات', 'فن ', 'شلنگ', 'خنک', 'بخاری', 'فشنگی'],
        'filter'     => ['فیلتر', 'روغن', 'سرویس دوره'],
        'electric'   => ['باتری', 'دینام', 'ecu', 'رله', 'کویل', 'شمع', 'استپر', 'انژکتور', 'پمپ بنزین', 'سنسور', 'برقی', 'سیم کلاچ', 'سیم گاز'],
        'light'      => ['چراغ', 'مه شکن'],
        'suspension' => ['جلوبندی', 'کمک فنر', 'بلبرینگ', 'پلوس', 'کلاچ', 'چرخ'],
        'body'       => ['سپر', 'بدنه', 'قالپاق', 'جلو پنجره', 'شبکه', 'دستگیره', 'قفل', 'برف پاک کن', 'قاب', 'آینه'],
        'engine'     => ['موتور', 'تسمه تایم', 'سرسیلندر', 'اگزوز', 'کاتالیزور', 'کاسه نمد', 'اورینگ', 'سوخت'],
        'quality'    => ['کد فنی', 'اصل', 'ایساکو', 'مطمئن', 'تقلبی', 'گارانتی'],
        'guide'      => ['راهنما', 'چک لیست', 'انتخاب', 'خرید', 'نکات'],
    ];
}

/** آدرس کاور مقاله؛ اگر عنوان با هیچ موضوعی جور نشد، بر اساس شناسه‌ی مقاله ثابت انتخاب می‌شود. */
function article_cover($article): string
{
    $title = is_object($article) ? (string) ($article->titr ?? '') : (string) $article;
    $title = mb_strtolower($title, 'UTF-8');

    foreach (article_cover_map() as $cover => $keywords) {
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && mb_strpos($title, mb_strtolower($keyword, 'UTF-8')) !== false) {
                return '/assets/images/blog/' . $cover . '.svg';
            }
        }
    }

    $covers = array_keys(article_cover_map());
    $id = is_object($article) ? (int) ($article->articleid ?? 0) : 0;

    return '/assets/images/blog/' . $covers[$id % count($covers)] . '.svg';
}
