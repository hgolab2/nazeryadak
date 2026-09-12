<?php

namespace App\Http\Controllers;

use App\Services\BaleBot;
use Illuminate\Http\Request;

/**
 * نقطه‌ای که بله رویدادهای ربات (پیام مدیر، لمس دکمه) را به سایت می‌فرستد.
 *
 * آدرس، بخش مخفی دارد (/bale/webhook/{secret}) چون هیچ امضای دیگری در
 * درخواست‌های بله نیست؛ هر کس آدرس را نداند نمی‌تواند خودش را جای بله جا
 * بزند. ثبت آدرس با «php artisan bale:webhook» انجام می‌شود.
 */
class BaleWebhookController extends Controller
{
    public function handle(Request $request, string $secret)
    {
        if (! hash_equals(self::secret(), $secret)) {
            abort(404);
        }

        $update = $request->json()->all();

        if (is_array($update) && $update !== []) {
            (new BaleBot())->handle($update);
        }

        // بله منتظر ۲۰۰ است؛ هر پاسخ دیگری باعث ارسال دوباره‌ی همان رویداد می‌شود
        return response()->json(['ok' => true]);
    }

    /**
     * بخش مخفی آدرس وبهوک.
     *
     * اگر BALE_WEBHOOK_SECRET تنظیم نشده، از روی توکن ربات ساخته می‌شود؛
     * توکن خودش محرمانه است، پس هش آن هم قابل حدس نیست.
     */
    public static function secret(): string
    {
        $configured = (string) config('bale.webhook_secret');

        if ($configured !== '') {
            return $configured;
        }

        return substr(hash('sha256', 'bale-webhook|' . (string) config('bale.token') . '|' . (string) config('app.key')), 0, 40);
    }

    /** آدرس کامل وبهوک برای ثبت در بله. */
    public static function url(): string
    {
        return rtrim((string) config('app.url'), '/') . '/bale/webhook/' . self::secret();
    }
}
