<?php
namespace App\Http\Controllers;
use App\Models\Order;
use App\Services\FinanceReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect('/loginAdmin');
        }

        // ترتیب و رابطه‌ها مثل /admin/order/list؛ داشبورد بر اساس created_at
        // مرتب می‌شد و لیست بر اساس id، پس دو صفحه یک چیز نشان نمی‌دادند
        $recentOrders = Order::with(['customer', 'pendingReceipt', 'items', 'expenses'])
            ->where('status', '!=', 'draft')
            ->orderByDesc('id')
            ->take(10)
            ->get();

        // شمارنده‌ها و سود از همان منبعی می‌آیند که صفحه‌ی حسابداری و ربات
        // بله استفاده می‌کنند، تا سه جا سه عدد مختلف نگویند
        $snapshot = FinanceReport::snapshot();
        $month    = FinanceReport::thisMonth();
        $today    = FinanceReport::today();
        $allTime  = FinanceReport::allTime();

        return view('admin.dashboard', [
            'snapshot'     => $snapshot,
            'month'        => $month,
            'monthLabel'   => Jalalian::now()->format('%B'),
            'today'        => $today,
            'allTime'      => $allTime,
            'recentOrders' => $recentOrders,
            'statuses'     => Order::STATUSES,
        ]);
    }
}
