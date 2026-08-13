<?php

namespace App\Http\Controllers;

use App\Models\CustomerNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('customer')->user();

        if (! $user) {
            return redirect('/login');
        }

        $notifications = CustomerNotification::where('customer_id', $user->id)
            ->latest()
            ->paginate(20);

        // باز کردن صفحه یعنی کاربر آن‌ها را دیده است؛ شمارنده‌ی زنگوله صفر می‌شود.
        CustomerNotification::where('customer_id', $user->id)
            ->unread()
            ->update(['read_at' => now()]);

        return view('profile.notifications', compact('notifications'));
    }

    /** شمارنده‌ی زنگوله برای به‌روزرسانی بدون بارگذاری دوباره‌ی صفحه */
    public function unreadCount()
    {
        $user = Auth::guard('customer')->user();

        return response()->json([
            'count' => $user ? CustomerNotification::unreadCountFor($user->id) : 0,
        ]);
    }
}
