<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\EshopCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductAdminController extends Controller
{
    public function admin_list(Request $request)
    {
        $user = Auth::user();
        if (!$user) return redirect('/loginAdmin');
        access(388);

        $query = Product::orderBy($request->order ?? 'id', $request->orderby ?? 'desc');

        if (!empty($request->title)) {
            $query->where('title', 'like', "%{$request->title}%");
        }
        if (!empty($request->sku)) {
            $query->where('sku', 'like', "%{$request->sku}%");
        }
        if (!empty($request->car_model)) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('category_id', (int) $request->car_model);
            });
        }

        $totalCount = $query->count();
        $model = $query->paginate($request->showcount ?? 20);

        if ($request->ajax()) {
            $view = view('product.admin.list_type', compact('model', 'totalCount'))->render();
            return response()->json(['html' => $view, 'totalCount' => $totalCount]);
        }

        $carCategories = EshopCategory::orderBy('name')->get();
        return view('product.admin.list', compact('model', 'carCategories'));
    }

    public function admin_create()
    {
        if (!Auth::user()) return redirect('/loginAdmin');
        access(388);
        $categories = EshopCategory::orderBy('name')->get();
        return view('product.admin.create', compact('categories'));
    }

    public function admin_store(Request $request)
    {
        if (!Auth::user()) return redirect('/loginAdmin');
        access(388);

        $validator = Validator::make($request->all(), [
            'title'         => 'required|string|max:255',
            'sku'           => 'nullable|string|max:100',
            'price'         => 'required|integer|min:0',
            'regular_price' => 'nullable|integer|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'is_special_offer' => 'nullable|boolean',
            'stock'         => 'nullable|integer|min:0',
            'weight'        => 'nullable|integer|min:0',
            'is_active'     => 'required|boolean',
            'description'   => 'nullable|string',
            'short_description' => 'nullable|string',
            'car_model'     => 'nullable|string|max:255',
            'file'          => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->only([
            'title', 'sku', 'price', 'regular_price', 'discount_percent',
            'stock', 'weight', 'is_active', 'description', 'short_description', 'car_model',
        ]);

        $data['slug'] = Str::slug($request->sku ?: $request->title);
        $data['is_special_offer'] = $request->boolean('is_special_offer');
        if ((int) ($data['discount_percent'] ?? 0) > 0) {
            $data['is_special_offer'] = true;
            if ((int) ($data['regular_price'] ?? 0) > 0) {
                $data['price'] = (int) round($data['regular_price'] * (100 - (int) $data['discount_percent']) / 100);
            }
        }

        if ($request->hasFile('file')) {
            $data['file_path'] = $this->storeUploadedProductImage($request);
        }

        $product = Product::create($data);
        if (!empty($data['file_path'])) {
            DB::table('product_images')->insert([
                'product_id' => $product->id,
                'path' => $data['file_path'],
                'alt' => $product->title,
                'is_primary' => 1,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect('/admin/product/list')->with('success', 'Product saved successfully');
    }

    public function admin_edit($id)
    {
        if (!Auth::user()) return redirect('/loginAdmin');
        access(388);

        $model = Product::with('images')->findOrFail($id);
        $categories = EshopCategory::orderBy('name')->get();
        return view('product.admin.create', compact('model', 'categories'));
    }

    public function admin_update(Request $request, $id)
    {
        if (!Auth::user()) return redirect('/loginAdmin');
        access(388);

        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title'         => 'required|string|max:255',
            'sku'           => 'nullable|string|max:100',
            'price'         => 'required|integer|min:0',
            'regular_price' => 'nullable|integer|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'is_special_offer' => 'nullable|boolean',
            'stock'         => 'nullable|integer|min:0',
            'weight'        => 'nullable|integer|min:0',
            'is_active'     => 'required|boolean',
            'description'   => 'nullable|string',
            'short_description' => 'nullable|string',
            'car_model'     => 'nullable|string|max:255',
            'file'          => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->only([
            'title', 'sku', 'price', 'regular_price', 'discount_percent',
            'stock', 'weight', 'is_active', 'description', 'short_description', 'car_model',
        ]);
        $data['is_special_offer'] = $request->boolean('is_special_offer');
        if ((int) ($data['discount_percent'] ?? 0) > 0) {
            $data['is_special_offer'] = true;
            if ((int) ($data['regular_price'] ?? 0) > 0) {
                $data['price'] = (int) round($data['regular_price'] * (100 - (int) $data['discount_percent']) / 100);
            }
        }

        if ($request->hasFile('file')) {
            $data['file_path'] = $this->storeUploadedProductImage($request);
        }

        $product->update($data);

        return redirect('/admin/product/list')->with('success', 'Product updated successfully');
    }

    public function statusProduct($id)
    {
        if (!Auth::user()) return redirect('/loginAdmin');
        access(388);

        $product = Product::findOrFail($id);
        $product->update(['is_active' => !$product->is_active]);

        return back()->with('success', 'Product status changed');
    }

    public function uploadImage(Request $request, $id)
    {
        if (!Auth::user()) return redirect('/loginAdmin');
        access(388);

        $product = Product::findOrFail($id);

        $request->validate(['file' => 'required|image|max:5120']);

        $imagePath = $this->storeUploadedProductImage($request);
        DB::table('product_images')->insert([
            'product_id' => $product->id,
            'path' => $imagePath,
            'alt' => $product->title,
            'is_primary' => $product->images()->count() === 0 ? 1 : 0,
            'sort_order' => ($product->images()->max('sort_order') ?? 0) + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if (!$product->file_path) {
            $product->update(['file_path' => $imagePath]);
        }

        return back()->with('success', 'Product image updated');
    }
    public function setPrimaryImage($id, $imageId)
    {
        if (!Auth::user()) return redirect('/loginAdmin');
        access(388);

        $product = Product::findOrFail($id);
        $image = $product->images()->where('id', $imageId)->firstOrFail();

        DB::table('product_images')->where('product_id', $product->id)->update(['is_primary' => 0]);
        $image->update(['is_primary' => 1]);
        $product->update(['file_path' => $image->path]);

        return back()->with('success', 'Primary image changed');
    }
    public function deleteImage($id)
    {
        if (!Auth::user()) return redirect('/loginAdmin');
        access(388);

        $product = Product::findOrFail($id);

        if ($product->file_path && str_starts_with($product->file_path, '/storage/products/')) {
            $old = str_replace('/storage/', '', $product->file_path);
            \Storage::disk('public')->delete($old);
        }

        $product->update(['file_path' => null]);

        return back()->with('success', 'Product image deleted');
    }

    private function storeUploadedProductImage(Request $request): string
    {
        $file = $request->file('file');
        $directory = public_path('upload/products/' . date('Y/m'));
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = uniqid('product-', true) . '.' . $extension;
        $file->move($directory, $filename);

        return '/upload/products/' . date('Y/m') . '/' . $filename;
    }
    public function destroy($id)
    {
        if (!Auth::user()) return redirect('/loginAdmin');
        access(388);

        Product::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}



