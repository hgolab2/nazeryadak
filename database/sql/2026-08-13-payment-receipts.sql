-- ============================================================================
-- تغییرات دیتابیس — رسید پرداخت و تأیید/رد آن در پنل مدیریت
--
-- مایگریشن این نسخه:
--   2026_08_13_230000_add_manual_receipt_fields_to_payments_table
--
-- روی سرور می‌توانید به‌جای این فایل فقط «php artisan migrate» بزنید؛
-- این فایل برای وقتی است که دسترسی به artisan ندارید و مستقیم در
-- phpMyAdmin کار می‌کنید.
--
-- قبل از اجرا از دیتابیس بکاپ بگیرید.
-- ============================================================================


-- ---------------------------------------------------------------------------
-- ۱) ستون وضعیت پرداخت باید VARCHAR باشد
--
-- در نسخه‌های قدیمی، payments.status از نوع ENUM('init','success','failed')
-- بود. وضعیت جدید «rejected» (رسید ردشده) و «pending»/«paid» در آن ENUM جا
-- نمی‌شوند و MySQL خطای «Data truncated for column status» می‌دهد.
--
-- اول این را ببینید:
--     SHOW COLUMNS FROM `payments` LIKE 'status';
-- اگر خروجی varchar(20) بود، این بخش را رد کنید؛ اگر enum بود، هر سه دستور
-- زیر را اجرا کنید.
-- ---------------------------------------------------------------------------

-- ALTER TABLE `payments` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'pending';
-- UPDATE `payments` SET `status` = 'pending' WHERE `status` = 'init';
-- UPDATE `payments` SET `status` = 'paid'    WHERE `status` = 'success';


-- ---------------------------------------------------------------------------
-- ۲) ستون‌های رسید پرداخت دستی
--
-- جدول جدا ساخته نشد تا مدیر پرداخت درگاه و پرداخت دستی را در یک صفحه ببیند؛
-- رکورد دستی با gateway = 'manual' مشخص می‌شود.
--
--   method        روش پرداخت: card_to_card | bank_transfer | pos | cash
--   reference     شماره پیگیری/رهگیریِ خود مشتری (عمدا جدا از ref_id است،
--                 چون callback زرین‌پال رکورد را با ref_id پیدا می‌کند)
--   card_last4    چهار رقم آخر کارت مشتری
--   payer_name    نام پرداخت‌کننده
--   receipt_image مسیر تصویر رسید در /upload/receipts/سال/ماه/
--   customer_note توضیح مشتری
--   admin_note    دلیل رد کردن یا یادداشت تأیید (برای مشتری پیامک می‌شود)
--   reviewed_by   شناسه‌ی مدیری که تأیید/رد کرده (users.id)
--   reviewed_at   زمان بررسی
-- ---------------------------------------------------------------------------

ALTER TABLE `payments`
    ADD COLUMN `method`        VARCHAR(30)  COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `gateway`,
    ADD COLUMN `reference`     VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `ref_id`,
    ADD COLUMN `card_last4`    VARCHAR(4)   COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `reference`,
    ADD COLUMN `payer_name`    VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `card_last4`,
    ADD COLUMN `receipt_image` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `payer_name`,
    ADD COLUMN `customer_note` TEXT         COLLATE utf8mb4_unicode_ci NULL             AFTER `receipt_image`,
    ADD COLUMN `admin_note`    TEXT         COLLATE utf8mb4_unicode_ci NULL             AFTER `customer_note`,
    ADD COLUMN `reviewed_by`   BIGINT UNSIGNED NULL DEFAULT NULL AFTER `admin_note`,
    ADD COLUMN `reviewed_at`   TIMESTAMP    NULL DEFAULT NULL AFTER `reviewed_by`;


-- ---------------------------------------------------------------------------
-- ۳) ایندکس‌ها
--
-- صفحه‌ی «پرداخت‌ها» همیشه روی همین دو ستون فیلتر می‌زند و نشان قرمز کنار
-- منو در هر صفحه‌ی پنل یک شمارش روی status می‌گیرد.
-- ---------------------------------------------------------------------------

ALTER TABLE `payments`
    ADD INDEX `payments_status_index`  (`status`),
    ADD INDEX `payments_gateway_index` (`gateway`);


-- ---------------------------------------------------------------------------
-- ۴) جدول تنظیمات (فقط اگر از قبل نیست)
--
-- شماره کارت و شبای فروشگاه در همین جدول ذخیره می‌شوند. اگر مایگریشن
-- 2026_08_13_180000_create_settings_table روی سرور اجرا شده، این بخش را رد
-- کنید. برای بررسی:  SHOW TABLES LIKE 'settings';
-- ---------------------------------------------------------------------------

-- CREATE TABLE `settings` (
--     `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
--     `setting_key`   VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
--     `setting_value` TEXT         COLLATE utf8mb4_unicode_ci NULL,
--     `created_at`    TIMESTAMP NULL DEFAULT NULL,
--     `updated_at`    TIMESTAMP NULL DEFAULT NULL,
--     PRIMARY KEY (`id`),
--     UNIQUE KEY `settings_setting_key_unique` (`setting_key`)
-- ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- ۵) حساب فروشگاه برای کارت‌به‌کارت (اختیاری)
--
-- همین مقادیر از پنل، «تنظیمات فروشگاه ← حساب فروشگاه» هم قابل ثبت‌اند و راه
-- بهتری است، چون کش تنظیمات را هم خودش پاک می‌کند.
--
-- اگر مستقیم اینجا ثبت می‌کنید:
--   • شماره کارت را بدون فاصله و خط تیره بنویسید.
--   • بعد از اجرا حتما «php artisan cache:clear» بزنید یا فایل‌های
--     storage/framework/cache را پاک کنید؛ تنظیمات برای همیشه کش می‌شوند و
--     تا کش پاک نشود، مقدار تازه به مشتری نشان داده نمی‌شود.
-- ---------------------------------------------------------------------------

-- INSERT INTO `settings` (`setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
--     ('bank_name',         'ملت',                        NOW(), NOW()),
--     ('bank_card_number',  '6104337812345678',           NOW(), NOW()),
--     ('bank_sheba',        'IR120570028780010957775101', NOW(), NOW()),
--     ('bank_account_name', 'علی حاجی ناظری',             NOW(), NOW())
-- ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `updated_at` = NOW();


-- ---------------------------------------------------------------------------
-- ۶) ثبت مایگریشن
--
-- بدون این سطر، اجرای بعدیِ «php artisan migrate» روی سرور دوباره تلاش می‌کند
-- همین ستون‌ها را بسازد و با خطای «ستون از قبل وجود دارد» متوقف می‌شود.
-- اول این را بگیرید تا شماره‌ی batch بعدی را بدانید:
--     SELECT IFNULL(MAX(`batch`), 0) + 1 FROM `migrations`;
-- سپس عدد 99 پایین را با همان مقدار عوض کنید.
-- ---------------------------------------------------------------------------

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_08_13_230000_add_manual_receipt_fields_to_payments_table', 99);


-- ---------------------------------------------------------------------------
-- ۷) دسترسی منوی پنل
--
-- صفحه‌ی «پرداخت‌ها» از همان کد دسترسی سفارشات (۳۸۸) استفاده می‌کند و تابع
-- access() در این پروژه فعلا برای هر کاربر واردشده true برمی‌گرداند؛ پس هیچ
-- رکوردی در modules یا role_module لازم نیست.
-- ---------------------------------------------------------------------------


-- ---------------------------------------------------------------------------
-- ۸) بررسی بعد از اجرا
-- ---------------------------------------------------------------------------

-- SHOW COLUMNS FROM `payments`;
-- SELECT `id`, `order_id`, `gateway`, `method`, `amount`, `status`, `reference`
--   FROM `payments` ORDER BY `id` DESC LIMIT 10;
-- SELECT COUNT(*) AS awaiting_review FROM `payments`
--  WHERE `gateway` = 'manual' AND `status` = 'pending';


-- ============================================================================
-- برگشت (rollback)
-- ============================================================================

-- ALTER TABLE `payments`
--     DROP INDEX `payments_status_index`,
--     DROP INDEX `payments_gateway_index`,
--     DROP COLUMN `method`,
--     DROP COLUMN `reference`,
--     DROP COLUMN `card_last4`,
--     DROP COLUMN `payer_name`,
--     DROP COLUMN `receipt_image`,
--     DROP COLUMN `customer_note`,
--     DROP COLUMN `admin_note`,
--     DROP COLUMN `reviewed_by`,
--     DROP COLUMN `reviewed_at`;
-- DELETE FROM `migrations`
--  WHERE `migration` = '2026_08_13_230000_add_manual_receipt_fields_to_payments_table';
