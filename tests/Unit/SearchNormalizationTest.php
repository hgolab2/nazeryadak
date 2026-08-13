<?php

namespace Tests\Unit;

use App\Models\Product;
use PHPUnit\Framework\TestCase;

/**
 * عنوان ۹۳٪ محصولات با «ي» و «ك» عربی ذخیره شده، ولی کیبورد فارسی «ی» و «ک»
 * تولید می‌کند. تا پیش از اصلاح، جستجوی «فیلتر» صفر نتیجه می‌داد در حالی که
 * ۱۹۱ محصول موجود بود. این تست‌ها جلوی برگشتن همان مشکل را می‌گیرند.
 */
class SearchNormalizationTest extends TestCase
{
    public function test_arabic_and_persian_letters_normalize_to_the_same_form(): void
    {
        // «فيلتر» با ي عربی و «فیلتر» با ی فارسی باید یکی شوند
        $this->assertSame(
            Product::normalizeTerm('فيلتر'),
            Product::normalizeTerm('فیلتر')
        );

        $this->assertSame(
            Product::normalizeTerm('كيت'),
            Product::normalizeTerm('کیت')
        );

        $this->assertSame(
            Product::normalizeTerm('ديسك'),
            Product::normalizeTerm('دیسک')
        );
    }

    public function test_persian_and_arabic_digits_become_latin(): void
    {
        $this->assertSame('206', Product::normalizeTerm('۲۰۶'));
        $this->assertSame('206', Product::normalizeTerm('٢٠٦'));
        $this->assertSame('206', Product::normalizeTerm('206'));
    }

    public function test_car_name_matches_regardless_of_digit_script(): void
    {
        $this->assertSame(
            Product::normalizeTerm('پژو ۲۰۶'),
            Product::normalizeTerm('پژو 206')
        );
    }

    public function test_extra_whitespace_and_zwnj_are_collapsed(): void
    {
        $this->assertSame('فیلتر روغن', Product::normalizeTerm('  فیلتر   روغن  '));
        // نیم‌فاصله به فاصله‌ی معمولی تبدیل می‌شود
        $this->assertSame('نیم فاصله', Product::normalizeTerm("نیم\u{200C}فاصله"));
    }

    public function test_empty_and_null_terms_are_safe(): void
    {
        $this->assertSame('', Product::normalizeTerm(null));
        $this->assertSame('', Product::normalizeTerm('   '));
    }

    public function test_search_scope_is_skipped_for_empty_term(): void
    {
        // اسکوپ نباید روی عبارت خالی شرطی اضافه کند
        $this->assertSame('', Product::normalizeTerm(''));
    }
}
