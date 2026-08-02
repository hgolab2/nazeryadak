<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\EshopCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DOMDocument;
use DOMXPath;

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

    public function import(Request $request)
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
        $content = file_get_contents($file->getRealPath());

        $content = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $content);

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $content);
        $xpath = new DOMXPath($dom);

        $rows = $xpath->query('//tbody/tr');

        if ($rows->length === 0) {
            return back()->with('error', 'فایل معتبر نیست یا داده‌ای یافت نشد.');
        }

        try {
            $rowsData = [];
            foreach ($rows as $row) {
                $cells = $row->getElementsByTagName('td');
                if ($cells->length < 7) continue;

                $sku   = trim($cells->item(0)->textContent);
                $title = trim($cells->item(1)->textContent);
                if (empty($sku) || empty($title)) continue;
                if (!ctype_digit($sku)) continue;

                $rowsData[] = [
                    'sku'        => $sku,
                    'title'      => $title,
                    'car_model'  => trim($cells->item(3)->textContent),
                    'stock'      => (int) trim($cells->item(4)->textContent),
                    'avg_price'  => (int) floatval(str_replace(',', '', trim($cells->item(5)->textContent))),
                    'sale_price' => (int) floatval(str_replace(',', '', trim($cells->item(6)->textContent))),
                ];
            }

            $carModelNames = array_values(array_unique(array_filter(array_column($rowsData, 'car_model'))));
            $catMap = EshopCategory::whereIn('name', $carModelNames)->pluck('id', 'name')->toArray();
            $newCats = [];
            foreach ($carModelNames as $name) {
                if (!isset($catMap[$name])) {
                    $newCats[] = ['name' => $name, 'slug' => Str::slug($name, '-', null)];
                }
            }
            if (!empty($newCats)) {
                EshopCategory::insert($newCats);
                $catMap = EshopCategory::whereIn('name', $carModelNames)->pluck('id', 'name')->toArray();
            }

            $allSkus = array_column($rowsData, 'sku');
            $existingSkus = Product::whereIn('sku', $allSkus)->pluck('sku')->flip()->toArray();

            $now = now();
            $imported = 0;
            $updated = 0;

            foreach (array_chunk($rowsData, 500) as $chunk) {
                $upsertData = [];
                foreach ($chunk as $row) {
                    if (isset($existingSkus[$row['sku']])) {
                        $updated++;
                    } else {
                        $imported++;
                    }
                    $upsertData[] = [
                        'sku'           => $row['sku'],
                        'title'         => $row['title'],
                        'slug'          => Str::slug($row['sku']),
                        'price'         => $row['sale_price'],
                        'regular_price' => $row['avg_price'],
                        'stock'         => $row['stock'],
                        'is_active'     => $row['stock'] > 0 ? 1 : 0,
                        'car_model'     => $row['car_model'],
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }

                Product::upsert($upsertData, ['sku'], [
                    'title', 'price', 'regular_price', 'stock', 'is_active', 'car_model', 'updated_at',
                ]);
            }

            $productMap = Product::whereIn('sku', $allSkus)->pluck('id', 'sku')->toArray();

            $existingLinks = DB::table('product_in_category')
                ->whereIn('product_id', array_values($productMap))
                ->select('product_id', 'category_id')
                ->get()
                ->mapWithKeys(fn($r) => [$r->product_id . '-' . $r->category_id => true])
                ->toArray();

            $newLinks = [];
            foreach ($rowsData as $row) {
                if (empty($row['car_model'])) continue;
                $productId  = $productMap[$row['sku']] ?? null;
                $categoryId = $catMap[$row['car_model']] ?? null;
                if (!$productId || !$categoryId) continue;

                $key = $productId . '-' . $categoryId;
                if (isset($existingLinks[$key])) continue;
                $existingLinks[$key] = true;

                $newLinks[] = [
                    'product_id'  => $productId,
                    'category_id' => $categoryId,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }

            foreach (array_chunk($newLinks, 500) as $linkChunk) {
                DB::table('product_in_category')->insert($linkChunk);
            }

            DB::table('import_logs')->insert([
                'user_id'    => $user->user_id,
                'file_name'  => $file->getClientOriginalName(),
                'imported'   => $imported,
                'updated'    => $updated,
                'total'      => $imported + $updated,
                'created_at' => $now,
            ]);

            return back()->with('success', "عملیات با موفقیت انجام شد. {$imported} محصول جدید اضافه و {$updated} محصول بروزرسانی شد. " . count($catMap) . " دسته‌بندی خودرو ایجاد/بررسی شد.");

        } catch (\Throwable $e) {
            return back()->with('error', 'خطا در پردازش فایل: ' . $e->getMessage());
        }
    }
}
