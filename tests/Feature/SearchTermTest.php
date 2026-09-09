<?php

namespace Tests\Feature;

use App\Models\SearchTerm;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * شمارش عبارت‌های جستجو و پیشنهادِ پرتکرارها به بقیه‌ی کاربرها.
 */
class SearchTermTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('search_terms', function (Blueprint $table) {
            $table->id();
            $table->string('term', 120)->unique();
            $table->string('display_term', 160);
            $table->unsignedBigInteger('hits')->default(0);
            $table->unsignedInteger('results_count')->default(0);
            $table->timestamp('last_searched_at')->nullable();
            $table->timestamps();
        });

        Cache::flush();
    }

    private function row(string $term): ?object
    {
        return DB::table('search_terms')->where('term', $term)->first();
    }

    public function test_the_same_term_raises_one_counter_instead_of_adding_rows(): void
    {
        SearchTerm::record('لنت ترمز', 12);
        SearchTerm::record('لنت ترمز', 12);
        SearchTerm::record('لنت ترمز', 14);

        $this->assertSame(1, DB::table('search_terms')->count());
        $this->assertSame(3, (int) $this->row('لنت ترمز')->hits);

        // آخرین تعداد نتیجه می‌نشیند، نه جمعشان: سوال «الان نتیجه دارد؟» است.
        $this->assertSame(14, (int) $this->row('لنت ترمز')->results_count);
    }

    public function test_different_spellings_of_one_term_share_a_row(): void
    {
        // «ي» و «ك» عربی و ارقام فارسی نباید ردیف جدا بسازند
        SearchTerm::record('فيلتر روغن ۲۰۶', 5);
        SearchTerm::record('فیلتر روغن 206', 5);

        $this->assertSame(1, DB::table('search_terms')->count());
        $this->assertSame(2, (int) DB::table('search_terms')->value('hits'));
    }

    public function test_a_one_letter_term_is_not_stored(): void
    {
        SearchTerm::record('ل', 900);
        SearchTerm::record('  ', 900);

        $this->assertSame(0, DB::table('search_terms')->count());
    }

    public function test_only_repeated_terms_with_results_are_suggested(): void
    {
        $this->seedTerm('لنت ترمز', SearchTerm::POPULAR_MIN_HITS, 20);
        $this->seedTerm('فیلتر روغن', SearchTerm::POPULAR_MIN_HITS + 5, 8);

        // یک بار کمتر از آستانه: کنجکاوی یک نفر هنوز «پرتکرار» نیست
        $this->seedTerm('کنجکاوی یک‌نفره', SearchTerm::POPULAR_MIN_HITS - 1, 30);

        // پرتکرار ولی بی‌نتیجه: پیشنهادی که به صفحه‌ی خالی برسد بدتر از
        // نبودنِ پیشنهاد است
        $this->seedTerm('قطعه‌ی ناموجود', 50, 0);

        $this->assertSame(['فیلتر روغن', 'لنت ترمز'], SearchTerm::popular(8));
    }

    public function test_the_default_list_fills_the_rest_without_repeating(): void
    {
        $this->seedTerm('لنت ترمز', 10, 20);

        $terms = popular_search_terms(6);

        $this->assertCount(6, $terms);
        $this->assertSame('لنت ترمز', $terms[0]);

        // «لنت ترمز» در فهرست پیش‌فرض هم هست و نباید دو بار بیاید
        $this->assertSame(1, count(array_keys($terms, 'لنت ترمز')));
    }

    public function test_the_default_list_stands_in_while_no_search_is_recorded_yet(): void
    {
        $this->assertSame(
            ['لنت ترمز', 'فیلتر روغن', 'تسمه تایم', 'شمع', 'دیسک و صفحه', 'کمک فنر'],
            popular_search_terms(6)
        );
    }

    private function seedTerm(string $term, int $hits, int $results): void
    {
        DB::table('search_terms')->insert([
            'term'             => \App\Models\Product::normalizeTerm($term),
            'display_term'     => $term,
            'hits'             => $hits,
            'results_count'    => $results,
            'last_searched_at' => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }
}
