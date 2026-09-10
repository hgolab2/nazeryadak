<?php

namespace App\Services;

/**
 * بندانگشتی‌سازی تصاویر در اندازه‌ای که واقعا نمایش داده می‌شوند.
 *
 * تصاویر منبع حدود ۱۰۲۴ پیکسل‌اند، ولی در کارت محصول حدود ۱۳۴ پیکسل و در
 * گالری صفحه‌ی کالا حدود ۳۹۰ پیکسل دیده می‌شوند. تا پیش از این، مرورگر
 * تصویر کامل را می‌گرفت و خودش کوچک می‌کرد؛ یعنی چند برابر بایتِ لازم روی
 * شبکه. اینجا برای هر تصویر یک نسخه‌ی WebP در اندازه‌ی نمایش ساخته و در
 * public/cache/thumbs نگهداری می‌شود.
 *
 * فایل‌ها زیر public می‌نشینند تا آپاچی مستقیم و بدون بوت لاراول تحویلشان
 * بدهد؛ فقط اولین درخواستِ هر تصویر به PHP می‌رسد (ThumbnailController) و
 * از آن به بعد یک فایل ثابت است. اصل تصویر هیچ‌وقت دست نمی‌خورد.
 */
class ThumbnailService
{
    /**
     * عرض‌های مجاز، برگرفته از اندازه‌ی واقعی نمایش در CSS:
     *
     *   160 → بندانگشتی‌های گالری صفحه‌ی کالا (۶۴px، دو برابر برای رتینا)
     *   300 → کارت محصول در ریل صفحه‌ی اصلی و گرید فروشگاه (۱۳۴–۱۹۶px)
     *   600 → تصویر اصلی صفحه‌ی کالا (۳۹۰px) و نسخه‌ی ۲x کارت‌ها
     *
     * لیست بسته است تا کسی با درخواست هزار عرضِ دلخواه دیسک را پر نکند.
     */
    public const SIZES = [160, 300, 600];

    /** مسیر پوشه‌ی کش، نسبت به public. */
    public const CACHE_DIR = 'cache/thumbs';

    /**
     * آدرس نسخه‌ی بندانگشتی یک تصویر.
     *
     * وجود فایلِ بندانگشتی بررسی نمی‌شود — اگر نباشد، اولین درخواست آن را
     * می‌سازد. فقط وجود «اصل تصویر» چک می‌شود تا برای مسیرهای خراب آدرسِ
     * بی‌مصرف تولید نشود.
     */
    public static function url(?string $source, int $width): ?string
    {
        $source = trim((string) $source);

        if ($source === '' || ! in_array($width, self::SIZES, true) || ! self::usable()) {
            return null;
        }

        // تصاویر روی دامنه‌ی دیگر را نمی‌توانیم بخوانیم؛ SVG هم برداری است
        // و کوچک‌سازی برایش بی‌معنی.
        if (str_starts_with($source, 'http://') || str_starts_with($source, 'https://')) {
            return null;
        }

        // پسوند باید انتهای خودِ آدرس باشد؛ این شرط هم فرمت را محدود
        // می‌کند و هم آدرس‌های دارای ?query یا #hash را کنار می‌گذارد که
        // چسباندن «.webp» به انتهایشان آدرس بی‌معنی می‌ساخت.
        if (! preg_match('/\.(jpe?g|png)$/i', $source)) {
            return null;
        }

        if (! public_file_path(self::relative($source))) {
            return null;
        }

        return '/' . self::CACHE_DIR . '/' . $width . '/' . ltrim($source, '/') . '.webp';
    }

    /**
     * ساخت فایل بندانگشتی و برگرداندن مسیر مطلقش.
     *
     * @param  string  $source  مسیر تصویر اصلی نسبت به public (مثل upload/products/…/a.jpg)
     */
    public static function generate(string $source, int $width): ?string
    {
        if (! in_array($width, self::SIZES, true) || ! function_exists('imagewebp')) {
            return null;
        }

        $relative = self::safeRelative($source);
        if (! $relative) {
            return null;
        }

        $absolute = public_file_path($relative);
        if (! $absolute) {
            return null;
        }

        $target = public_path(self::CACHE_DIR . '/' . $width . '/' . $relative . '.webp');

        // اگر نسخه‌ی معتبر و به‌روزی هست، دوباره نمی‌سازیم.
        if (is_file($target) && filemtime($target) >= filemtime($absolute)) {
            return $target;
        }

        $image = self::load($absolute);
        if (! $image) {
            return null;
        }

        // PNG شفاف بدون این سه خط پس‌زمینه‌ی سیاه می‌گیرد.
        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        $image = self::downscale($image, $width);

        $dir = dirname($target);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            imagedestroy($image);
            return null;
        }

        /*
        | نوشتن روی فایل موقت و سپس rename: اگر دو درخواست هم‌زمان یک
        | بندانگشتی را بسازند، مرورگر هیچ‌وقت فایل نیمه‌نوشته نمی‌گیرد.
        */
        $temp = $target . '.' . getmypid() . '.tmp';
        $ok = @imagewebp($image, $temp, 80);
        imagedestroy($image);

        if (! $ok || ! is_file($temp)) {
            @unlink($temp);
            return null;
        }

        if (! @rename($temp, $target)) {
            @unlink($temp);
            return null;
        }

        return $target;
    }

    /**
     * آیا این سرور اصلا می‌تواند بندانگشتی بسازد؟
     *
     * اگر نتواند (GD بدون WebP، یا پوشه‌ی public غیرقابل‌نوشتن) قالب‌ها
     * نباید آدرس بندانگشتی بدهند: هر تصویر یک‌بار به PHP می‌خورد، ساخت
     * شکست می‌خورد و با ریدایرکت به فایل اصلی برمی‌گردد — یعنی صفحه از
     * قبل هم کندتر می‌شود. با برگرداندن null، قالب‌ها بی‌سروصدا به همان
     * رفتار قبلی (webp کنار فایل) برمی‌گردند.
     *
     * یک‌بار در هر درخواست بررسی می‌شود.
     */
    private static function usable(): bool
    {
        static $usable = null;

        if ($usable !== null) {
            return $usable;
        }

        if (! function_exists('imagewebp')) {
            return $usable = false;
        }

        $root = public_path(self::CACHE_DIR);

        // اگر پوشه هنوز ساخته نشده، ملاک نوشتنی‌بودنِ خودِ public است.
        return $usable = is_dir($root) ? is_writable($root) : is_writable(public_path());
    }

    /**
     * پاک‌کردن بندانگشتی‌های یک تصویر، برای وقتی فایل اصلی در همان مسیر
     * بازنویسی می‌شود (مثل «images:isaco --force»).
     *
     * بدون این کار، تصویر عوض می‌شود ولی کاربر نسخه‌ی قدیمی را می‌بیند:
     * وقتی فایل بندانگشتی ساخته شده باشد، آپاچی مستقیم تحویلش می‌دهد و
     * درخواست هیچ‌وقت به PHP نمی‌رسد، پس مقایسه‌ی تاریخِ داخل generate
     * اصلا اجرا نمی‌شود. آپلود پنل مدیریت این مشکل را ندارد چون هر فایل
     * نام یکتا (uniqid) می‌گیرد و مسیر تکرار نمی‌شود.
     */
    public static function forget(string $source): void
    {
        $relative = self::safeRelative($source);

        if (! $relative) {
            return;
        }

        foreach (self::SIZES as $size) {
            @unlink(public_path(self::CACHE_DIR . '/' . $size . '/' . $relative . '.webp'));
        }
    }

    /**
     * مسیر نسبیِ یک تصویر، فقط اگر درخواستش بی‌خطر باشد؛ وگرنه null.
     *
     * تنها جایی است که ورودی کاربر اعتبارسنجی می‌شود، و هم generate و هم
     * کنترلر از آن استفاده می‌کنند. اگر کنترلر بررسی جداگانه‌ی خودش را
     * داشت، دو مسیرِ متفاوت پیدا می‌شد و آنکه از قلم می‌افتاد راهِ نفوذ
     * می‌شد — مثل مسیرِ جایگزینی که هنگام شکستِ ساخت اجرا می‌شود.
     */
    public static function safeRelative(string $source): ?string
    {
        $relative = self::relative($source);

        if ($relative === '') {
            return null;
        }

        // «..» بعد از رمزگشایی بررسی می‌شود تا ..%2f هم گرفته شود.
        if (str_contains($relative, '..')) {
            return null;
        }

        // فقط تصویر؛ بدون این شرط هر مسیری (مثل index.php) به شاخه‌ی
        // جایگزین می‌رسید و آدرسش به کاربر برگردانده می‌شد.
        if (! preg_match('/\.(jpe?g|png)$/i', $relative)) {
            return null;
        }

        return $relative;
    }

    /** مسیر نسبی و رمزگشایی‌شده‌ی یک آدرس تصویر. */
    public static function relative(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: $path;

        return ltrim(str_replace('\\', '/', rawurldecode($path)), '/');
    }

    /** کوچک‌سازی با حفظ نسبت ابعاد؛ تصویرِ کوچک‌تر از حد، بزرگ نمی‌شود. */
    private static function downscale($image, int $maxDim)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $longest = max($width, $height);

        if ($longest <= $maxDim) {
            return $image;
        }

        $ratio = $maxDim / $longest;
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $canvas;
    }

    private static function load(string $path)
    {
        try {
            $image = str_ends_with(strtolower($path), '.png')
                ? @imagecreatefrompng($path)
                : @imagecreatefromjpeg($path);

            return $image ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
