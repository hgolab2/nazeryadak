-- ============================================================================
-- کلیدواژه‌های هدف سئو و داده‌ی خام سرچ کنسول — تغییرات دیتابیس
--
-- معادل دستیِ مهاجرت:
--   database/migrations/2026_09_12_000000_create_seo_keywords_tables.php
--
-- چرا دستی: روی سرور مهاجرت‌های معلقِ قدیمی «php artisan migrate» را
-- می‌شکنند و نوبت به این یکی نمی‌رسد.
--
-- اجرا:
--   mysql -u USER -p DBNAME < 2026_09_12_seo_keywords.sql
--
-- امن برای اجرای دوباره: CREATE TABLE IF NOT EXISTS.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `seo_keywords` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `keyword`          VARCHAR(160) NOT NULL,
  `term`             VARCHAR(120) NOT NULL,
  `target_url`       VARCHAR(500) NULL,
  `priority`         TINYINT UNSIGNED NOT NULL DEFAULT 2,
  `note`             TEXT NULL,
  `http_status`      SMALLINT UNSIGNED NULL,
  `page_title`       VARCHAR(300) NULL,
  `page_h1`          VARCHAR(300) NULL,
  `page_description` VARCHAR(500) NULL,
  `in_title`         TINYINT(1) NULL,
  `in_h1`            TINYINT(1) NULL,
  `in_description`   TINYINT(1) NULL,
  `body_hits`        SMALLINT UNSIGNED NULL,
  `is_indexable`     TINYINT(1) NULL,
  `checked_at`       TIMESTAMP NULL,
  `gsc_clicks`       INT UNSIGNED NULL,
  `gsc_impressions`  INT UNSIGNED NULL,
  `gsc_position`     DECIMAL(6,2) NULL,
  `gsc_ctr`          DECIMAL(6,4) NULL,
  `gsc_top_page`     VARCHAR(500) NULL,
  `gsc_synced_at`    TIMESTAMP NULL,
  `created_at`       TIMESTAMP NULL,
  `updated_at`       TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seo_keywords_term_unique` (`term`),
  KEY `seo_keywords_priority_gsc_impressions_index` (`priority`, `gsc_impressions`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gsc_queries` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `query`        VARCHAR(200) NOT NULL,
  `term`         VARCHAR(120) NOT NULL,
  `page`         VARCHAR(500) NOT NULL DEFAULT '',
  `clicks`       INT UNSIGNED NOT NULL DEFAULT 0,
  `impressions`  INT UNSIGNED NOT NULL DEFAULT 0,
  `ctr`          DECIMAL(6,4) NOT NULL DEFAULT 0,
  `position`     DECIMAL(6,2) NOT NULL DEFAULT 0,
  `period_days`  SMALLINT UNSIGNED NOT NULL DEFAULT 28,
  `synced_at`    TIMESTAMP NULL,
  `created_at`   TIMESTAMP NULL,
  `updated_at`   TIMESTAMP NULL,
  `page_hash`    CHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gsc_queries_term_page_hash_unique` (`term`, `page_hash`),
  KEY `gsc_queries_impressions_index` (`impressions`),
  KEY `gsc_queries_clicks_index` (`clicks`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
