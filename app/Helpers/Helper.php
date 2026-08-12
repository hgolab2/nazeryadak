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
function sendSms($to_number, $text, $udh = "",$adv="")
{
    if(strlen($to_number) != 11 || substr($to_number , 0 , 2) != '09')return;
    if (trim($text) == '') return;
    date_default_timezone_set('Asia/Tehran');
    $text = str_replace('\n', "\n", $text);
    $input["text"] = $text;
    $text = urlencode("$text");
    $sms_number = env('SMS_NUMBER');
    $username = env('SMS_USERNAME');
    $password = env('SMS_PASSWORD');
    $url_sms = "http://tsms.ir/url/tsmshttp.php?from=$sms_number&to=$to_number&username=$username&password=$password&message=$text";

    eval ("\$url_sms = \"$url_sms\";");
    $code_number = file_get_contents_curl("$url_sms");
    $input["type"] = 1;
    $input["mobile"] = $to_number;

    $user = Auth::guard('customer')->user();
    // user not found
    if ($user) {
        $input["user_id"] = $user->id;
    }
    $input["udh"]=$code_number;
    Sms::create($input);
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
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = \DB::table('shipping_settings')->pluck('setting_value', 'setting_key')->toArray();
    return $cache;
}

function postCalculation($orderid)
{
    $order = Order::where('id', $orderid)->first();
    $info = getShippingInfo($order);
    return $info['cost'];
}

function getShippingInfo($order)
{
    $settings = getShippingSettings();
    $localProvinceId      = (int) ($settings['local_province_id'] ?? 19);
    $localFreeThreshold   = (int) ($settings['local_free_threshold'] ?? 50000000);
    $localShippingCost    = (int) ($settings['local_shipping_cost'] ?? 500000);
    $nationalFreeThreshold = (int) ($settings['national_free_threshold'] ?? 200000000);

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
            return ['cost' => 0, 'type' => 'free', 'label' => 'ارسال رایگان (قم)'];
        }
        return ['cost' => $localShippingCost, 'type' => 'local', 'label' => number_format($localShippingCost) . ' تومان (پیک در قم)'];
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
function seo_site_name(): string
{
    return 'ناظر یدک';
}

function seo_base_url(): string
{
    return rtrim(config('app.url') ?: url('/'), '/');
}

function seo_url(string $path = ''): string
{
    return seo_base_url() . '/' . ltrim($path, '/');
}

function seo_description(string $text, int $limit = 155): string
{
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)));
    return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit - 1) . '…' : $text;
}

function seo_json_ld(array $data): string
{
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

function seo_default_keywords(): string
{
    return 'خرید لوازم یدکی خودرو, قطعات ایساکو, لوازم یدکی ایساکو, خرید قطعات اصلی ایساکو, نمایندگی قطعات ایساکو, قطعات اصلی خودرو, خرید قطعات خودرو, فروشگاه لوازم یدکی, قطعات پژو 206, قطعات پژو 405, قطعات سمند, قطعات دنا, قطعات پراید, کد فنی قطعه خودرو';
}

function seo_store_schema(): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'AutoPartsStore',
        '@id' => seo_url('#store'),
        'name' => seo_site_name(),
        'alternateName' => ['nazeryadak', 'فروشگاه قطعات ایساکو'],
        'description' => 'فروشگاه تخصصی لوازم یدکی خودرو با تمرکز ویژه بر قطعات اصلی ایساکو و ارسال سراسر کشور.',
        'url' => seo_url(),
        'logo' => seo_url('/assets/images/logo.png'),
        'image' => seo_url('/assets/images/logo.png'),
        'telephone' => '+989127471631',
        'priceRange' => '$',
        'areaServed' => 'IR',
        'address' => ['@type' => 'PostalAddress', 'addressCountry' => 'IR', 'addressRegion' => 'قم'],
        'sameAs' => ['https://wa.me/989127471631'],
    ];
}

function seo_breadcrumb_schema(array $items): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_map(function ($item, $index) {
            return ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $item['name'], 'item' => $item['url']];
        }, array_values($items), array_keys(array_values($items))),
    ];
}
?>