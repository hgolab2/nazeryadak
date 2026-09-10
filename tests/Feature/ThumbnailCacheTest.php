<?php

namespace Tests\Feature;

use App\Services\ThumbnailService;
use Tests\TestCase;

/**
 * مسیر /cache/thumbs ورودیِ کاربر را مستقیم به مسیر فایل تبدیل می‌کند و
 * فایل روی دیسک می‌نویسد؛ یعنی دو خطر واقعی دارد: خواندن فایل بیرون از
 * public، و پرکردن دیسک با درخواستِ عرض‌های بی‌شمار. این تست هر دو را
 * قفل می‌کند، به‌علاوه‌ی اینکه بندانگشتی واقعا در اندازه‌ی خواسته‌شده
 * ساخته می‌شود.
 */
class ThumbnailCacheTest extends TestCase
{
    private string $relative = 'cache/__test__/sample.png';

    protected function setUp(): void
    {
        parent::setUp();

        // یک تصویر ۱۰۰۰ پیکسلی می‌سازیم تا کوچک‌سازی قابل سنجش باشد.
        $path = public_path($this->relative);
        @mkdir(dirname($path), 0755, true);

        $image = imagecreatetruecolor(1000, 1000);
        imagefill($image, 0, 0, imagecolorallocate($image, 10, 120, 200));
        imagepng($image, $path);
        imagedestroy($image);
    }

    protected function tearDown(): void
    {
        @unlink(public_path($this->relative));

        foreach (ThumbnailService::SIZES as $size) {
            @unlink(public_path(ThumbnailService::CACHE_DIR . "/{$size}/{$this->relative}.webp"));
        }

        parent::tearDown();
    }

    public function test_thumbnail_is_built_at_the_requested_size(): void
    {
        $file = ThumbnailService::generate($this->relative, 300);

        $this->assertNotNull($file, 'بندانگشتی ساخته نشد');
        $this->assertSame([300, 300], array_slice(getimagesize($file), 0, 2));
        $this->assertSame('image/webp', getimagesize($file)['mime']);

        // نکته‌ی اصلی همین است: باید از اصل تصویر بسیار سبک‌تر باشد.
        $this->assertLessThan(filesize(public_path($this->relative)), filesize($file));
    }

    public function test_request_writes_the_file_so_apache_serves_it_next_time(): void
    {
        $url = '/' . ThumbnailService::CACHE_DIR . '/300/' . $this->relative . '.webp';

        $this->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/webp');

        $this->assertFileExists(public_path(ltrim($url, '/')));
    }

    public function test_original_image_is_never_touched(): void
    {
        $before = md5_file(public_path($this->relative));

        ThumbnailService::generate($this->relative, 160);

        $this->assertSame($before, md5_file(public_path($this->relative)));
    }

    /**
     * عرضِ خارج از لیست باید رد شود؛ وگرنه یک ربات با /cache/thumbs/1/…
     * تا /cache/thumbs/9999/… از هر تصویر هزاران نسخه می‌سازد.
     */
    public function test_unlisted_width_is_rejected(): void
    {
        foreach ([1, 42, 301, 1200, 99999] as $width) {
            $this->get('/' . ThumbnailService::CACHE_DIR . "/{$width}/{$this->relative}.webp")
                ->assertNotFound();
        }
    }

    /** هیچ مسیری نباید بتواند از public بیرون بزند یا فایل غیرتصویری بخواند. */
    public function test_paths_outside_public_and_non_images_are_rejected(): void
    {
        $bad = [
            '/cache/thumbs/300/../../../.env.webp',
            '/cache/thumbs/300/upload/..%2f..%2f.env.webp',
            '/cache/thumbs/300/index.php.webp',
            '/cache/thumbs/300/../.env.webp',
            '/cache/thumbs/300/upload/products/does-not-exist.jpg.webp',
        ];

        foreach ($bad as $url) {
            $this->get($url)->assertNotFound();
        }

        $this->assertNull(ThumbnailService::safeRelative('../.env'));
        $this->assertNull(ThumbnailService::safeRelative('index.php'));
        $this->assertNull(ThumbnailService::safeRelative(''));
    }

    /**
     * وقتی تصویر روی همان مسیر بازنویسی می‌شود، بندانگشتی باید برود؛
     * وگرنه آپاچی تا یک سال نسخه‌ی قدیمی را سرو می‌کند و تغییرِ عکس
     * هیچ‌وقت به کاربر نمی‌رسد.
     */
    public function test_forget_removes_every_cached_size(): void
    {
        foreach (ThumbnailService::SIZES as $size) {
            $this->assertNotNull(ThumbnailService::generate($this->relative, $size));
        }

        ThumbnailService::forget($this->relative);

        foreach (ThumbnailService::SIZES as $size) {
            $this->assertFileDoesNotExist(
                public_path(ThumbnailService::CACHE_DIR . "/{$size}/{$this->relative}.webp")
            );
        }
    }

    /** آدرس باید فقط برای تصویرِ محلیِ موجود ساخته شود. */
    public function test_url_helper_only_covers_local_existing_images(): void
    {
        $this->assertNotNull(thumb_url('/' . $this->relative, 300));

        $this->assertNull(thumb_url('https://example.com/a.jpg', 300));
        $this->assertNull(thumb_url('/images/no-image.svg', 300));
        $this->assertNull(thumb_url('/upload/products/missing.jpg', 300));
        $this->assertNull(thumb_url('/' . $this->relative, 301));
        $this->assertNull(thumb_url(null, 300));

        $this->assertStringContainsString(' 1x, ', thumb_srcset('/' . $this->relative, 300, 600));
        $this->assertNull(thumb_srcset('/images/no-image.svg', 300, 600));
    }
}
