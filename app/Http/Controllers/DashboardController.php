<?php
namespace App\Http\Controllers;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect('/loginAdmin');
        }

        $totalProducts = Product::count();
        $totalOrders = Order::count();
        $totalCustomers = Customer::count();
        $totalRevenue = Order::where('status', 'paid')->sum('total_price');

        // ترتیب و رابطه‌ها مثل /admin/order/list؛ داشبورد بر اساس created_at
        // مرتب می‌شد و لیست بر اساس id، پس دو صفحه یک چیز نشان نمی‌دادند
        $recentOrders = Order::with(['customer', 'pendingReceipt'])
            ->orderByDesc('id')
            ->take(10)
            ->get();

        $pendingOrders = Order::where('status', 'pending')->count();
        $paidOrders = Order::where('status', 'paid')->count();

        return view('admin.dashboard', compact(
            'totalProducts',
            'totalOrders',
            'totalCustomers',
            'totalRevenue',
            'recentOrders',
            'pendingOrders',
            'paidOrders'
        ));
    }
}
