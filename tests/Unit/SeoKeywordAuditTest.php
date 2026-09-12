<?php

namespace Tests\Unit;

use App\Support\PageAudit;
use App\Support\SearchConsole;
use PHPUnit\Framework\TestCase;

/**
 * ممیزی کلیدواژه روی متن صفحه و خواندن CSV سرچ کنسول.
 *
 * هر دو بدون دیتابیس و بدون HTTP کار می‌کنند؛ آنچه اینجا محافظت می‌شود
 * قواعد تطبیق است: عبارت با ترتیب/املای متفاوت هم باید «هست» حساب شود،
 * و CSV با ارقام فارسی و درصد باید درست خوانده شود.
 */
class SeoKeywordAuditTest extends TestCase
{
    public function test_keyword_is_found_regardless_of_word_order_and_extra_words(): void
    {
        $this->assertTrue(PageAudit::contains('خرید لنت ترمز جلو پژو 206 | قیمت و کد فنی', 'لنت ترمز پژو 206'));
        $this->assertTrue(PageAudit::contains('پژو ۲۰۶ — لنت ترمز اصلی', 'لنت ترمز پژو 206'));
    }

    public function test_keyword_matches_across_arabic_letters_digits_and_zwnj(): void
    {
        $this->assertTrue(PageAudit::contains('كمك‌فنر عقب سمند', 'کمک فنر سمند'));
        $this->assertTrue(PageAudit::contains('فیلتر روغن پژو ۴۰۵', 'فيلتر روغن 405'));
    }

    public function test_missing_word_means_keyword_absent(): void
    {
        $this->assertFalse(PageAudit::contains('لنت ترمز جلو پژو 405', 'لنت ترمز پژو 206'));
        $this->assertFalse(PageAudit::contains('', 'لنت ترمز'));
        $this->assertFalse(PageAudit::contains(null, 'لنت ترمز'));
    }

    public function test_search_console_csv_is_parsed_with_persian_digits_and_percent(): void
    {
        $csv = "\xEF\xBB\xBF" . "Top queries,Clicks,Impressions,CTR,Position\n"
            . "\"لنت ترمز 206\",12,340,3.53%,8.2\n"
            . "\"کمک فنر سمند\",۰,۹۵,۰٪,۱۷.۸\n"
            . "\n";

        $rows = SearchConsole::parseCsv($csv);

        $this->assertCount(2, $rows);
        $this->assertSame('لنت ترمز 206', $rows[0]['query']);
        $this->assertSame(12, $rows[0]['clicks']);
        $this->assertSame(340, $rows[0]['impressions']);
        $this->assertEqualsWithDelta(0.0353, $rows[0]['ctr'], 0.0001);
        $this->assertEqualsWithDelta(8.2, $rows[0]['position'], 0.01);

        $this->assertSame(95, $rows[1]['impressions']);
        $this->assertEqualsWithDelta(17.8, $rows[1]['position'], 0.01);
    }

    public function test_pages_csv_rows_are_skipped(): void
    {
        $csv = "Top pages,Clicks,Impressions,CTR,Position\n"
            . "https://nazeryadak.ir/shop,5,100,5%,9\n";

        $this->assertSame([], SearchConsole::parseCsv($csv));
    }
}
