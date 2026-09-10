<?php

namespace App\Http\Controllers;

use App\Services\ThumbnailService;
use Illuminate\Http\Response;

/**
 * ساخت بندانگشتی در اولین درخواست.
 *
 * وقتی فایل کش موجود باشد، آپاچی همان‌جا تحویلش می‌دهد و این کنترلر اصلا
 * صدا زده نمی‌شود (قاعده‌ی «فایل موجود را مستقیم بده» در public/.htaccess).
 * پس این مسیر فقط یک‌بار برای هر تصویر/اندازه اجرا می‌شود؛ بعد از آن یک
 * فایل ثابت است. به همین دلیل هم نیازی به پیش‌ساختن همه‌ی تصاویر نیست و
 * عکس‌های تازه‌آپلودشده خودبه‌خود پوشش داده می‌شوند.
 */
class ThumbnailController extends Controller
{
    public function show(int $width, string $path)
    {
        // مسیر با پسوند .webp تمام می‌شود؛ اصلِ تصویر همان بدون این پسوند است.
        if (! str_ends_with(strtolower($path), '.webp')) {
            abort(404);
        }

        // عرض باید از لیست بسته باشد، وگرنه هر کسی می‌تواند با هزار عرضِ
        // دلخواه، هزار نسخه از هر تصویر بسازد و دیسک را پر کند.
        if (! in_array($width, ThumbnailService::SIZES, true)) {
            abort(404);
        }

        $source = ThumbnailService::safeRelative(substr($path, 0, -5));
        if (! $source) {
            abort(404);
        }

        $file = ThumbnailService::generate($source, $width);

        if (! $file) {
            /*
            | ساخت که شکست بخورد (تصویر خراب یا نیمه‌دانلود) کاربر نباید
            | عکسِ شکسته ببیند. اگر اصل تصویر هست همان را نشان می‌دهیم؛
            | کندتر ولی درست. مسیر اینجا از قبل اعتبارسنجی شده است.
            */
            if (public_file_path($source)) {
                return redirect('/' . $source, 302);
            }

            abort(404);
        }

        /*
        | همان هدرهای کشِ فایل‌های ثابت. درخواست بعدی به هر حال به آپاچی
        | می‌خورد نه اینجا، ولی اگر کاربری دقیقا هم‌زمان با ساخت فایل
        | برسد، پاسخِ او هم باید قابل کش باشد.
        */
        return new Response(file_get_contents($file), 200, [
            'Content-Type'   => 'image/webp',
            'Content-Length' => (string) filesize($file),
            'Cache-Control'  => 'public, max-age=31536000, immutable',
        ]);
    }
}
