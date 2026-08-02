<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\EshopCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
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
            $query->where('car_model', 'like', "%{$request->car_model}%");
        }

        $totalCount = $query->count();
        $model = $query->paginate($request->showcount ?? 20);

        if ($request->ajax()) {
            $view = view('product.admin.list_type', compact('model', 'totalCount'))->render();
            return response()->json(['html' => $view, 'totalCount' => $totalCount]);
        }

        return view('product.admin.list', compact('model'));
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
            'stock'         => 'nullable|integer|min:0',
            'weight'        => 'nullable|integer|min:0',
            'is_active'     => 'required|boolean',
            'description'   => 'nullable|string',
            'car_model'     => 'nullable|string|max:255',
            'file'          => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->only([
            'title', 'sku', 'price', 'regular_price',
            'stock', 'weight', 'is_active', 'description', 'car_model',
        ]);

        $data['slug'] = Str::slug($request->sku ?: $request->title);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('products', 'public');
            $data['file_path'] = '/storage/' . $path;
        }

        Product::create($data);

        return redirect('/admin/product/list')->with('success', 'محصول با موفقیت ایجاد شد');
    }

    public function admin_edit($id)
    {
        if (!Auth::user()) return redirect('/loginAdmin');
        access(388);

        $model = Product::findOrFail($id);
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
            'stock'         => 'nullable|integer|min:0',
            'weight'        => 'nullable|integer|min:0',
            'is_active'     => 'required|boolean',
            'description'   => 'nullable|string',
            'car_model'     => 'nullable|string|max:255',
            'file'          => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->only([
            'title', 'sku', 'price', 'regular_price',
            'stock', 'weight', 'is_active', 'description', 'car_model',
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('products', 'public');
            $data['file_path'] = '/storage/' . $path;
        }

        $product->update($data);

        return redirect('/admin/product/list')->with('success', 'محصول با موفقیت بروزرسانی شد');
    }

    public function statusProduct($id)
    {
        if (!Auth::user()) return redirect('/loginAdmin');
        access(388);

        $product = Product::findOrFail($id);
        $product->update(['is_active' => !$product->is_active]);

        return back()->with('success', 'وضعیت محصول تغییر کرد');
    }

    public function uploadImage(Request $request, $id)
    {
        if (!Auth::user()) return redirect('/loginAdmin');
        access(388);

        $product = Product::findOrFail($id);

        $request->validate(['file' => 'required|image|max:5120']);

        if ($product->file_path && str_starts_with($product->file_path, '/storage/products/')) {
            $old = str_replace('/storage/', '', $product->file_path);
            \Storage::disk('public')->delete($old);
        }

        $path = $request->file('file')->store('products', 'public');
        $product->update(['file_path' => '/storage/' . $path]);

        return back()->with('success', 'تصویر محصول بروزرسانی شد');
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

        return back()->with('success', 'تصویر محصول حذف شد');
    }

    public function destroy($id)
    {
        if (!Auth::user()) return redirect('/loginAdmin');
        access(388);

        Product::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
