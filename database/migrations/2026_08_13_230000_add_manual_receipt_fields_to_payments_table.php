<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ثبت «رسید پرداخت دستی» روی همان جدول payments.
 *
 * پرداخت آنلاین در این فروشگاه معمولا خاموش است و مشتری بعد از تماس کارشناس،
 * کارت‌به‌کارت یا واریز می‌کند. تا پیش از این هیچ‌جا ثبت نمی‌شد که چه کسی چه
 * مبلغی را کِی و با چه شماره پیگیری‌ای پرداخت کرده؛ ادمین باید دستی وضعیت
 * سفارش را «پرداخت شده» می‌کرد و هیچ سندی پشتش نبود.
 *
 * جدول جدا ساخته نشد تا مدیر همه‌ی پرداخت‌ها (درگاه و دستی) را در یک صفحه
 * ببیند؛ رکورد دستی با gateway = 'manual' مشخص می‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'method')) {
                // کارت‌به‌کارت، واریز بانکی، کارتخوان، نقدی
                $table->string('method', 30)->nullable()->after('gateway');
            }
            if (! Schema::hasColumn('payments', 'reference')) {
                // شماره پیگیری/رهگیریِ خودِ مشتری؛ عمدا از ref_id جداست چون
                // callback درگاه با ref_id رکورد را پیدا می‌کند
                $table->string('reference', 100)->nullable()->after('ref_id');
            }
            if (! Schema::hasColumn('payments', 'card_last4')) {
                $table->string('card_last4', 4)->nullable()->after('reference');
            }
            if (! Schema::hasColumn('payments', 'payer_name')) {
                $table->string('payer_name', 100)->nullable()->after('card_last4');
            }
            if (! Schema::hasColumn('payments', 'receipt_image')) {
                $table->string('receipt_image', 255)->nullable()->after('payer_name');
            }
            if (! Schema::hasColumn('payments', 'customer_note')) {
                $table->text('customer_note')->nullable()->after('receipt_image');
            }
            if (! Schema::hasColumn('payments', 'admin_note')) {
                // دلیل رد شدن یا یادداشت تأیید
                $table->text('admin_note')->nullable()->after('customer_note');
            }
            if (! Schema::hasColumn('payments', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('admin_note');
            }
            if (! Schema::hasColumn('payments', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });

        // صفحه‌ی مدیریت پرداخت‌ها همیشه روی همین دو ستون فیلتر می‌زند
        Schema::table('payments', function (Blueprint $table) {
            $indexes = collect(Schema::getIndexes('payments'))->pluck('name')->all();

            if (! in_array('payments_status_index', $indexes, true)) {
                $table->index('status', 'payments_status_index');
            }
            if (! in_array('payments_gateway_index', $indexes, true)) {
                $table->index('gateway', 'payments_gateway_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $columns = array_filter([
                'method', 'reference', 'card_last4', 'payer_name', 'receipt_image',
                'customer_note', 'admin_note', 'reviewed_by', 'reviewed_at',
            ], fn ($column) => Schema::hasColumn('payments', $column));

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
