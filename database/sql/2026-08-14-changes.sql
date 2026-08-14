-- ============================================================================
-- تغییرات دیتابیس — نسخه‌ی ۲۳ مرداد ۱۴۰۵
--
-- ادامه‌ی فایل 2026-08-13-changes.sql (که compare_at_price و
-- customer_notifications را داشت). این فایل بقیه‌ی مایگریشن‌های همین دوره را
-- پوشش می‌دهد:
--
--   2026_08_13_180000_add_online_payment_setting
--   2026_08_13_180000_create_settings_table
--   2026_08_13_200000_add_seo_fields_to_products
--   2026_08_13_210000_create_redirects_and_not_found_logs_tables
--   2026_08_13_220000_create_seo_terms_table
--   2026_08_13_230000_add_manual_receipt_fields_to_payments_table
--   2026_08_13_230000_create_product_reviews_table
--   2026_08_14_000000_add_wholesale_fields_to_products
--
-- روی سرور می‌توانید به‌جای این فایل فقط «php artisan migrate» بزنید؛ این فایل
-- برای وقتی است که دسترسی به artisan ندارید و مستقیم در phpMyAdmin کار می‌کنید.
--
-- نکته: اصلاح‌های ظاهریِ جستجو (پنل پیشنهاد هدر و لیست خودرو) هیچ تغییر
-- دیتابیسی ندارند؛ فقط CSS و JS هستند.
--
-- ترتیب اجرا مهم است: بخش ۴ ستون‌ها را «بعد از compare_at_price» می‌گذارد،
-- پس فایل قبلی باید اجرا شده باشد.
--
-- قبل از اجرا از دیتابیس بکاپ بگیرید.
-- ============================================================================


-- ---------------------------------------------------------------------------
-- ۱) جدول تنظیمات عمومی سایت
--
-- کلید-مقدارِ ساده: مشخصات تماس، متن پیامک‌ها و شماره حساب فروشگاه.
-- ---------------------------------------------------------------------------

CREATE TABLE `settings` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key`   VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `setting_value` TEXT         COLLATE utf8mb4_unicode_ci NULL,
    `created_at`    TIMESTAMP NULL DEFAULT NULL,
    `updated_at`    TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `settings_setting_key_unique` (`setting_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- ۲) کلید روشن/خاموش پرداخت آنلاین
--
-- پیش‌فرض خاموش است: سفارش‌ها با پیش‌فاکتور و تماس کارشناس نهایی می‌شوند.
-- ---------------------------------------------------------------------------

INSERT INTO `shipping_settings` (`setting_key`, `setting_value`, `description`, `created_at`, `updated_at`)
SELECT 'online_payment_enabled', '0', 'فعال بودن پرداخت آنلاین (۱ فعال / ۰ غیرفعال)', NOW(), NOW()
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `shipping_settings` WHERE `setting_key` = 'online_payment_enabled');


-- ---------------------------------------------------------------------------
-- ۳) فیلدهای سئوی دستی محصول
--
-- تا پیش از این عنوان و توضیحات متای صفحه‌ی محصول فقط خودکار ساخته می‌شد و
-- مدیر راهی برای بازنویسی نداشت. همه nullable‌اند، پس محصولات فعلی دقیقا مثل
-- قبل رفتار می‌کنند.
-- ---------------------------------------------------------------------------

ALTER TABLE `products`
    ADD COLUMN `seo_title`       VARCHAR(191) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `car_model`,
    ADD COLUMN `seo_description` VARCHAR(500) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `seo_title`,
    ADD COLUMN `focus_keyword`   VARCHAR(191) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `seo_description`,
    ADD COLUMN `canonical_url`   VARCHAR(500) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `focus_keyword`,
    ADD COLUMN `robots_index`    TINYINT(1) NOT NULL DEFAULT 1 AFTER `canonical_url`,
    ADD COLUMN `robots_follow`   TINYINT(1) NOT NULL DEFAULT 1 AFTER `robots_index`;


-- ---------------------------------------------------------------------------
-- ۴) فیلدهای فروش عمده‌ی محصول
--
-- خالی بودن یعنی «خودکار حساب کن»؛ آستانه‌ی تعداد و قیمت عمده از روی قیمت خود
-- محصول به‌دست می‌آید، پس برای محصولات فعلی چیزی لازم نیست پر شود.
-- ---------------------------------------------------------------------------

ALTER TABLE `products`
    ADD COLUMN `wholesale_min_qty` INT UNSIGNED NULL DEFAULT NULL AFTER `compare_at_price`,
    ADD COLUMN `wholesale_price`   INT UNSIGNED NULL DEFAULT NULL AFTER `wholesale_min_qty`,
    ADD COLUMN `wholesale_enabled` TINYINT(1) NOT NULL DEFAULT 1 AFTER `wholesale_price`;


-- ---------------------------------------------------------------------------
-- ۵) ریدایرکت‌ها و مانیتور خطای ۴۰۴
--
-- همان دو ابزاری که در Rank Math زیر Redirections و 404 Monitor هستند.
-- ---------------------------------------------------------------------------

CREATE TABLE `redirects` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `source_path` VARCHAR(191) COLLATE utf8mb4_unicode_ci NOT NULL,
    `target_path` VARCHAR(500) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `status_code` SMALLINT UNSIGNED NOT NULL DEFAULT 301,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `hits`        INT UNSIGNED NOT NULL DEFAULT 0,
    `last_hit_at` TIMESTAMP NULL DEFAULT NULL,
    `note`        VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `created_at`  TIMESTAMP NULL DEFAULT NULL,
    `updated_at`  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `redirects_source_path_unique` (`source_path`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `not_found_logs` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `path`         VARCHAR(191) COLLATE utf8mb4_unicode_ci NOT NULL,
    `referer`      VARCHAR(500) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `user_agent`   VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `hits`         INT UNSIGNED NOT NULL DEFAULT 1,
    `last_seen_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at`   TIMESTAMP NULL DEFAULT NULL,
    `updated_at`   TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `not_found_logs_path_unique` (`path`),
    KEY `not_found_logs_last_seen_at_index` (`last_seen_at`),
    KEY `not_found_logs_hits_index` (`hits`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- ۶) صفحات فرود سئو (دسته و خودرو)
-- ---------------------------------------------------------------------------

CREATE TABLE `seo_terms` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type`            VARCHAR(20)  COLLATE utf8mb4_unicode_ci NOT NULL,
    `slug`            VARCHAR(160) COLLATE utf8mb4_unicode_ci NOT NULL,
    `name`            VARCHAR(191) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `heading`         VARCHAR(191) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `intro`           TEXT         COLLATE utf8mb4_unicode_ci NULL,
    `body`            TEXT         COLLATE utf8mb4_unicode_ci NULL,
    `seo_title`       VARCHAR(191) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `seo_description` VARCHAR(500) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `focus_keyword`   VARCHAR(191) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `robots_index`    TINYINT(1) NOT NULL DEFAULT 1,
    `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`      TIMESTAMP NULL DEFAULT NULL,
    `updated_at`      TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `seo_terms_type_slug_unique` (`type`, `slug`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- ۷) نظرات و امتیاز محصولات
--
-- نظر تا تأیید مدیر در وضعیت pending می‌ماند و روی صفحه‌ی محصول دیده نمی‌شود.
-- ---------------------------------------------------------------------------

CREATE TABLE `product_reviews` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id`  BIGINT UNSIGNED NOT NULL,
    `customer_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `name`        VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `rating`      TINYINT UNSIGNED NOT NULL,
    `title`       VARCHAR(191) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `comment`     TEXT         COLLATE utf8mb4_unicode_ci NOT NULL,
    `status`      VARCHAR(20)  COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
    `is_buyer`    TINYINT(1) NOT NULL DEFAULT 0,
    `ip`          VARCHAR(45)  COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `created_at`  TIMESTAMP NULL DEFAULT NULL,
    `updated_at`  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `product_reviews_product_id_status_index` (`product_id`, `status`),
    KEY `product_reviews_status_index` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- ۸) رسید پرداخت دستی روی جدول payments
--
-- اگر فایل 2026-08-13-payment-receipts.sql را قبلا اجرا کرده‌اید، این بخش و
-- سطر مربوطه در بخش ۹ را رد کنید.
--
-- اگر payments.status هنوز ENUM قدیمی است، اول این را بزنید وگرنه وضعیت
-- «rejected» ذخیره نمی‌شود:
--     ALTER TABLE `payments` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'pending';
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

ALTER TABLE `payments`
    ADD INDEX `payments_status_index`  (`status`),
    ADD INDEX `payments_gateway_index` (`gateway`);


-- ---------------------------------------------------------------------------
-- ۹) ثبت مایگریشن‌ها
--
-- بدون این سطرها، اجرای بعدیِ «php artisan migrate» روی سرور دوباره تلاش
-- می‌کند همین‌ها را بسازد و با خطای «از قبل وجود دارد» متوقف می‌شود.
-- اول شماره‌ی batch بعدی را بگیرید:
--     SELECT IFNULL(MAX(`batch`), 0) + 1 FROM `migrations`;
-- سپس عدد 99 را با همان مقدار عوض کنید.
-- ---------------------------------------------------------------------------

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_08_13_180000_add_online_payment_setting',                   99),
    ('2026_08_13_180000_create_settings_table',                        99),
    ('2026_08_13_200000_add_seo_fields_to_products',                   99),
    ('2026_08_13_210000_create_redirects_and_not_found_logs_tables',   99),
    ('2026_08_13_220000_create_seo_terms_table',                       99),
    ('2026_08_13_230000_add_manual_receipt_fields_to_payments_table',  99),
    ('2026_08_13_230000_create_product_reviews_table',                 99),
    ('2026_08_14_000000_add_wholesale_fields_to_products',             99);


-- ---------------------------------------------------------------------------
-- ۱۰) بررسی بعد از اجرا
-- ---------------------------------------------------------------------------

-- SHOW TABLES LIKE 'settings';
-- SHOW TABLES LIKE 'redirects';
-- SHOW TABLES LIKE 'not_found_logs';
-- SHOW TABLES LIKE 'seo_terms';
-- SHOW TABLES LIKE 'product_reviews';
-- SHOW COLUMNS FROM `products` LIKE 'seo\_%';
-- SHOW COLUMNS FROM `products` LIKE 'wholesale\_%';
-- SHOW COLUMNS FROM `payments`;
-- SELECT * FROM `shipping_settings` WHERE `setting_key` = 'online_payment_enabled';


-- ============================================================================
-- برگشت (rollback)
-- ============================================================================

-- DROP TABLE IF EXISTS `product_reviews`;
-- DROP TABLE IF EXISTS `seo_terms`;
-- DROP TABLE IF EXISTS `not_found_logs`;
-- DROP TABLE IF EXISTS `redirects`;
-- DROP TABLE IF EXISTS `settings`;
-- DELETE FROM `shipping_settings` WHERE `setting_key` = 'online_payment_enabled';
-- ALTER TABLE `products`
--     DROP COLUMN `seo_title`, DROP COLUMN `seo_description`, DROP COLUMN `focus_keyword`,
--     DROP COLUMN `canonical_url`, DROP COLUMN `robots_index`, DROP COLUMN `robots_follow`,
--     DROP COLUMN `wholesale_min_qty`, DROP COLUMN `wholesale_price`, DROP COLUMN `wholesale_enabled`;
-- ALTER TABLE `payments`
--     DROP INDEX `payments_status_index`, DROP INDEX `payments_gateway_index`,
--     DROP COLUMN `method`, DROP COLUMN `reference`, DROP COLUMN `card_last4`,
--     DROP COLUMN `payer_name`, DROP COLUMN `receipt_image`, DROP COLUMN `customer_note`,
--     DROP COLUMN `admin_note`, DROP COLUMN `reviewed_by`, DROP COLUMN `reviewed_at`;
-- DELETE FROM `migrations` WHERE `migration` IN (
--     '2026_08_13_180000_add_online_payment_setting',
--     '2026_08_13_180000_create_settings_table',
--     '2026_08_13_200000_add_seo_fields_to_products',
--     '2026_08_13_210000_create_redirects_and_not_found_logs_tables',
--     '2026_08_13_220000_create_seo_terms_table',
--     '2026_08_13_230000_add_manual_receipt_fields_to_payments_table',
--     '2026_08_13_230000_create_product_reviews_table',
--     '2026_08_14_000000_add_wholesale_fields_to_products'
-- );
