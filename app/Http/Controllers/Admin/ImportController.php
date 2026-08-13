<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductStockImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    public function showForm()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect('/loginAdmin');
        }

        $lastImport = DB::table('import_logs')->latest()->first();

        return view('admin.import', compact('lastImport'));
    }

    public function import(Request $request, ProductStockImportService $importer)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        DB::disableQueryLog();

        $user = Auth::user();
        if (!$user) {
            return redirect('/loginAdmin');
        }

        $request->validate([
            'file' => 'required|file|max:20480',
        ]);

        $file = $request->file('file');

        try {
            $result = $importer->import($file->getRealPath(), $file->getClientOriginalName(), (int) $user->user_id, true);

            return back()->with('success', "عملیات با موفقیت انجام شد. {$result['imported']} محصول جدید اضافه و {$result['updated']} محصول بروزرسانی شد. {$result['deleted']} رکورد تکراری حذف شد. {$result['deactivated']} محصول دارای SKU که خارج از فایل بود ناموجود شد. {$result['categories']} دسته‌بندی خودرو ایجاد/بررسی شد.");
        } catch (\Throwable $e) {
            return back()->with('error', 'خطا در پردازش فایل: ' . $e->getMessage());
        }
    }
}