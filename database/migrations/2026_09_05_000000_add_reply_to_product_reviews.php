<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * پاسخ فروشگاه به نظر.
 *
 * خریدار قطعه‌ی یدکی اغلب زیر نظرش سوال می‌پرسد — «به تیپ ۵ هم می‌خورد؟»،
 * «اصل است یا طرح؟». تا امروز جایی برای پاسخ نبود و آن سوال بی‌جواب روی
 * صفحه می‌ماند؛ هم خریدار بعدی را بلاتکلیف می‌گذارد هم نشان می‌دهد فروشنده
 * صفحه را رها کرده است.
 *
 * پاسخ به‌جای یک ردیف جدا، ستون خودِ نظر است: هر نظر حداکثر یک پاسخ دارد و
 * همیشه با همان نظر خوانده و نمایش داده می‌شود، پس جدول جدا فقط یک join
 * اضافه می‌شد بی‌آنکه چیزی حل کند.
 *
 * replied_at جداست چون از created_atِ نظر جدا حرکت می‌کند و فاصله‌ی
 * «سوال تا پاسخ» همان چیزی است که در پنل به کار پیگیری می‌آید.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('product_reviews', 'reply')) {
                $table->text('reply')->nullable()->after('comment');
            }
            if (! Schema::hasColumn('product_reviews', 'replied_at')) {
                $table->timestamp('replied_at')->nullable()->after('reply');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            foreach (['reply', 'replied_at'] as $column) {
                if (Schema::hasColumn('product_reviews', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
