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
    /**
     * ستون‌هایی که فرم ثبت/ویرایش محصول اجازه‌ی نوشتن در آن‌ها را دارد.
     * category_id قبلا در این فهرست نبود، پس دسته‌بندی انتخاب‌شده در فرم
     * بی‌سروصدا دور ریخته می‌شد و محصول در فیلتر دسته‌بندی فروشگاه پیدا نمی‌شد.
     */
    private const EDITABLE_FIELDS = [
        'title', 'sku', 'price', 'regular_price', 'discount_percent',
        'stock', 'weight', 'is_active', 'description', 'short_description',
        'car_model', 'category_id',
        'seo_title', 'seo_description', 'focus_keyword', 'canonical_url',
    ];

    private static function rules(): array
    {
        $categoryIds = array_column(\App\Enums\ProductCategory::cases(), 'value');

        return [
            'title'         => 'required|string|max:255',
            'sku'           => 'nullable|string|max:100',
            'price'         => 'required|integer|min:0',
            'regular_price' => 'nullable|integer|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'is_special_offer' => 'nullable|boolean',
            // موجودی دیگر اختیاری نیست؛ محصول با stock خالی در فروشگاه دیده
            // می‌شد ولی به سبد خرید اضافه نمی‌شد
            'stock'         => 'required|integer|min:0',
            'weight'        => 'nullable|integer|min:0',
            'is_active'     => 'required|boolean',
            'description'   => 'nullable|string',
            'short_description' => 'nullable|string',
            'car_model'     => 'nullable|string|max:255',
            'category_id'   => 'nullable|integer|in:' . implode(',', $categoryIds),
            'file'          => 'nullable|image|max:2048',

            // سئوی دستی؛ همگی اختیاری‌اند و خالی‌شان یعنی «خودکار بساز»
            'seo_title'       => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'focus_keyword'   => 'nullable|string|max:255',
            'canonical_url'   => 'nullable|url|max:500',
            'robots_index'    => 'nullable|boolean',
            'robots_follow'   => 'nullable|boolean',
        ];
    }

    /**
     * پیام‌های فارسی؛ پروژه فایل ترجمه‌ی validation ندارد و بدون این‌ها
     * مدیر به جای پیام، رشته‌ی «validation.required» می‌بیند.
     */
    private static function messages(): array
    {
        return [
            'title.required'     => 'عنوان قطعه را وارد کنید.',
            'price.required'     => 'قیمت فروش را وارد کنید.',
            'price.integer'      => 'قیمت فروش باید عدد باشد (بدون کاما یا حروف).',
            'stock.required'     => 'موجودی را وارد کنید؛ برای قطعه‌ی ناموجود عدد صفر بگذارید.',
            'stock.integer'      => 'موجودی باید عدد باشد.',
            'stock.min'          => 'موجودی نمی‌تواند منفی باشد.',
            'regular_price.integer' => 'قیمت اصلی باید عدد باشد.',
            'discount_percent.max'  => 'درصد تخفیف نمی‌تواند بیشتر از ۱۰۰ باشد.',
            'is_active.required' => 'وضعیت محصول را مشخص کنید.',
            'category_id.in'     => 'دسته‌بندی انتخاب‌شده معتبر نیست.',
            'file.image'         => 'فایل انتخاب‌شده تصویر نیست.',
            'file.max'           => 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد.',
            'canonical_url.url'  => 'آدرس کانونیکال باید یک لینک کامل باشد (با https:// شروع شود).',
            'seo_description.max'=> 'توضیحات متا نباید بیشتر از ۵۰۰ کاراکتر باشد.',
        ];
    }

    /**
     * دسته‌بندی قطعه را در جدول واسط هم می‌نویسد، چون فیلتر «/shop?category=»
     * و شمارش دسته‌ها از product_in_category خوانده می‌شود نه از ستون
     * products.category_id. ردیف‌های خارج از بازه‌ی ۱ تا ۱۱ (دسته‌های خودرو)
     * دست نخورده می‌مانند — همان قراردادی که products:categorize دارد.
     */
    private function syncCategory(int $productId, $categoryId): void
    {
        DB::table('product_in_category')
            ->where('product_id', $productId)
            ->whereBetween('category_id', [1, 11])
            ->delete();

        if (!$categoryId) {
            return;
        }

        DB::table('product_in_category')->insert([
            'product_id'  => $productId,
            'category_id' => (int) $categoryId,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    /**
     * پرچم‌های index/follow صفحه‌ی محصول.
     *
     * چک‌باکسِ تیک‌نخورده اصلا در درخواست نمی‌آید، به همین دلیل فرم کنار هر
     * چک‌باکس یک input مخفی با مقدار صفر دارد؛ اینجا فقط تبدیل به boolean
     * می‌شود تا مقدار «0» رشته‌ای وارد دیتابیس نشود.
     */
    private function robotsFlags(Request $request): array
    {
        return [
            'robots_index'  => $request->boolean('robots_index'),
            'robots_follow' => $request->boolean('robots_follow'),
        ];
    }

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

        $validator = Validator::make($request->all(), self::rules(), self::messages());

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->only(self::EDITABLE_FIELDS);

        $data['slug'] = Str::slug($request->sku ?: $request->title);
        $data['is_special_offer'] = $request->boolean('is_special_offer');
        $data += $this->robotsFlags($request);
        // تخفیف روی قیمت فروش اعمال می‌شود، نه روی regular_price که قیمت خرید است
        $discountPercent = (int) ($data['discount_percent'] ?? 0);
        unset($data['discount_percent']);

        if ($request->hasFile('file')) {
            $data['file_path'] = $this->storeUploadedProductImage($request);
        }

        $product = Product::create($data);
        $product->applyDiscountPercent($discountPercent);
        $product->save();
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

        $this->syncCategory($product->id, $data['category_id'] ?? null);

        return redirect('/admin/product/list')->with('success', 'محصول با موفقیت ثبت شد.');
    }

    public function admin_edit($id)
    {
        if (!Auth::user()) return redirect('/loginAdmin');
        access(388);

        $model = Product::with('images')->findOrFail($id);
        $categories = EshopCategory::orderBy('name')->get();

        // دسته‌ی محصولات موجود در جدول واسط نگهداری می‌شود (خروجی products:categorize)
        // و ستون category_id اغلب خالی است، پس هر دو را در نظر می‌گیریم
        $selectedCategoryId = DB::table('product_in_category')
            ->where('product_id', $model->id)
            ->whereBetween('category_id', [1, 11])
            ->value('category_id') ?? $model->category_id;

        return view('product.admin.create', compact('model', 'categories', 'selectedCategoryId'));
    }

    public function admin_update(Request $request, $id)
    {
        if (!Auth::user()) return redirect('/loginAdmin');
        access(388);

        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), self::rules(), self::messages());

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->only(self::EDITABLE_FIELDS);
        $data['is_special_offer'] = $request->boolean('is_special_offer');
        $data += $this->robotsFlags($request);
        // تخفیف روی قیمت فروش اعمال می‌شود، نه روی regular_price که قیمت خرید است
        $discountPercent = (int) ($data['discount_percent'] ?? 0);
        unset($data['discount_percent']);

        if ($request->hasFile('file')) {
            $data['file_path'] = $this->storeUploadedProductImage($request);
        }

        // ادمین قیمت فروش تازه را وارد می‌کند، پس مبنای تخفیف باید از نو حساب شود
        $product->compare_at_price = null;
        $product->update($data);
        $product->applyDiscountPercent($discountPercent);
        $product->save();
        $this->syncCategory($product->id, $data['category_id'] ?? null);

        return redirect('/admin/product/list')->with('success', 'محصول با موفقیت ویرایش شد.');
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

        // نسخه‌ی WebP بلافاصله ساخته می‌شود تا تصویرهای تازه‌آپلودشده هم مثل
        // بقیه سبک سرو شوند و نیازی به اجرای دستی «php artisan images:webp» نباشد.
        $this->makeWebpVariant($directory . DIRECTORY_SEPARATOR . $filename, $extension);

        return '/upload/products/' . date('Y/m') . '/' . $filename;
    }

    /**
     * ساخت نسخه‌ی WebP کنار فایل اصلی.
     *
     * شکست این کار نباید آپلود را خراب کند؛ اگر نشد، تصویر اصلی سرو
     * می‌شود و دستور images:webp بعدا آن را می‌سازد.
     */
    private function makeWebpVariant(string $absolutePath, string $extension): void
    {
        if (! function_exists('imagewebp') || ! is_file($absolutePath)) {
            return;
        }

        try {
            $image = match ($extension) {
                'png' => @imagecreatefrompng($absolutePath),
                'jpg', 'jpeg' => @imagecreatefromjpeg($absolutePath),
                default => null,
            };

            if (! $image) {
                return;
            }

            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);

            $target = preg_replace('/\.(jpe?g|png)$/i', '.webp', $absolutePath);
            @imagewebp($image, $target, 82);
            imagedestroy($image);

            // اگر WebP از اصل بزرگ‌تر شد، نگهش نمی‌داریم.
            if (is_file($target) && filesize($target) >= filesize($absolutePath)) {
                @unlink($target);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('WebP generation failed', [
                'path' => $absolutePath,
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function destroy($id)
    {
        if (!Auth::user()) return redirect('/loginAdmin');
        access(388);

        Product::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}



