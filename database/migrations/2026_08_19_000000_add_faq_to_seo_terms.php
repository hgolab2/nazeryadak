<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * پرسش‌های متداولِ اختصاصیِ هر صفحه‌ی فرود.
 *
 * جدا از intro/body ذخیره می‌شود چون علاوه بر نمایش، باید به FAQPage schema
 * هم تبدیل شود؛ اگر داخل HTMLِ body می‌رفت، استخراج پرسش و پاسخ از آن
 * حدس‌زدنی و شکننده می‌شد. ساختار: [{"q": "...", "a": "..."}, ...]
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_terms', function (Blueprint $table) {
            $table->text('faq')->nullable()->after('body');

            /*
            | «این متن را ماشین نوشته یا آدم؟»
            |
            | دستور seo:landing باید بتواند متن‌های تولیدیِ خودش را بازتولید
            | کند بدون اینکه متنی که مدیر دستی نوشته پاک شود. هر ذخیره از
            | پنل، این پرچم را false می‌کند و آن رکورد از آن پس دست‌نخورده
            | می‌ماند.
            */
            $table->boolean('generated')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('seo_terms', function (Blueprint $table) {
            $table->dropColumn(['faq', 'generated']);
        });
    }
};
