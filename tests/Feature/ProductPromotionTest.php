<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * «نردبان» بازدید: قطعه‌ای که در یک روز به آستانه برسد، اول فهرست‌ها می‌آید.
 *
 * مثل بقیه‌ی تست‌های محصول، جدول‌ها دستی روی sqlite حافظه‌ای ساخته می‌شوند
 * چون جدول products مهاجرت کامل ندارد.
 */
class ProductPromotionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Product::flushContactPriceCache();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('price')->default(0);
            $table->boolean('wholesale_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->date('viewed_on');
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'viewed_on']);
        });
    }

    private function product(string $title): Product
    {
        return Product::create([
            'title'     => $title,
            'price'     => 1000000,
            'file_path' => '/images/part.webp',
            'is_active' => true,
        ]);
    }

    private function seedViews(Product $product, string $day, int $hits): void
    {
        DB::table('product_views')->insert([
            'product_id' => $product->id,
            'viewed_on'  => $day,
            'hits'       => $hits,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** همان ترتیبی که فهرست فروشگاه و پیشنهاد زنده استفاده می‌کنند. */
    private function listing(): array
    {
        return Product::where('is_active', 1)
            ->orderByPromotion()
            ->orderByDesc('id')
            ->pluck('id')
            ->all();
    }

    public function test_reaching_the_daily_threshold_lifts_a_product_to_the_top(): void
    {
        $promoted = $this->product('قطعه پربازدید');
        $newer    = $this->product('قطعه تازه ثبت شده');

        $this->seedViews($promoted, today()->toDateString(), Product::PROMOTION_MIN_DAILY_HITS);

        // ترتیب پیش‌فرض فهرست «جدیدترین» است، پس بدون نردبان $newer اول می‌آمد.
        $this->assertSame([$promoted->id, $newer->id], $this->listing());
    }

    public function test_one_view_short_of_the_threshold_changes_nothing(): void
    {
        $almost = $this->product('یک بازدید کم');
        $newer  = $this->product('قطعه تازه ثبت شده');

        $this->seedViews($almost, today()->toDateString(), Product::PROMOTION_MIN_DAILY_HITS - 1);

        $this->assertSame([$newer->id, $almost->id], $this->listing());
    }

    public function test_days_below_the_threshold_do_not_add_up(): void
    {
        $trickle = $this->product('روزی چهار بازدید');
        $newer   = $this->product('قطعه تازه ثبت شده');

        // مجموع این پنج روز از آستانه خیلی بیشتر است، ولی هیچ روزی خودش به
        // آستانه نرسیده؛ نردبان مالِ روزِ واقعا پربازدید است نه جمعِ قطره‌ها.
        for ($day = 0; $day < 5; $day++) {
            $this->seedViews($trickle, today()->subDays($day)->toDateString(), Product::PROMOTION_MIN_DAILY_HITS - 1);
        }

        $this->assertSame([$newer->id, $trickle->id], $this->listing());
    }

    public function test_the_ladder_expires_after_the_window(): void
    {
        $expired = $this->product('پربازدیدِ ماه پیش');
        $newer   = $this->product('قطعه تازه ثبت شده');

        $this->seedViews(
            $expired,
            today()->subDays(Product::PROMOTION_WINDOW_DAYS)->toDateString(),
            500
        );

        $this->assertSame([$newer->id, $expired->id], $this->listing());
    }

    public function test_busier_days_rank_above_quieter_ones(): void
    {
        $busy   = $this->product('روز شلوغ');
        $quiet  = $this->product('روز آرام');

        $this->seedViews($quiet, today()->toDateString(), Product::PROMOTION_MIN_DAILY_HITS);
        $this->seedViews($busy, today()->toDateString(), Product::PROMOTION_MIN_DAILY_HITS * 10);

        // $quiet شناسه‌ی کوچک‌تری دارد و در ترتیب پیش‌فرض عقب‌تر است؛ اینجا
        // فقط تعداد بازدید تعیین‌کننده است.
        $this->assertSame([$busy->id, $quiet->id], $this->listing());
    }
}
