<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * امتیاز مجموعه‌بندی‌شده‌ی نظرات: ذخیره‌سازی، پاک‌سازی ورودی و میانگین معیارها.
 *
 * مثل بقیه‌ی تست‌های محصول، نسخه‌ی کوچکی از جدول‌ها روی sqlite حافظه‌ای ساخته
 * می‌شود چون جدول‌های واقعی از دیتابیس قدیمی آمده‌اند و مایگریشن کامل ندارند.
 */
class ProductReviewCriteriaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Product::flushContactPriceCache();
        ProductReview::flushCriteriaSupportCache();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('price')->default(0);
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('wholesale_enabled')->default(true);
            $table->decimal('rating_avg', 3, 2)->nullable();
            $table->unsignedInteger('rating_count')->default(0);
            $table->timestamps();
        });

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('name', 100);
            $table->unsignedTinyInteger('rating');
            $table->json('criteria')->nullable();
            $table->string('title')->nullable();
            $table->text('comment');
            $table->string('status', 20)->default('pending');
            $table->boolean('is_buyer')->default(false);
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        ProductReview::flushCriteriaSupportCache();
        parent::tearDown();
    }

    private function product(): Product
    {
        return Product::create([
            'title'     => 'لنت ترمز آزمایشی',
            'price'     => 1000000,
            'stock'     => 10,
            'is_active' => true,
        ]);
    }

    private function review(int $productId, int $rating, ?array $criteria, string $status = ProductReview::STATUS_APPROVED): ProductReview
    {
        return ProductReview::create([
            'product_id' => $productId,
            'name'       => 'کاربر آزمایشی',
            'rating'     => $rating,
            'criteria'   => $criteria,
            'comment'    => 'متن نظر آزمایشی برای این قطعه.',
            'status'     => $status,
        ]);
    }

    public function test_unknown_keys_and_out_of_range_scores_are_dropped(): void
    {
        $clean = ProductReview::sanitizeCriteria([
            'quality' => '5',
            'value'   => 0,      // خارج از بازه
            'fitment' => 9,      // خارج از بازه
            'hacked'  => 5,      // معیار ناشناخته
        ]);

        $this->assertSame(['quality' => 5], $clean);
    }

    public function test_empty_or_non_array_input_becomes_null(): void
    {
        $this->assertNull(ProductReview::sanitizeCriteria(null));
        $this->assertNull(ProductReview::sanitizeCriteria('5'));
        $this->assertNull(ProductReview::sanitizeCriteria([]));
        $this->assertNull(ProductReview::sanitizeCriteria(['quality' => '']));
    }

    public function test_form_submission_stores_the_criteria_scores(): void
    {
        $product = $this->product();

        $this->post("/product/{$product->id}/review", [
            'name'     => 'رضا',
            'rating'   => 4,
            'comment'  => 'روی پژو ۲۰۶ بدون مشکل جا رفت و صدا نمی‌دهد.',
            'criteria' => ['quality' => 5, 'value' => 3, 'hacked' => 5],
        ]);

        $review = ProductReview::first();

        $this->assertNotNull($review);
        $this->assertSame(ProductReview::STATUS_PENDING, $review->status);
        $this->assertSame(['quality' => 5, 'value' => 3], $review->criteria);
    }

    public function test_review_without_criteria_is_still_accepted(): void
    {
        $product = $this->product();

        $this->post("/product/{$product->id}/review", [
            'name'    => 'رضا',
            'rating'  => 5,
            'comment' => 'کیفیت ساخت خوبی دارد و سریع رسید.',
        ]);

        $review = ProductReview::first();

        $this->assertNotNull($review);
        $this->assertNull($review->criteria);
    }

    public function test_out_of_range_criterion_is_rejected_before_saving(): void
    {
        $product = $this->product();

        $this->post("/product/{$product->id}/review", [
            'name'     => 'رضا',
            'rating'   => 4,
            'comment'  => 'کیفیت قطعه در حد قیمتش قابل قبول است.',
            'criteria' => ['quality' => 8],
        ])->assertSessionHasErrors('criteria.quality');

        $this->assertSame(0, ProductReview::count());
    }

    /**
     * میانگینِ هر معیار فقط از نظرهایی گرفته می‌شود که به همان معیار امتیاز
     * داده‌اند؛ وگرنه معیاری که دو نفر از پنج نفر پرش کرده‌اند، مصنوعی پایین
     * می‌آمد.
     */
    public function test_criterion_average_ignores_reviews_that_skipped_it(): void
    {
        $product = $this->product();

        $this->review($product->id, 5, ['quality' => 5, 'value' => 4]);
        $this->review($product->id, 3, ['quality' => 2]);
        $this->review($product->id, 4, null);

        $summary = $product->fresh()->ratingSummary();

        $this->assertSame(3, $summary['count']);
        $this->assertSame(4.0, $summary['avg']);
        $this->assertSame(3.5, $summary['criteria']['quality']['avg']);
        $this->assertSame(2, $summary['criteria']['quality']['count']);
        $this->assertSame(4.0, $summary['criteria']['value']['avg']);
        $this->assertSame(1, $summary['criteria']['value']['count']);
        $this->assertSame(0, $summary['criteria']['durability']['count']);
    }

    public function test_pending_reviews_do_not_reach_the_summary(): void
    {
        $product = $this->product();

        $this->review($product->id, 5, ['quality' => 5]);
        $this->review($product->id, 1, ['quality' => 1], ProductReview::STATUS_PENDING);

        $summary = $product->fresh()->ratingSummary();

        $this->assertSame(1, $summary['count']);
        $this->assertSame(5.0, $summary['criteria']['quality']['avg']);
    }

    public function test_star_distribution_counts_each_rating(): void
    {
        $product = $this->product();

        $this->review($product->id, 5, null);
        $this->review($product->id, 5, null);
        $this->review($product->id, 3, null);

        $summary = $product->fresh()->ratingSummary();

        $this->assertSame(2, $summary['distribution'][5]['count']);
        $this->assertSame(1, $summary['distribution'][3]['count']);
        $this->assertSame(0, $summary['distribution'][1]['count']);
        $this->assertEqualsWithDelta(66.7, $summary['distribution'][5]['percent'], 0.05);
    }
}
