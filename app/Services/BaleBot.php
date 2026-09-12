<?php

namespace App\Services;

use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Support\OrderSummary;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;

/**
 * ربات دوطرفه‌ی بله: مدیر از داخل بله وضعیت سفارش را عوض می‌کند و گزارش
 * می‌گیرد، بدون باز کردن پنل.
 *
 * ورودی‌ها از دو راه می‌رسند و هر دو به handle() می‌دهند:
 *   - وبهوک (BaleWebhookController) روی سرور
 *   - دستور bale:poll (getUpdates) در محیط محلی یا سروری که وبهوک ندارد
 *
 * امنیت: فقط کاربرانی که شناسه‌شان در BALE_ADMIN_IDS (یا چت خصوصیِ
 * BALE_CHAT_ID) باشد می‌توانند کاری بکنند. بقیه فقط شناسه‌ی خودشان را
 * می‌بینند تا مدیر بتواند اضافه‌شان کند.
 */
class BaleBot
{
    /** دکمه‌های منوی پایین صفحه؛ متن هر دکمه همان دستور است. */
    private const MENU = [
        ['📋 سفارش‌های باز', '🕐 سفارش‌های امروز'],
        ['📊 آمار امروز', '📊 آمار ماه'],
        ['💰 سود ماه', '🏆 پرفروش‌ها'],
        ['📦 فروشگاه', '❓ راهنما'],
    ];

    /** @param array $update یک آیتم از getUpdates یا بدنه‌ی وبهوک */
    public function handle(array $update): void
    {
        try {
            if (isset($update['callback_query'])) {
                $this->handleCallback($update['callback_query']);

                return;
            }

            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            }
        } catch (\Throwable $e) {
            Log::error('پردازش پیام بله ناموفق بود', [
                'update_id' => $update['update_id'] ?? null,
                'message'   => $e->getMessage(),
            ]);
        }
    }

    /* ------------------------------------------------------- دکمه‌ها */

    private function handleCallback(array $query): void
    {
        $callbackId = (string) ($query['id'] ?? '');
        $fromId     = (string) ($query['from']['id'] ?? '');
        $chatId     = (string) ($query['message']['chat']['id'] ?? '');
        $messageId  = (int) ($query['message']['message_id'] ?? 0);
        $data       = (string) ($query['data'] ?? '');

        if (! $this->authorized($fromId, $chatId)) {
            BaleNotifier::answerCallback($callbackId, 'شما مجاز به این کار نیستید. شناسه‌ی شما: ' . $fromId, true);

            return;
        }

        $parts = explode(':', $data);

        // «st:{id}:{status}» → تغییر وضعیت
        if (($parts[0] ?? '') === 'st' && count($parts) === 3) {
            $order = Order::with(['customer', 'items.product', 'address.province', 'expenses'])->find((int) $parts[1]);

            if (! $order) {
                BaleNotifier::answerCallback($callbackId, 'سفارش پیدا نشد.', true);

                return;
            }

            $status = $parts[2];

            try {
                $changed = (new OrderStatusService())->change($order, $status, 'bale');
            } catch (\InvalidArgumentException $e) {
                BaleNotifier::answerCallback($callbackId, $e->getMessage(), true);

                return;
            }

            $label = Order::STATUSES[$status] ?? $status;

            BaleNotifier::answerCallback(
                $callbackId,
                $changed ? "سفارش #{$order->id} → {$label}" : "سفارش #{$order->id} از قبل «{$label}» بود."
            );

            // پیام قبلی به‌روز می‌شود تا دکمه‌ی زده‌شده دوباره جلوی چشم نباشد.
            // خودِ تغییر وضعیت یک پیام تازه هم می‌فرستد (OrderNotifier).
            $this->refreshOrderMessage($chatId, $messageId, $order->fresh(['customer', 'items.product', 'address.province', 'expenses']));

            return;
        }

        // «show:{id}» → وضعیت فعلی همان سفارش در همان پیام
        if (($parts[0] ?? '') === 'show' && isset($parts[1])) {
            $order = Order::with(['customer', 'items.product', 'address.province', 'expenses'])->find((int) $parts[1]);

            if (! $order) {
                BaleNotifier::answerCallback($callbackId, 'سفارش پیدا نشد.', true);

                return;
            }

            BaleNotifier::answerCallback($callbackId, 'به‌روز شد');
            $this->refreshOrderMessage($chatId, $messageId, $order);

            return;
        }

        BaleNotifier::answerCallback($callbackId, 'دکمه‌ی ناشناخته');
    }

    /** متن و دکمه‌های پیام یک سفارش را با وضعیت فعلی جایگزین می‌کند. */
    private function refreshOrderMessage(string $chatId, int $messageId, Order $order): void
    {
        if ($chatId === '' || $messageId <= 0) {
            return;
        }

        $text = BaleNotifier::compose('order_resend', OrderSummary::baleFields($order));
        $text = str_replace('🔁 سفارش (ارسال دوباره)', Order::statusIcon((string) $order->status) . ' سفارش #' . $order->id, $text);

        BaleNotifier::editMessage($chatId, $messageId, $text, ['inline_keyboard' => OrderSummary::keyboard($order)]);
    }

    /* ------------------------------------------------------- دستورها */

    private function handleMessage(array $message): void
    {
        $chatId = (string) ($message['chat']['id'] ?? '');
        $fromId = (string) ($message['from']['id'] ?? '');
        $text   = trim((string) ($message['text'] ?? ''));

        if ($chatId === '' || $text === '') {
            return;
        }

        if (! $this->authorized($fromId, $chatId)) {
            BaleNotifier::sendTo($chatId, "این ربات فقط برای مدیران فروشگاه است.\nشناسه‌ی شما: {$fromId}\nبرای دسترسی، این شناسه را در BALE_ADMIN_IDS سرور اضافه کنید.");

            return;
        }

        $reply = $this->reply($this->normalize($text));

        BaleNotifier::sendTo($chatId, $reply['text'], $reply['markup'] ?? $this->menuMarkup());
    }

    /**
     * متن دستور را به شکل استاندارد درمی‌آورد: بدون «/»، بدون ایموجی منو،
     * ارقام فارسی → لاتین.
     */
    private function normalize(string $text): string
    {
        $text = function_exists('toLatinDigits') ? toLatinDigits($text) : $text;
        $text = ltrim($text, '/');
        // ایموجی ابتدای دکمه‌های منو
        $text = preg_replace('/^[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}]+\s*/u', '', $text) ?? $text;

        return trim($text);
    }

    /**
     * @return array{text: string, markup?: array}
     */
    public function reply(string $command): array
    {
        $lower = mb_strtolower($command);

        // «سفارش 123» یا «order 123» یا فقط عدد
        if (preg_match('/^(?:سفارش|order)?\s*#?\s*(\d+)$/u', $lower, $m)) {
            return $this->orderReply((int) $m[1]);
        }

        return match (true) {
            in_array($lower, ['start', 'menu', 'منو', 'شروع'], true) => [
                'text'   => "سلام 👋\nاز منوی پایین، یا با این دستورها کار کنید:\n\n" . $this->helpText(),
                'markup' => $this->menuMarkup(),
            ],
            in_array($lower, ['help', 'راهنما'], true) => ['text' => $this->helpText()],

            in_array($lower, ['open', 'سفارش‌های باز', 'سفارشهای باز', 'سفارش های باز', 'باز'], true) => $this->openOrdersReply(),
            in_array($lower, ['today', 'امروز', 'سفارش‌های امروز', 'سفارشهای امروز', 'سفارش های امروز'], true) => $this->todayOrdersReply(),
            str_starts_with($lower, 'orders') || str_starts_with($lower, 'سفارش‌ها') || str_starts_with($lower, 'سفارشها') => $this->recentOrdersReply($this->numberIn($lower, 10)),

            in_array($lower, ['stats', 'آمار امروز', 'آمار'], true) => $this->statsReply('امروز', FinanceReport::today()),
            in_array($lower, ['week', 'آمار هفته', 'هفته'], true) => $this->statsReply('۷ روز اخیر', FinanceReport::lastDays(7)),
            in_array($lower, ['month', 'آمار ماه', 'ماه'], true) => $this->statsReply('ماه ' . Jalalian::now()->format('%B'), FinanceReport::thisMonth()),
            in_array($lower, ['all', 'آمار کل', 'کل'], true) => $this->statsReply('از ابتدا', FinanceReport::allTime()),

            in_array($lower, ['profit', 'سود', 'سود ماه'], true) => $this->profitReply(),
            in_array($lower, ['top', 'پرفروش‌ها', 'پرفروشها', 'پرفروش ها', 'پرفروش'], true) => $this->topReply(),
            in_array($lower, ['shop', 'فروشگاه', 'محصولات'], true) => $this->shopReply(),

            default => ['text' => "دستور «{$command}» را نشناختم.\n\n" . $this->helpText()],
        };
    }

    private function helpText(): string
    {
        return implode("\n", [
            '📋 باز — سفارش‌هایی که منتظر اقدام‌اند',
            '🕐 امروز — سفارش‌های ثبت‌شده‌ی امروز',
            '/orders 20 — آخرین ۲۰ سفارش',
            '/order 123 یا فقط 123 — جزئیات و دکمه‌های وضعیت',
            '',
            '📊 آمار امروز | هفته | ماه | کل — فروش، هزینه، سود خالص',
            '💰 سود — سود ماه جاری و ماه‌های قبل',
            '🏆 پرفروش‌ها — پرفروش‌ترین قطعات ماه',
            '📦 فروشگاه — تعداد محصول، موجودی، مشتری',
            '',
            'با دکمه‌های زیر هر پیام سفارش، وضعیتش را همان‌جا عوض کنید؛ مشتری پیامک می‌گیرد.',
        ]);
    }

    private function menuMarkup(): array
    {
        return [
            'keyboard'        => array_map(fn ($row) => array_map(fn ($t) => ['text' => $t], $row), self::MENU),
            'resize_keyboard' => true,
        ];
    }

    /* ------------------------------------------------------- سفارش‌ها */

    private function orderReply(int $id): array
    {
        $order = Order::with(['customer', 'items.product', 'address.province', 'expenses'])->find($id);

        if (! $order) {
            return ['text' => "سفارشی با شماره‌ی {$id} پیدا نشد."];
        }

        $text = BaleNotifier::compose('order_resend', OrderSummary::baleFields($order));
        $text = str_replace('🔁 سفارش (ارسال دوباره)', Order::statusIcon((string) $order->status) . ' سفارش #' . $order->id, $text);

        return ['text' => $text, 'markup' => ['inline_keyboard' => OrderSummary::keyboard($order)]];
    }

    private function openOrdersReply(): array
    {
        $orders = Order::with('customer')
            ->whereIn('status', Order::OPEN_STATUSES)
            ->orderBy('id')
            ->limit(30)
            ->get();

        if ($orders->isEmpty()) {
            return ['text' => '✅ سفارش بازی نیست؛ همه‌چیز رسیدگی شده.'];
        }

        return ['text' => $this->orderList('📋 سفارش‌های منتظر اقدام (' . $orders->count() . ')', $orders)];
    }

    private function todayOrdersReply(): array
    {
        $orders = Order::with('customer')
            ->where('created_at', '>=', now()->startOfDay())
            ->where('status', '!=', 'draft')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        if ($orders->isEmpty()) {
            return ['text' => 'امروز هنوز سفارشی ثبت نشده.'];
        }

        return ['text' => $this->orderList('🕐 سفارش‌های امروز (' . $orders->count() . ')', $orders)];
    }

    private function recentOrdersReply(int $count): array
    {
        $count  = max(1, min(50, $count));
        $orders = Order::with('customer')->where('status', '!=', 'draft')->orderByDesc('id')->limit($count)->get();

        if ($orders->isEmpty()) {
            return ['text' => 'هنوز سفارشی ثبت نشده.'];
        }

        return ['text' => $this->orderList('📋 آخرین ' . $orders->count() . ' سفارش', $orders)];
    }

    private function orderList(string $title, $orders): string
    {
        $lines = [$title, ''];

        foreach ($orders as $order) {
            $lines[] = OrderSummary::line($order);
        }

        $lines[] = '';
        $lines[] = 'برای جزئیات و تغییر وضعیت، شماره‌ی سفارش را بفرستید.';

        return implode("\n", $lines);
    }

    /* ------------------------------------------------------- آمار */

    private function statsReply(string $title, array $r): array
    {
        $lines = ['📊 آمار ' . $title, ''];
        $lines[] = 'سفارش تسویه‌شده: ' . $r['orders'] . ' — ' . $r['items'] . ' قطعه';
        $lines[] = 'فروش: ' . number_format($r['sales']) . ' تومان';
        $lines[] = 'قیمت خرید کالا: ' . number_format($r['cogs']) . ' تومان';
        $lines[] = 'سود ناخالص: ' . number_format($r['gross_profit']) . ' تومان';

        if ($r['order_expenses'] > 0) {
            $lines[] = 'هزینه‌های سفارش‌ها: ' . number_format($r['order_expenses']) . ' تومان';
        }

        if ($r['general_expenses'] > 0) {
            $lines[] = 'هزینه‌های عمومی: ' . number_format($r['general_expenses']) . ' تومان';
        }

        if ($r['other_income'] > 0) {
            $lines[] = 'سایر درآمدها: ' . number_format($r['other_income']) . ' تومان';
        }

        $lines[] = '';
        $lines[] = '💰 سود خالص: ' . number_format($r['net_profit']) . ' تومان' . ($r['sales'] > 0 ? ' (' . $r['margin'] . '٪)' : '');

        if ($r['expenses_by_category'] !== []) {
            $lines[] = '';
            $lines[] = 'هزینه‌ها به تفکیک:';
            foreach (array_slice($r['expenses_by_category'], 0, 6, true) as $category => $amount) {
                $lines[] = '• ' . FinanceTransaction::categoryName($category) . ': ' . number_format($amount);
            }
        }

        $snapshot = FinanceReport::snapshot();
        $lines[]  = '';
        $lines[]  = 'سفارش باز: ' . $snapshot['orders_open']
            . ($snapshot['pending_receipts'] > 0 ? ' — رسید منتظر بررسی: ' . $snapshot['pending_receipts'] : '');

        return ['text' => implode("\n", $lines)];
    }

    private function profitReply(): array
    {
        $lines = ['💰 سود خالص ماه‌های اخیر', ''];

        foreach (FinanceReport::monthlyTrend(6) as $row) {
            $r = $row['report'];
            $lines[] = $row['label'] . ': ' . number_format($r['net_profit']) . ' تومان'
                . ' (فروش ' . number_format($r['sales']) . '، ' . $r['orders'] . ' سفارش)';
        }

        $all = FinanceReport::allTime();
        $lines[] = '';
        $lines[] = 'از ابتدا: ' . number_format($all['net_profit']) . ' تومان سود از ' . number_format($all['sales']) . ' تومان فروش';

        return ['text' => implode("\n", $lines)];
    }

    private function topReply(): array
    {
        $r    = FinanceReport::thisMonth();
        $rows = FinanceReport::topProducts($r['from'], $r['to'], 10);

        if ($rows === []) {
            // ماه خلوت بوده؛ کل تاریخچه را نشان بده که پیام خالی نباشد
            $all  = FinanceReport::allTime();
            $rows = FinanceReport::topProducts($all['from'], $all['to'], 10);
            $title = '🏆 پرفروش‌ترین قطعات (از ابتدا)';
        } else {
            $title = '🏆 پرفروش‌ترین قطعات ماه ' . Jalalian::now()->format('%B');
        }

        if ($rows === []) {
            return ['text' => 'هنوز فروشی ثبت نشده.'];
        }

        $lines = [$title, ''];
        foreach ($rows as $i => $row) {
            $lines[] = ($i + 1) . ') ' . $row['title'] . ' — ' . $row['qty'] . ' عدد — ' . number_format($row['sales']) . ' تومان';
        }

        return ['text' => implode("\n", $lines)];
    }

    private function shopReply(): array
    {
        $s = FinanceReport::snapshot();

        $lines = ['📦 وضعیت فروشگاه', ''];
        $lines[] = 'محصولات: ' . number_format($s['products']) . ' (فعال: ' . number_format($s['products_active']) . ')';
        $lines[] = 'بدون موجودی: ' . number_format($s['products_nostock']);
        $lines[] = 'مشتریان: ' . number_format($s['customers']);
        $lines[] = 'کل سفارش‌ها: ' . number_format($s['orders']);
        $lines[] = '';
        $lines[] = 'سفارش‌ها به تفکیک وضعیت:';

        foreach (Order::STATUSES as $key => $label) {
            $count = (int) ($s['status_counts'][$key] ?? 0);
            if ($count > 0) {
                $lines[] = Order::statusIcon($key) . ' ' . $label . ': ' . $count;
            }
        }

        if ($s['pending_receipts'] > 0) {
            $lines[] = '';
            $lines[] = '🧷 رسید پرداخت منتظر بررسی: ' . $s['pending_receipts'];
        }

        return ['text' => implode("\n", $lines)];
    }

    /* ------------------------------------------------------- کمکی */

    private function numberIn(string $text, int $default): int
    {
        return preg_match('/(\d+)/', $text, $m) ? (int) $m[1] : $default;
    }

    /**
     * مجاز کیست؟ کاربری که در فهرست مدیران است، یا پیامی که از یکی از
     * چت‌های مقصد (گروه مدیران) آمده و فهرست مدیران خالی است.
     */
    private function authorized(string $fromId, string $chatId): bool
    {
        $admins = BaleNotifier::adminIds();

        if ($fromId !== '' && in_array($fromId, $admins, true)) {
            return true;
        }

        // گروه مقصد بدون فهرست مدیر: هر عضو گروه مجاز است
        return config('bale.admin_ids') === '' || config('bale.admin_ids') === null
            ? in_array($chatId, BaleNotifier::chatIds(), true)
            : false;
    }
}
