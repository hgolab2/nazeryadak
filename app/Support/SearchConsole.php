<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * خواندن گزارش Performance از Google Search Console.
 *
 * دو راه ورود داده هست و هر دو به یک جدول (gsc_queries) می‌ریزند:
 *
 *   ۱) API با Service Account — بدون کتابخانه‌ی google/apiclient: توکن با
 *      JWT امضاشده (RS256) از OAuth گرفته می‌شود و یک POST به
 *      searchAnalytics/query. کل کار کمتر از صد خط است و وابستگیِ ۲۰
 *      مگابایتی نمی‌ارزید.
 *
 *   ۲) CSV خروجیِ خودِ سرچ کنسول (Performance → Export → Download CSV).
 *      این راه همیشه کار می‌کند؛ حتی اگر سرور به googleapis دسترسی نداشته
 *      باشد (که برای سرورهای داخل ایران محتمل است).
 *
 * پیکربندی API در config/seo.php → search_console.
 */
class SearchConsole
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const SCOPE     = 'https://www.googleapis.com/auth/webmasters.readonly';

    public static function isConfigured(): bool
    {
        $path = (string) config('seo.search_console.credentials');
        $site = (string) config('seo.search_console.site_url');

        return $path !== '' && $site !== '' && is_readable($path);
    }

    /**
     * عبارت × صفحه برای بازه‌ی مشخص.
     *
     * @return array<int, array{query:string,page:string,clicks:int,impressions:int,ctr:float,position:float}>
     */
    public static function fetchQueries(int $days = 28, int $rowLimit = 5000): array
    {
        if (! static::isConfigured()) {
            throw new RuntimeException('سرچ کنسول پیکربندی نشده است (SEO_GSC_CREDENTIALS و SEO_GSC_SITE_URL).');
        }

        $token = static::accessToken();
        $site  = (string) config('seo.search_console.site_url');

        // گوگل داده‌ی ۲ تا ۳ روز اخیر را هنوز نهایی نکرده؛ بازه از سه روز پیش
        // به عقب شمرده می‌شود تا اعداد ناقص وارد گزارش نشوند.
        $end   = now()->subDays(3)->toDateString();
        $start = now()->subDays(3 + $days)->toDateString();

        $rows = [];
        $startRow = 0;

        do {
            $response = Http::timeout(60)
                ->withToken($token)
                ->post('https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($site) . '/searchAnalytics/query', [
                    'startDate'  => $start,
                    'endDate'    => $end,
                    'dimensions' => ['query', 'page'],
                    'rowLimit'   => min(5000, $rowLimit),
                    'startRow'   => $startRow,
                    'dataState'  => 'final',
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('خطای سرچ کنسول: HTTP ' . $response->status() . ' — ' . mb_substr($response->body(), 0, 300));
            }

            $batch = $response->json('rows') ?? [];
            foreach ($batch as $row) {
                $rows[] = [
                    'query'       => (string) ($row['keys'][0] ?? ''),
                    'page'        => (string) ($row['keys'][1] ?? ''),
                    'clicks'      => (int) ($row['clicks'] ?? 0),
                    'impressions' => (int) ($row['impressions'] ?? 0),
                    'ctr'         => (float) ($row['ctr'] ?? 0),
                    'position'    => (float) ($row['position'] ?? 0),
                ];
            }

            $startRow += count($batch);
        } while (count($batch) === 5000 && $startRow < $rowLimit);

        return $rows;
    }

    /**
     * توکن دسترسی از روی Service Account. ۵۰ دقیقه کش می‌شود (اعتبارش یک
     * ساعت است).
     */
    private static function accessToken(): string
    {
        return Cache::remember('gsc:access-token', 3000, function () {
            $credentials = json_decode((string) file_get_contents((string) config('seo.search_console.credentials')), true);
            if (! is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
                throw new RuntimeException('فایل Service Account معتبر نیست.');
            }

            $now = time();
            $header = static::b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = static::b64(json_encode([
                'iss'   => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud'   => self::TOKEN_URL,
                'iat'   => $now,
                'exp'   => $now + 3600,
            ]));

            $signature = '';
            $key = openssl_pkey_get_private($credentials['private_key']);
            if ($key === false || ! openssl_sign($header . '.' . $claims, $signature, $key, OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('امضای JWT ناموفق بود؛ private_key خراب است.');
            }

            $jwt = $header . '.' . $claims . '.' . static::b64($signature);

            $response = Http::timeout(30)->asForm()->post(self::TOKEN_URL, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if (! $response->successful() || ! $response->json('access_token')) {
                throw new RuntimeException('گرفتن توکن از گوگل ناموفق بود: HTTP ' . $response->status() . ' — ' . mb_substr($response->body(), 0, 300));
            }

            return (string) $response->json('access_token');
        });
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * خواندن CSV خروجی سرچ کنسول.
     *
     * ستون‌ها بسته به زبان رابط فرق می‌کنند («Top queries» / «پرس‌وجوهای
     * برتر») ولی ترتیبشان ثابت است: عبارت، کلیک، نمایش، CTR، رتبه. اگر
     * خروجی «Pages» باشد ستون اول آدرس است و اینجا رد می‌شود.
     *
     * @return array<int, array{query:string,page:string,clicks:int,impressions:int,ctr:float,position:float}>
     */
    public static function parseCsv(string $contents): array
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        $lines = preg_split('/\r\n|\r|\n/', (string) $contents);

        $rows = [];
        $first = true;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cells = str_getcsv($line);
            if ($first) {
                $first = false;
                // سطر عنوان: عددی نیست
                if (! is_numeric(str_replace([',', '٬'], '', (string) ($cells[1] ?? '')))) {
                    continue;
                }
            }

            if (count($cells) < 5) {
                continue;
            }

            $query = trim((string) $cells[0]);
            if ($query === '' || preg_match('#^https?://#i', $query)) {
                continue;
            }

            $rows[] = [
                'query'       => $query,
                'page'        => '',
                'clicks'      => (int) static::number($cells[1]),
                'impressions' => (int) static::number($cells[2]),
                'ctr'         => static::percent($cells[3]),
                'position'    => (float) static::number($cells[4]),
            ];
        }

        return $rows;
    }

    private static function number(string $value): float
    {
        $value = strtr($value, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٫' => '.', '٬' => '', ',' => '']);

        return (float) preg_replace('/[^0-9.\-]/', '', $value);
    }

    private static function percent(string $value): float
    {
        $n = static::number($value);

        // «12.5%» → 0.125؛ اگر از قبل نسبت باشد (0.125) دست نمی‌خورد
        return str_contains($value, '%') || $n > 1 ? $n / 100 : $n;
    }
}
