<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * بازدید روزانه‌ی هر محصول: یک ردیف به‌ازای «محصول × روز».
 *
 * ثبت بازدید هیچ‌وقت نباید صفحه‌ی محصول را بشکند، پس همه‌ی مسیرها داخل
 * try/catch هستند و با Query Builder کار می‌کنند تا رویدادهای مدل و
 * touch شدن updated_at محصول را راه نیندازند.
 */
class ProductView extends Model
{
    protected $table = 'product_views';

    protected $fillable = ['product_id', 'viewed_on', 'hits'];

    protected $casts = [
        'viewed_on' => 'date',
        'hits'      => 'integer',
    ];

    /**
     * نشانه‌های خزنده در User-Agent.
     *
     * بدون این فیلتر، ریلِ «پربازدیدترین» عملا ترتیب خزش گوگل را نشان می‌داد:
     * خزنده کوکی نگه نمی‌دارد، پس هر بازدیدش یک نشست تازه و یک شمارش جدید
     * حساب می‌شد؛ روزی چند ده آدرس هم می‌خزد.
     */
    private const BOT_SIGNATURES = [
        'bot', 'crawl', 'spider', 'slurp', 'bingpreview', 'facebookexternalhit',
        'headlesschrome', 'python-requests', 'curl/', 'wget', 'axios', 'go-http-client',
        'ahrefs', 'semrush', 'mj12', 'dotbot', 'petalbot', 'yandex', 'applebot',
    ];

    /** کلید نشست برای محصول‌هایی که در همین نشست دیده شده‌اند. */
    private const SESSION_KEY = 'viewed_products';

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * ثبت یک بازدید برای محصول.
     *
     * رفرش صفحه یا برگشت با دکمه‌ی back شمارش را بالا نمی‌برد: هر محصول در
     * هر نشست، روزی یک بار شمرده می‌شود. بدون این محدودیت عددِ «پربازدید»
     * بیشتر بازتاب رفتار یک کاربر بود تا محبوبیت قطعه.
     */
    public static function record(Product $product, Request $request): void
    {
        try {
            if (self::isBot((string) $request->userAgent())) {
                return;
            }

            $today = now()->toDateString();

            if (! self::markSeen($request, (int) $product->id, $today)) {
                return;
            }

            $now = now();

            $updated = DB::table('product_views')
                ->where('product_id', $product->id)
                ->where('viewed_on', $today)
                ->update([
                    'hits'       => DB::raw('hits + 1'),
                    'updated_at' => $now,
                ]);

            if (! $updated) {
                // insertOrIgnore به‌جای insert: ایندکس یکتا ممکن است در
                // درخواست هم‌زمانِ دیگری همین ردیف را ساخته باشد و خطای
                // duplicate نباید به کاربر برسد.
                $inserted = DB::table('product_views')->insertOrIgnore([
                    'product_id' => $product->id,
                    'viewed_on'  => $today,
                    'hits'       => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if (! $inserted) {
                    DB::table('product_views')
                        ->where('product_id', $product->id)
                        ->where('viewed_on', $today)
                        ->update(['hits' => DB::raw('hits + 1'), 'updated_at' => $now]);
                }
            }

            // مجموع کل، روی خودِ محصول. عمدا updated_at محصول را دست نمی‌زند
            // چون چند فهرست با latest('updated_at') مرتب می‌شوند و بازدید
            // نباید ترتیبشان را جابه‌جا کند.
            DB::table('products')
                ->where('id', $product->id)
                ->update(['views_count' => DB::raw('views_count + 1')]);
        } catch (\Throwable $e) {
            // شمارنده نباید مانع نمایش صفحه‌ی محصول شود.
        }
    }

    /**
     * آیا این محصول امروز در این نشست شمرده نشده است؟
     *
     * اگر شمرده نشده، علامت می‌خورد و true برمی‌گردد.
     */
    private static function markSeen(Request $request, int $productId, string $today): bool
    {
        if (! $request->hasSession()) {
            return true;
        }

        $session = $request->session();
        $seen = $session->get(self::SESSION_KEY, []);

        if (! is_array($seen)) {
            $seen = [];
        }

        if (($seen[$productId] ?? null) === $today) {
            return false;
        }

        // نشست بلندمدت است و سیاهه‌ی محصول‌های دیده‌شده بی‌نهایت رشد می‌کند؛
        // با تغییر روز از صفر شروع می‌شود.
        $seen = array_filter($seen, fn ($day) => $day === $today);

        $seen[$productId] = $today;
        $session->put(self::SESSION_KEY, $seen);

        return true;
    }

    private static function isBot(string $userAgent): bool
    {
        if ($userAgent === '') {
            // مرورگر واقعی User-Agent می‌فرستد؛ درخواست بی‌امضا شمرده نمی‌شود.
            return true;
        }

        $userAgent = strtolower($userAgent);

        foreach (self::BOT_SIGNATURES as $signature) {
            if (str_contains($userAgent, $signature)) {
                return true;
            }
        }

        return false;
    }
}
