<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * کد تخفیف درصدی با سقف مبلغ.
 *
 * تخفیف همیشه درصدی از «مبلغ اقلام» (orders.final_price) است و هرگز روی
 * هزینه‌ی ارسال اعمال نمی‌شود؛ ارسال قاعده‌ی خودش را دارد (getShippingInfo)
 * و تخفیف‌دادن رویش یعنی فروشگاه از جیب خودش کرایه بدهد.
 *
 * max_discount سقف ریالیِ همان درصد است: «۲۰٪ تخفیف تا سقف ۵۰ هزار تومان»
 * یعنی روی فاکتور ۱ میلیونی هم فقط ۵۰ هزار تومان کم می‌شود. مقدار NULL
 * یعنی بی‌سقف.
 *
 * تعداد دفعات مصرف عمدا به صورت ستون شمارنده ذخیره نشده و هر بار از روی
 * خود سفارش‌ها شمرده می‌شود (DiscountCode::usedCount)؛ شمارنده‌ی جدا با هر
 * سفارش نیمه‌کاره یا حذف‌شده از واقعیت فاصله می‌گیرد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            // کد همیشه با حروف بزرگ لاتین ذخیره می‌شود تا «off10» و «OFF10» یکی باشند
            $table->string('code', 40)->unique();
            $table->string('title', 150)->nullable();
            $table->unsignedTinyInteger('percent');
            // سقف تخفیف به تومان؛ NULL یعنی سقفی ندارد
            $table->unsignedBigInteger('max_discount')->nullable();
            // حداقل مبلغ اقلام فاکتور برای فعال‌شدن کد (تومان)
            $table->unsignedBigInteger('min_order_amount')->default(0);
            // NULL یعنی نامحدود
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // فهرست پنل با «فعال‌ها» و «منقضی‌ها» فیلتر می‌شود
            $table->index('is_active');
            $table->index('expires_at');
        });

        Schema::table('orders', function (Blueprint $table) {
            // کد به سفارش گره می‌خورد نه به سبد، چون سبد در session است و
            // بعد از پرداخت پاک می‌شود ولی فاکتور باید تخفیفش را نگه دارد.
            $table->unsignedBigInteger('discount_code_id')->nullable()->after('final_price');
            // اسنپ‌شات متن کد؛ اگر مدیر بعدا کد را حذف کند، فاکتور قدیمی
            // همچنان می‌گوید مشتری با چه کدی خرید کرده است
            $table->string('discount_code', 40)->nullable()->after('discount_code_id');
            $table->unsignedBigInteger('discount_amount')->default(0)->after('discount_code');

            $table->index('discount_code_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['discount_code_id']);
            $table->dropColumn(['discount_code_id', 'discount_code', 'discount_amount']);
        });

        Schema::dropIfExists('discount_codes');
    }
};
