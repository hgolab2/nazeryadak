<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * دو کار در یک میدل‌ور، چون هر دو به «مسیر نرمال‌شده‌ی درخواست» نیاز دارند:
 *
 * ۱) اعمال قواعد ریدایرکتِ تعریف‌شده در پنل، پیش از مسیریابی.
 * ۲) ثبت مسیرهایی که به ۴۰۴ رسیده‌اند، برای مانیتور خطاها.
 *
 * چون در bootstrap/app.php به‌صورت global ثبت می‌شود، خروجی ۴۰۴ مسیرهای
 * تعریف‌نشده هم از اینجا رد می‌شود: استثنای NotFoundHttpException در
 * لایه‌های داخلی‌تر به پاسخ تبدیل شده و به همین‌جا برمی‌گردد.
 */
class SeoRedirect
{
    /**
     * پسوندهایی که ۴۰۴شان ارزش ثبت ندارد.
     * بدون این فهرست، لاگ پر می‌شود از فایل‌های ظاهریِ کش‌شده‌ی مرورگرها.
     */
    private const IGNORED_EXTENSIONS = [
        'css', 'js', 'map', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'svg',
        'ico', 'woff', 'woff2', 'ttf', 'eot', 'mp4', 'zip',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // فرم‌ها و درخواست‌های AJAX نباید ریدایرکت شوند؛ ریدایرکت روی POST
        // داده‌ی فرم را دور می‌ریزد.
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        $path = Redirect::normalize($request->getPathInfo());

        if ($rule = $this->findRule($path)) {
            return $this->applyRule($request, $rule);
        }

        $response = $next($request);

        if ($response->getStatusCode() === 404) {
            $this->logNotFound($request, $path);
        }

        return $response;
    }

    /**
     * خواندن قاعده از کش. هر خطای دیتابیس (مثلا قبل از اجرای مهاجرت) باید
     * بی‌صدا رد شود وگرنه کل سایت با ۵۰۰ پایین می‌آید.
     */
    private function findRule(string $path): ?array
    {
        try {
            return Redirect::map()[$path] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function applyRule(Request $request, array $rule): Response
    {
        $this->countHit($rule['id']);

        // 410 یعنی «این صفحه عمدا حذف شده»؛ گوگل سریع‌تر از ۴۰۴ حذفش می‌کند.
        if ($rule['status'] === 410) {
            return response()->view('errors.404', [], 410);
        }

        $target = trim((string) $rule['target']) ?: '/';

        if (! preg_match('~^https?://~i', $target)) {
            // کوئری‌استرینگ درخواست (utm، page و …) روی مقصد داخلی حفظ می‌شود
            // مگر خود مقصد کوئری داشته باشد.
            $query = $request->getQueryString();
            if ($query && ! str_contains($target, '?')) {
                $target .= '?' . $query;
            }
            $target = url($target);
        }

        return redirect()->away($target, $rule['status']);
    }

    /** شمارنده‌ی برخورد؛ با Query Builder تا رویدادهای مدل و پاک‌سازی کش صدا زده نشوند. */
    private function countHit(int $id): void
    {
        try {
            DB::table('redirects')->where('id', $id)->update([
                'hits'        => DB::raw('hits + 1'),
                'last_hit_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // شمارنده نباید مانع ریدایرکت شود.
        }
    }

    private function logNotFound(Request $request, string $path): void
    {
        if ($path === '/' || mb_strlen($path) > 191) {
            return;
        }

        $extension = strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
        if ($extension && in_array($extension, self::IGNORED_EXTENSIONS, true)) {
            return;
        }

        try {
            $now = now();
            $values = [
                'referer'      => mb_substr((string) $request->headers->get('referer'), 0, 500) ?: null,
                'user_agent'   => mb_substr((string) $request->userAgent(), 0, 255) ?: null,
                'last_seen_at' => $now,
                'updated_at'   => $now,
            ];

            $updated = DB::table('not_found_logs')
                ->where('path', $path)
                ->update($values + ['hits' => DB::raw('hits + 1')]);

            if (! $updated) {
                DB::table('not_found_logs')->insert($values + [
                    'path'       => $path,
                    'hits'       => 1,
                    'created_at' => $now,
                ]);
            }
        } catch (\Throwable $e) {
            // لاگ‌کردن خطا نباید خودش خطا بسازد؛ صفحه‌ی ۴۰۴ باید نمایش داده شود.
        }
    }
}
