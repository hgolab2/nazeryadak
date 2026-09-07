<?php

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
use App\Models\Product;
use App\Models\ProductView;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * شمارش بازدید محصول و ریل «پیشنهاد ویژه»ی ساخته‌شده روی آن.
 *
 * مثل بقیه‌ی تست‌های محصول، جدول‌ها دستی روی sqlite حافظه‌ای ساخته می‌شوند
 * چون جدول products مهاجرت کامل ندارد.
 */
class ProductViewCountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Product::flushContactPriceCache();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('price')->default(0);
            $table->integer('discount_percent')->default(0);
            $table->boolean('is_special_offer')->default(false);
            // مدل Product مقدار پیش‌فرض این ستون را روی نمونه‌ی تازه می‌نشاند
            $table->boolean('wholesale_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamps();
        });

        Schema::create('product_in_category', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->integer('category_id');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->string('path')->nullable();
            $table->string('alt')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
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

    private function product(array $attributes = []): Product
    {
        return Product::create($attributes + [
            'title'     => 'قطعه آزمایشی',
            'price'     => 1000000,
            'file_path' => '/images/part.webp',
            'is_active' => true,
        ]);
    }

    /** درخواستی با نشست، مثل درخواست واقعیِ یک مرورگر. */
    private function browserRequest(string $userAgent = 'Mozilla/5.0 (Windows NT 10.0) Chrome/120'): Request
    {
        $request = Request::create('/product/1', 'GET', [], [], [], ['HTTP_USER_AGENT' => $userAgent]);
        $request->setLaravelSession(new Store('test', new ArraySessionHandler(120)));

        return $request;
    }

    private function views(Product $product, ?string $day = null): int
    {
        return (int) DB::table('product_views')
            ->where('product_id', $product->id)
            ->where('viewed_on', $day ?? today()->toDateString())
            ->value('hits');
    }

    public function test_a_visit_is_stored_for_today_and_added_to_the_total(): void
    {
        $product = $this->product();

        ProductView::record($product, $this->browserRequest());

        $this->assertSame(1, $this->views($product));
        $this->assertSame(1, (int) $product->fresh()->views_count);
    }

    public function test_refreshing_the_page_in_the_same_session_is_counted_once(): void
    {
        $product = $this->product();
        $request = $this->browserRequest();

        ProductView::record($product, $request);
        ProductView::record($product, $request);
        ProductView::record($product, $request);

        $this->assertSame(1, $this->views($product));
    }

    public function test_a_different_visitor_adds_to_the_same_day_row(): void
    {
        $product = $this->product();

        ProductView::record($product, $this->browserRequest());
        ProductView::record($product, $this->browserRequest());

        $this->assertSame(2, $this->views($product));
        $this->assertSame(1, DB::table('product_views')->count());
    }

    public function test_crawlers_are_not_counted(): void
    {
        $product = $this->product();

        ProductView::record($product, $this->browserRequest('Mozilla/5.0 (compatible; Googlebot/2.1)'));
        ProductView::record($product, $this->browserRequest('curl/8.4.0'));

        $this->assertSame(0, DB::table('product_views')->count());
        $this->assertSame(0, (int) $product->fresh()->views_count);
    }

    public function test_the_two_day_window_is_used_while_it_can_fill_the_rail(): void
    {
        $top    = $this->product(['title' => 'صدر', 'discount_percent' => 5]);
        $middle = $this->product(['title' => 'وسط', 'discount_percent' => 5]);
        $bottom = $this->product(['title' => 'ته', 'discount_percent' => 5]);
        $stale  = $this->product(['title' => 'پربازدید قدیمی', 'discount_percent' => 50]);

        $this->seedViews($top, today()->toDateString(), 30);
        $this->seedViews($middle, today()->subDay()->toDateString(), 20);
        $this->seedViews($bottom, today()->toDateString(), 5);

        // بازدید سه‌روزِ‌پیش، حتی با عدد بزرگ، در پنجره‌ی دو روزه دیده نمی‌شود
        $this->seedViews($stale, today()->subDays(3)->toDateString(), 900);

        // سه قطعه در دو روز اخیر بازدید خورده‌اند و ریلِ سه‌تایی را پر می‌کنند،
        // پس پنجره باز نمی‌شود و $stale بیرون می‌ماند.
        $rail = (new HomeController())->getSpecialOfferProducts(3);

        $this->assertSame([$top->id, $middle->id, $bottom->id], $rail->pluck('id')->all());
    }

    public function test_the_window_widens_to_a_week_when_two_days_do_not_fill_the_rail(): void
    {
        $recent   = $this->product(['title' => 'بازدید دیروز', 'discount_percent' => 5]);
        $lastWeek = $this->product(['title' => 'بازدید پنج روز پیش', 'discount_percent' => 5]);

        $this->seedViews($recent, today()->subDay()->toDateString(), 10);
        $this->seedViews($lastWeek, today()->subDays(5)->toDateString(), 800);

        $rail = (new HomeController())->getSpecialOfferProducts(12);

        // در دو روز اخیر فقط یک قطعه بازدید دارد و ریلِ ۱۲تایی پر نمی‌شود؛
        // بازه به یک هفته می‌رود و قطعه‌ی پنج‌روزِ‌پیش صدر را می‌گیرد.
        $this->assertSame([$lastWeek->id, $recent->id], $rail->pluck('id')->all());
    }

    public function test_views_older_than_a_week_are_never_counted(): void
    {
        $fresh   = $this->product(['title' => 'بازدید امروز', 'discount_percent' => 5]);
        $ancient = $this->product(['title' => 'بازدید ده روز پیش', 'discount_percent' => 5]);

        $this->seedViews($fresh, today()->toDateString(), 3);
        $this->seedViews($ancient, today()->subDays(10)->toDateString(), 5000);

        $rail = (new HomeController())->getSpecialOfferProducts(12);

        // حتی بعد از باز شدنِ پنجره تا یک هفته، ۵۰۰۰ بازدیدِ ده‌روزِ‌پیش
        // به حساب نمی‌آید و آن قطعه ته صف می‌ماند.
        $this->assertSame([$fresh->id, $ancient->id], $rail->pluck('id')->all());
    }

    public function test_rail_accepts_any_discount_but_needs_a_picture_and_an_active_product(): void
    {
        $onePercent  = $this->product(['discount_percent' => 1]);
        $noDiscount  = $this->product(['discount_percent' => 0]);
        $noPhoto     = $this->product(['discount_percent' => 40, 'file_path' => '']);
        $inactive    = $this->product(['discount_percent' => 40, 'is_active' => false]);
        $placeholder = $this->product(['discount_percent' => 40, 'file_path' => '/images/no-image.svg']);

        // پربازدیدترین‌ها هم اگر شرط‌ها را نداشته باشند نمی‌آیند
        foreach ([$noDiscount, $noPhoto, $inactive, $placeholder] as $rejected) {
            $this->seedViews($rejected, today()->toDateString(), 500);
        }

        $rail = (new HomeController())->getSpecialOfferProducts(12);

        $this->assertSame([$onePercent->id], $rail->pluck('id')->all());
    }

    public function test_a_product_whose_photo_comes_from_the_panel_still_qualifies(): void
    {
        $product = $this->product(['discount_percent' => 20, 'file_path' => null]);
        DB::table('product_images')->insert([
            'product_id' => $product->id,
            'path'       => '/uploads/part.webp',
            'is_primary' => true,
        ]);

        $rail = (new HomeController())->getSpecialOfferProducts(12);

        $this->assertSame([$product->id], $rail->pluck('id')->all());
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
}
