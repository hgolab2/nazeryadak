<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaleWebhookController;
use App\Http\Controllers\Controller;
use App\Services\BaleNotifier;
use App\Services\BaleOrderResender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * صفحه‌ی «بله» در پنل: وضعیت اتصال، وبهوک ربات، و ارسال دوباره‌ی سفارش‌هایی
 * که به بله نرسیده‌اند.
 *
 * تا پیش از این، اگر سفارشی به بله نمی‌رسید هیچ‌جا معلوم نبود چرا؛ مدیر
 * باید لاگ سرور را می‌خواند. این‌جا همه‌چیز یک‌جا دیده می‌شود.
 */
class BaleAdminController extends Controller
{
    public function index()
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $problem = BaleNotifier::problem();
        $webhook = null;

        if (! $problem) {
            // getWebhookInfo کند باشد نباید صفحه را خراب کند؛ null یعنی نامعلوم
            $webhook = BaleNotifier::api('getWebhookInfo', [], 'admin', 5);
        }

        $pendingOrders = BaleOrderResender::pending(30)->get();

        return view('bale.admin.index', [
            'problem'       => $problem,
            'chatIds'       => BaleNotifier::chatIds(),
            'adminIds'      => BaleNotifier::adminIds(),
            'webhook'       => $webhook,
            'webhookUrl'    => BaleWebhookController::url(),
            'pendingOrders' => $pendingOrders,
            'events'        => BaleNotifier::EVENTS,
            'enabledEvents' => array_filter(array_keys(BaleNotifier::EVENTS), fn ($e) => BaleNotifier::enabled($e)),
        ]);
    }

    /** پیام آزمایشی به همه‌ی مقصدها */
    public function test()
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        $result = BaleNotifier::sendNow('test', [
            'وضعیت' => 'اتصال پنل به بله برقرار است.',
            'کاربر' => trim((string) Auth::user()->fullname()),
        ]);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** ثبت وبهوک روی آدرس سایت */
    public function webhook(Request $request)
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        if ($problem = BaleNotifier::problem()) {
            return back()->with('error', $problem);
        }

        if ($request->input('action') === 'remove') {
            $ok = BaleNotifier::api('deleteWebhook', [], 'admin') !== null;

            return back()->with($ok ? 'success' : 'error', $ok ? 'وبهوک حذف شد.' : 'حذف وبهوک ناموفق بود.');
        }

        $url = BaleWebhookController::url();
        $ok  = BaleNotifier::api('setWebhook', ['url' => $url], 'admin') !== null;

        return back()->with(
            $ok ? 'success' : 'error',
            $ok ? 'وبهوک ثبت شد. حالا از داخل بله به ربات /start بفرستید.' : 'ثبت وبهوک ناموفق بود؛ جزئیات در لاگ سرور است.'
        );
    }

    /** ارسال دوباره‌ی سفارش‌هایی که به بله نرسیده‌اند */
    public function resend()
    {
        if (!Auth::user()) return redirect('/login');
        access(388);

        if ($problem = BaleNotifier::problem()) {
            return back()->with('error', $problem);
        }

        $result = (new BaleOrderResender())->resend(30);

        if ($result['total'] === 0) {
            return back()->with('success', 'سفارش ارسال‌نشده‌ای وجود نداشت.');
        }

        return back()->with(
            $result['failed'] === 0 ? 'success' : 'error',
            $result['sent'] . ' سفارش به بله فرستاده شد' . ($result['failed'] > 0 ? '؛ ' . $result['failed'] . ' سفارش ناموفق (لاگ سرور را ببینید).' : '.')
        );
    }
}
