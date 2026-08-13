-- ============================================================================
-- تغییرات دیتابیس — ۱۳ مرداد ۱۴۰۵
--
-- دو مایگریشن این نسخه:
--   2026_08_13_140000_add_compare_at_price_to_products
--   2026_08_13_160000_create_customer_notifications_table
--
-- روی سرور می‌توانید به‌جای این فایل فقط «php artisan migrate» بزنید؛
-- این فایل برای وقتی است که دسترسی به artisan ندارید و مستقیم در
-- phpMyAdmin کار می‌کنید.
--
-- قبل از اجرا از دیتابیس بکاپ بگیرید.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- ۱) قیمت پیش از تخفیف
--
-- regular_price قیمت خرید است و نباید به مشتری نشان داده شود. این ستون قیمت
-- قبل از تخفیف را جدا نگه می‌دارد تا کارت محصول، قیمت خط‌خورده‌ی درست را
-- نمایش دهد و محاسبه‌ی تخفیف قیمت فروش را نشکند.
-- ---------------------------------------------------------------------------

ALTER TABLE `products`
    ADD COLUMN `compare_at_price` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `regular_price`;


-- ---------------------------------------------------------------------------
-- ۲) اعلان‌های داخل سایت مشتری
-- ---------------------------------------------------------------------------

CREATE TABLE `customer_notifications` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `type`        VARCHAR(40)  COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'order',
    `title`       VARCHAR(191) COLLATE utf8mb4_unicode_ci NOT NULL,
    `body`        TEXT         COLLATE utf8mb4_unicode_ci NULL,
    `url`         VARCHAR(191) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `icon`        VARCHAR(40)  COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `read_at`     TIMESTAMP NULL DEFAULT NULL,
    `created_at`  TIMESTAMP NULL DEFAULT NULL,
    `updated_at`  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `customer_notifications_customer_id_read_at_index` (`customer_id`, `read_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- ۳) ثبت مایگریشن‌ها
--
-- بدون این دو سطر، اجرای بعدیِ «php artisan migrate» روی سرور دوباره تلاش
-- می‌کند همین تغییرات را بسازد و با خطای «ستون از قبل وجود دارد» متوقف می‌شود.
-- اول این را بگیرید تا شماره‌ی batch بعدی را بدانید:
--     SELECT IFNULL(MAX(`batch`), 0) + 1 FROM `migrations`;
-- سپس عدد 99 پایین را با همان مقدار عوض کنید.
-- ---------------------------------------------------------------------------

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_08_13_140000_add_compare_at_price_to_products',    99),
    ('2026_08_13_160000_create_customer_notifications_table', 99);


-- ---------------------------------------------------------------------------
-- ۴) پاک‌سازی بنرهای آزمایشی (اختیاری)
--
-- دو بنر اسلایدر صفحه‌ی اصلی با عنوان‌های آزمایشی fsdfsd و fffffff و بازه‌ی
-- تاریخ منقضی. اگر روی سرور هم همین رکوردها هستند، پنهانشان کنید.
-- ---------------------------------------------------------------------------

-- UPDATE `advertisement` SET `hidden` = 1
--  WHERE `position` = 5 AND `title` IN ('fsdfsd', 'fffffff');


-- ============================================================================
-- برگشت (rollback)
-- ============================================================================

-- DROP TABLE IF EXISTS `customer_notifications`;
-- ALTER TABLE `products` DROP COLUMN `compare_at_price`;
-- DELETE FROM `migrations` WHERE `migration` IN (
--     '2026_08_13_140000_add_compare_at_price_to_products',
--     '2026_08_13_160000_create_customer_notifications_table'
-- );
