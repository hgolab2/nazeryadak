-- ============================================================================
-- حسابداری فروشگاه و ردگیری ارسال سفارش به بله — تغییرات دیتابیس
--
-- معادل دستیِ مهاجرت:
--   database/migrations/2026_09_11_000000_add_finance_and_bale_fields.php
--
-- چرا دستی: روی سرور دو مهاجرت معلقِ قدیمی وجود دارد که اجرای
-- «php artisan migrate» روی آن‌ها می‌شکند و نوبت به این یکی نمی‌رسد.
--
-- اجرا:
--   mysql -u USER -p DBNAME < 2026_09_11_finance_and_bale.sql
--
-- امن برای اجرای دوباره نیست: اگر ستون‌ها از قبل باشند گام ۱ خطای
-- «Duplicate column name» می‌دهد. اول با گام ۰ بررسی کنید.
-- ============================================================================


-- ---------------------------------------------------------------------------
-- گام ۰) بررسی: آیا از قبل هست؟ (اگر هر کدام خروجی داشت، ادامه ندهید)
-- ---------------------------------------------------------------------------
SHOW COLUMNS FROM `products`    LIKE 'cost_price';
SHOW COLUMNS FROM `order_items` LIKE 'unit_cost';
SHOW COLUMNS FROM `orders`      LIKE 'paid_at';
SHOW TABLES LIKE 'finance_transactions';


-- ---------------------------------------------------------------------------
-- گام ۱) ستون‌های جدید
--
-- products.cost_price      قیمت خرید قطعه (تومان)؛ ایمپورت اکسل پرش می‌کند
-- order_items.unit_cost    قیمت خرید در لحظه‌ی سفارش؛ با تغییر قیمت بعدی
--                          محصول عوض نمی‌شود تا سود هر سفارش ثابت بماند
-- orders.paid_at           لحظه‌ی تسویه؛ مبنای گزارش درآمد ماهانه
-- orders.bale_notified_at  خالی یعنی «هنوز به بله نرسیده»؛ پنل و دستور
--                          bale:resend-orders همین‌ها را دوباره می‌فرستند
-- ---------------------------------------------------------------------------
ALTER TABLE `products`
    ADD COLUMN `cost_price` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `price`;

ALTER TABLE `order_items`
    ADD COLUMN `unit_cost` INT NULL DEFAULT NULL AFTER `unit_price`;

ALTER TABLE `orders`
    ADD COLUMN `paid_at`          TIMESTAMP NULL DEFAULT NULL AFTER `status`,
    ADD COLUMN `bale_notified_at` TIMESTAMP NULL DEFAULT NULL AFTER `paid_at`;


-- ---------------------------------------------------------------------------
-- گام ۲) دفتر درآمد و هزینه
--
-- type      income | expense
-- category  کلید دسته (server, sms, shipping, packaging, ads, ... )؛ فهرست
--           در App\Models\FinanceTransaction::CATEGORIES
-- order_id  اگر هزینه بابت یک سفارش خاص است (پست، بسته‌بندی) پر می‌شود و در
--           سود همان سفارش کسر می‌شود؛ خالی یعنی هزینه‌ی عمومی (سرور، پیامک)
--
-- درآمد فروش سفارش‌ها اینجا ثبت نمی‌شود؛ از خود جدول orders خوانده می‌شود.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `finance_transactions` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type`        VARCHAR(10)     NOT NULL,
    `category`    VARCHAR(40)     NOT NULL,
    `title`       VARCHAR(190)    NOT NULL,
    `amount`      BIGINT UNSIGNED NOT NULL,
    `order_id`    BIGINT UNSIGNED NULL DEFAULT NULL,
    `occurred_on` DATE            NOT NULL,
    `note`        TEXT            NULL,
    `created_by`  BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at`  TIMESTAMP       NULL DEFAULT NULL,
    `updated_at`  TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `finance_transactions_type_occurred_on_index` (`type`, `occurred_on`),
    KEY `finance_transactions_order_id_index` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- گام ۳) پرکردن داده‌های موجود
--
-- ۳-الف) قیمت خرید محصولات از روی قیمت فروش بازسازی می‌شود: قیمت سایت همان
-- قیمت اکسل × ۱.۲ است و پاداش تصادفی ایمپورت روی قیمتِ پیش از تخفیف نشسته
-- (همان فرمول Product::autoWholesalePrice). از این به بعد ایمپورت خودش این
-- ستون را پر می‌کند.
-- ---------------------------------------------------------------------------
UPDATE `products`
SET `cost_price` = ROUND(
    COALESCE(NULLIF(`compare_at_price`, 0), `price`) * 100 / (100 + COALESCE(`import_bonus_percent`, 0)) / 1.2
)
WHERE `cost_price` IS NULL AND `price` > 0;

-- ۳-ب) اقلام سفارش‌های قدیمی: قیمت خرید امروزِ همان محصول؛ اگر محصول حذف
-- شده، از قیمت فروش همان قلم برآورد می‌شود
UPDATE `order_items` oi
LEFT JOIN `products` p ON p.`id` = oi.`product_id`
SET oi.`unit_cost` = COALESCE(p.`cost_price`, ROUND(oi.`unit_price` / 1.2))
WHERE oi.`unit_cost` IS NULL AND oi.`unit_price` > 0;

-- ۳-ج) سفارش‌های تسویه‌شده‌ی فعلی: تاریخ پرداخت از رکورد پرداخت، وگرنه
-- آخرین تغییر سفارش
UPDATE `orders` o
LEFT JOIN (
    SELECT `order_id`, MIN(`paid_at`) AS `paid_at`
    FROM `payments`
    WHERE `status` = 'paid' AND `paid_at` IS NOT NULL
    GROUP BY `order_id`
) pay ON pay.`order_id` = o.`id`
SET o.`paid_at` = COALESCE(pay.`paid_at`, o.`updated_at`)
WHERE o.`paid_at` IS NULL
  AND o.`status` IN ('paid', 'processing', 'shipped', 'delivered');

-- ۳-د) سفارش‌های قدیمی «ارسال‌شده به بله» فرض می‌شوند؛ وگرنه اولین اجرای
-- «ارسال دوباره» همه‌ی تاریخچه را در بله می‌ریخت
UPDATE `orders`
SET `bale_notified_at` = `created_at`
WHERE `bale_notified_at` IS NULL;


-- ---------------------------------------------------------------------------
-- گام ۴) ثبت در دفتر مهاجرت‌ها
--
-- لازم است وگرنه هر بار که بعدا «php artisan migrate» اجرا شود، دوباره
-- سراغ همین تغییر می‌رود و با «Duplicate column name» می‌شکند.
-- ---------------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_11_000000_add_finance_and_bale_fields',
       COALESCE(MAX(`batch`), 0) + 1
FROM (SELECT `batch` FROM `migrations`) AS `current_batches`;


-- ---------------------------------------------------------------------------
-- گام ۵) بررسی نهایی
-- ---------------------------------------------------------------------------
SELECT
    (SELECT COUNT(*) FROM `products`    WHERE `cost_price` IS NOT NULL)        AS `محصول با قیمت خرید`,
    (SELECT COUNT(*) FROM `order_items` WHERE `unit_cost`  IS NOT NULL)        AS `قلم با قیمت خرید`,
    (SELECT COUNT(*) FROM `orders`      WHERE `paid_at`    IS NOT NULL)        AS `سفارش تسویه‌شده`,
    (SELECT COUNT(*) FROM `orders`      WHERE `bale_notified_at` IS NULL)      AS `سفارش ارسال‌نشده به بله (باید ۰ باشد)`,
    (SELECT COUNT(*) FROM `finance_transactions`)                              AS `تراکنش مالی`;


-- ============================================================================
-- برگرداندن (فقط در صورت نیاز)
-- ============================================================================
-- DROP TABLE IF EXISTS `finance_transactions`;
-- ALTER TABLE `orders`      DROP COLUMN `paid_at`, DROP COLUMN `bale_notified_at`;
-- ALTER TABLE `order_items` DROP COLUMN `unit_cost`;
-- ALTER TABLE `products`    DROP COLUMN `cost_price`;
-- DELETE FROM `migrations`
--  WHERE `migration` = '2026_09_11_000000_add_finance_and_bale_fields';
