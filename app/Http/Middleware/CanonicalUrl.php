<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * یک آدرس، یک نسخه.
 *
 * بدون این میدل‌ور، گوگل ممکن است http/https، با و بدون www و نسخه‌های
 * حاوی utm_* را چند صفحه‌ی جدا ببیند و اعتبار لینک‌ها بین آن‌ها تقسیم شود.
 * همه‌ی حالت‌های غیراستاندارد با 301 به آدرس کانونیکال منتقل می‌شوند.
 *
 * روی محیط توسعه خاموش است؛ با SEO_FORCE_HTTPS و SEO_FORCE_HOST فعال شود.
 */
class CanonicalUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        // فقط درخواست‌های معمولیِ GET؛ فرم‌ها و API نباید ریدایرکت شوند.
        if (! $request->isMethod('GET') || $request->ajax() || $request->expectsJson()) {
            return $next($request);
        }

        $config = (array) config('seo.canonical', []);
        $forceHttps = (bool) ($config['force_https'] ?? false);
        $forceHost = trim((string) ($config['force_host'] ?? ''));
        $stripParams = (array) ($config['strip_params'] ?? []);

        $scheme = $request->getScheme();
        $host = $request->getHost();
        $query = $request->query();
        $changed = false;

        if ($forceHttps && $scheme !== 'https') {
            $scheme = 'https';
            $changed = true;
        }

        if ($forceHost !== '' && strcasecmp($host, $forceHost) !== 0) {
            // فقط www را نرمال می‌کنیم؛ ساب‌دامنه‌های دیگر دست‌نخورده می‌مانند
            // تا محیط استیجینگ یا CDN تصادفا ریدایرکت نشود.
            if (strcasecmp($host, 'www.' . $forceHost) === 0 || strcasecmp('www.' . $host, $forceHost) === 0) {
                $host = $forceHost;
                $changed = true;
            }
        }

        // پارامترهای ردیابی فقط برای ابزارهای تحلیلی‌اند و نسخه‌ی تکراری
        // از هر صفحه می‌سازند؛ بعد از ثبت بازدید حذفشان می‌کنیم.
        foreach ($stripParams as $param) {
            if (array_key_exists($param, $query)) {
                unset($query[$param]);
                $changed = true;
            }
        }

        if (! $changed) {
            return $next($request);
        }

        $target = $scheme . '://' . $host . $request->getBaseUrl() . $request->getPathInfo();
        if ($query) {
            $target .= '?' . http_build_query($query);
        }

        return redirect()->away($target, 301);
    }
}
