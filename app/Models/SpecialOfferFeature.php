<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * حافظه‌ی ریل «پیشنهاد ویژه»: هر محصول چه روزهایی در ریل نشسته است.
 *
 * تنها مصرفش تصمیمِ چرخش است: محصولی که دیروز در ریل بوده، امروز جای خود
 * را به پربازدیدهای تازه می‌دهد. مثل شمارنده‌ی بازدید، هیچ‌کدام از این
 * مسیرها نباید صفحه‌ی اصلی را بشکند، پس همه داخل try/catch و روی Query
 * Builder هستند.
 */
class SpecialOfferFeature extends Model
{
    protected $table = 'special_offer_features';

    protected $fillable = ['product_id', 'featured_on'];

    protected $casts = [
        'featured_on' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * شناسه‌ی محصول‌هایی که در این روز در ریل بوده‌اند.
     *
     * @return int[]
     */
    public static function idsFeaturedOn(string $day): array
    {
        try {
            return DB::table('special_offer_features')
                ->where('featured_on', $day)
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        } catch (\Throwable $e) {
            // جدول هنوز مهاجرت نشده یا دیتابیس در دسترس نیست؛ نبودِ سابقه
            // یعنی «هیچ‌کس دیروز در ریل نبوده» و ریل با قاعده‌ی قبلی ساخته
            // می‌شود، نه اینکه صفحه‌ی اصلی خطا بدهد.
            return [];
        }
    }

    /**
     * ثبت ترکیب امروزِ ریل.
     *
     * صفحه‌ی اصلی پربازدیدترین صفحه‌ی سایت است و این متد در هر رندر صدا
     * زده می‌شود؛ تا وقتی ترکیب عوض نشده، حتی یک کوئری هم زده نمی‌شود.
     * کلید کش تا پایان همان روز معتبر است چون ملاکِ چرخش «روز» است.
     *
     * @param  int[]  $productIds
     */
    public static function remember(array $productIds): void
    {
        try {
            $productIds = array_values(array_unique(array_map('intval', $productIds)));

            if ($productIds === []) {
                return;
            }

            $today = now()->toDateString();
            $key   = 'special_offer_featured:' . $today;
            $hash  = md5(implode(',', $productIds));

            if (Cache::get($key) === $hash) {
                return;
            }

            $now = now();

            DB::table('special_offer_features')->insertOrIgnore(
                array_map(fn (int $id) => [
                    'product_id'  => $id,
                    'featured_on' => $today,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ], $productIds)
            );

            Cache::put($key, $hash, now()->endOfDay());
        } catch (\Throwable $e) {
            // ثبت نشدنِ سابقه فقط یعنی ریل فردا کمتر می‌چرخد؛ صفحه‌ی اصلی
            // نباید به‌خاطرش خطا بدهد.
        }
    }
}
