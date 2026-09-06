<?php

namespace Tests\Unit;

use App\Enums\ProductCategory;
use App\Models\Product;
use App\Models\ProductInCategory;
use App\Support\ProductDescription;
use Tests\TestCase;

/**
 * متن محصول برای گوگل ساخته می‌شود، پس دو خاصیت باید همیشه برقرار بماند:
 * یکتا بودن (وگرنه دوباره همان محتوای تکراری قبلی) و نگه‌داشتن مقاله‌ی
 * ایساکو (وگرنه بازنویسی، محتوای واقعی را می‌بلعد).
 */
class ProductDescriptionTest extends TestCase
{
    private function product(int $id, string $title, string $sku, string $car = 'پژو 206'): Product
    {
        $product = new Product();
        $product->id         = $id;
        $product->title      = $title;
        $product->sku        = $sku;
        $product->isaco_code = substr($sku, 0, 5);
        $product->car_model  = $car;

        // بدون لود کردن رابطه، categoryForSeo و isContactPrice به دیتابیس
        // می‌زنند؛ اینجا رابطه دستی نشانده می‌شود تا تست بی‌نیاز از داده باشد.
        $product->setRelation('categories', collect([
            new ProductInCategory(['category_id' => ProductCategory::ENGINE->value]),
        ]));

        return $product;
    }

    public function test_it_puts_the_product_identity_in_the_text(): void
    {
        $html = ProductDescription::build($this->product(11, 'واشر سرسیلندر TU5', '0331111111'), '');

        $this->assertStringContainsString('واشر سرسیلندر TU5', $html);
        $this->assertStringContainsString('0331111111', $html);
        $this->assertStringContainsString('03311', $html);
        $this->assertStringContainsString('پژو 206', $html);
        $this->assertStringContainsString('موتور و اجزای متعلقه', $html);
    }

    public function test_it_builds_a_structured_document_with_an_faq(): void
    {
        $html = ProductDescription::build($this->product(11, 'واشر سرسیلندر TU5', '0331111111'), '');

        $this->assertStringContainsString('<h2>مشخصات واشر سرسیلندر TU5</h2>', $html);
        $this->assertStringContainsString('<ul><li>', $html);
        $this->assertStringContainsString('<h3>قیمت واشر سرسیلندر TU5 چقدر است؟</h3>', $html);
        $this->assertStringContainsString('<a href="/contact-us">', $html);
        $this->assertGreaterThan(1500, mb_strlen(strip_tags($html)));
    }

    /**
     * همان مشکلی که این بازنویسی برای حلش نوشته شد: دو محصول از یک دسته و یک
     * خودرو نباید متن یکسان بگیرند.
     */
    public function test_two_products_of_the_same_category_do_not_share_text(): void
    {
        $first  = ProductDescription::build($this->product(11, 'واشر سرسیلندر TU5', '0331111111'), '');
        $second = ProductDescription::build($this->product(12, 'واشر سرسیلندر TU3', '0332222222'), '');

        $this->assertNotSame($first, $second);
    }

    /** مقاله‌ی ایساکو می‌ماند، ولی عنوانش یک پله پایین می‌آید. */
    public function test_it_keeps_the_isaco_article_and_demotes_its_headings(): void
    {
        $source = '<h2>واشر سرسیلندر</h2><p>واشر سرسیلندر وظیفه‌ی کمپرس کردن موتور را دارد.</p>';
        $html   = ProductDescription::build($this->product(11, 'واشر سرسیلندر TU5', '0331111111'), $source);

        $this->assertStringContainsString('<h3>واشر سرسیلندر</h3>', $html);
        $this->assertStringContainsString('وظیفه‌ی کمپرس کردن موتور را دارد', $html);
        $this->assertStringNotContainsString('<h2>واشر سرسیلندر</h2>', $html);
    }

    /** متن قالبیِ قدیمی هیچ اطلاعاتی نداشت؛ نباید به متن تازه راه پیدا کند. */
    public function test_it_drops_the_old_plain_text_boilerplate(): void
    {
        $source = 'واشر سرسیلندر TU5 در گروه قطعات مناسب موتور و اجزای متعلقه قرار می‌گیرد و برای جایگزینی قطعه فرسوده کاربرد دارد.';
        $html   = ProductDescription::build($this->product(11, 'واشر سرسیلندر TU5', '0331111111'), $source);

        $this->assertStringNotContainsString('برای جایگزینی قطعه فرسوده', $html);
    }

    /** نام‌های ایمپورت‌شده «&» دارند («موتور EF7 & EF7P»)؛ خام چاپ نشوند. */
    public function test_it_escapes_ampersands_coming_from_import(): void
    {
        $html = ProductDescription::build($this->product(11, 'دسته موتور EF7 & EF7P', '0331111111'), '');

        $this->assertStringContainsString('EF7 &amp; EF7P', $html);
        $this->assertStringNotContainsString('EF7 & EF7P', $html);
    }
}
