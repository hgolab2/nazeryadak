<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Facades\Http;

/**
 * ممیزی یک صفحه‌ی سایت برای یک عبارت هدف: عبارت در title، H1، description
 * و بدنه هست یا نه، و صفحه اصلا ایندکس‌پذیر هست یا نه.
 *
 * صفحه از روی HTTP خوانده می‌شود، نه از دیتابیس. دلیلش این است که title
 * و description در چهار جای مختلف ساخته می‌شوند (محصول، صفحه‌ی فرود،
 * مقاله، صفحات ثابت) و هرکدام قاعده‌ی خودشان را دارند؛ چیزی که گوگل
 * می‌بیند فقط HTML نهایی است، پس همان را می‌سنجیم.
 */
class PageAudit
{
    /**
     * @return array{
     *   http_status:int, page_title:?string, page_h1:?string, page_description:?string,
     *   in_title:bool, in_h1:bool, in_description:bool, body_hits:int, is_indexable:bool, error:?string
     * }
     */
    public static function run(string $url, string $keyword): array
    {
        $result = [
            'http_status'      => 0,
            'page_title'       => null,
            'page_h1'          => null,
            'page_description' => null,
            'in_title'         => false,
            'in_h1'            => false,
            'in_description'   => false,
            'body_hits'        => 0,
            'is_indexable'     => false,
            'error'            => null,
        ];

        $absolute = static::absolute($url);

        try {
            $response = Http::timeout(20)
                ->withHeaders(['User-Agent' => 'NazerYadak-SeoAudit/1.0'])
                ->withoutVerifying()
                ->get($absolute);
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();

            return $result;
        }

        $result['http_status'] = $response->status();
        if (! $response->successful()) {
            return $result;
        }

        $html = $response->body();

        $result['page_title']       = static::firstMatch('/<title[^>]*>(.*?)<\/title>/isu', $html);
        $result['page_h1']          = static::firstMatch('/<h1[^>]*>(.*?)<\/h1>/isu', $html);
        $result['page_description'] = static::metaContent($html, 'description');

        $robots = mb_strtolower((string) static::metaContent($html, 'robots'));
        $result['is_indexable'] = ! str_contains($robots, 'noindex');

        // canonical که به صفحه‌ی دیگری اشاره کند یعنی این صفحه خودش ایندکس نمی‌شود
        if (preg_match('/<link[^>]+rel=["\']canonical["\'][^>]*href=["\']([^"\']+)["\']/iu', $html, $m)) {
            $canonical = static::normalizePath($m[1]);
            if ($canonical !== '' && $canonical !== static::normalizePath($absolute)) {
                $result['is_indexable'] = false;
            }
        }

        $result['in_title']       = static::contains($result['page_title'], $keyword);
        $result['in_h1']          = static::contains($result['page_h1'], $keyword);
        $result['in_description'] = static::contains($result['page_description'], $keyword);

        $result['body_hits'] = static::countInBody($html, $keyword);

        return $result;
    }

    /**
     * آیا عبارت در متن هست؟
     *
     * تطبیق بر کلمه‌هاست نه رشته‌ی دقیق: «لنت ترمز جلو پژو ۲۰۶» عبارتِ
     * «لنت ترمز پژو ۲۰۶» را پوشش می‌دهد و گوگل هم همین‌طور می‌فهمد. هر دو
     * طرف یکسان‌سازی می‌شوند تا «ي» عربی و رقم فارسی تطبیق را نشکند.
     */
    public static function contains(?string $haystack, string $keyword): bool
    {
        if ($haystack === null || trim($haystack) === '') {
            return false;
        }

        $hay  = ' ' . Product::normalizeTerm(static::tokenize($haystack)) . ' ';
        $need = Product::normalizeTerm(static::tokenize($keyword));

        if ($need === '') {
            return false;
        }

        if (mb_strpos($hay, ' ' . $need . ' ') !== false) {
            return true;
        }

        foreach (array_filter(explode(' ', $need)) as $word) {
            if (mb_strpos($hay, ' ' . $word . ' ') === false) {
                return false;
            }
        }

        return true;
    }

    private static function countInBody(string $html, string $keyword): int
    {
        // اسکریپت، استایل و هدر/فوتر حساب نمی‌شوند؛ عبارتی که فقط در منوی
        // سایت تکرار شده «محتوا» نیست.
        $body = preg_replace('/<(script|style|noscript|header|footer|nav)[^>]*>.*?<\/\1>/isu', ' ', $html);
        $body = preg_replace('/<[^>]+>/u', ' ', (string) $body);
        $body = html_entity_decode((string) $body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $body = ' ' . Product::normalizeTerm(static::tokenize($body)) . ' ';

        $need = Product::normalizeTerm(static::tokenize($keyword));
        if ($need === '') {
            return 0;
        }

        return (int) mb_substr_count($body, ' ' . $need . ' ');
    }

    private static function firstMatch(string $pattern, string $html): ?string
    {
        if (! preg_match($pattern, $html, $m)) {
            return null;
        }

        $text = html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        return $text !== '' ? mb_substr($text, 0, 300) : null;
    }

    private static function metaContent(string $html, string $name): ?string
    {
        $patterns = [
            '/<meta[^>]+name=["\']' . preg_quote($name, '/') . '["\'][^>]+content=["\']([^"\']*)["\']/iu',
            '/<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']' . preg_quote($name, '/') . '["\']/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $m)) {
                $text = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                return $text !== '' ? mb_substr($text, 0, 500) : null;
            }
        }

        return null;
    }

    /** علائم نگارشی و نیم‌فاصله به فاصله، تا «کمک‌فنر» و «کمک فنر» یکی شوند. */
    private static function tokenize(string $text): string
    {
        $text = str_replace(["\u{200C}", '‌'], ' ', $text);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);

        return trim((string) $text);
    }

    public static function absolute(string $url): string
    {
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        return rtrim(seo_base_url(), '/') . '/' . ltrim($url, '/');
    }

    private static function normalizePath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '/';

        return rtrim(urldecode((string) $path), '/') ?: '/';
    }
}
