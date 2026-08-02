<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use DOMDocument;
use DOMXPath;

class IsacoImageService
{
    private static ?array $catalog = null;
    private static array $wordIndex = [];
    private static array $normalizedTitles = [];

    public function fetchForProduct(Product $product): array
    {
        $result = ['image' => null, 'description' => null];

        $hasImage = $product->file_path && $product->file_path !== '/images/no-image.svg';
        $hasDesc = !empty($product->description);

        if ($hasImage && $hasDesc) {
            return $result;
        }

        $this->loadCatalog();

        if (empty(self::$catalog)) {
            return $result;
        }

        $match = $this->findBestMatch($product->title);
        if (!$match) {
            return $result;
        }

        $updateData = [];

        if (!$hasImage) {
            $localPath = $this->downloadImage($match['image'], $product->id);
            if ($localPath) {
                $updateData['file_path'] = '/storage/' . $localPath;
                $result['image'] = '/storage/' . $localPath;
            }
        }

        if (!$hasDesc && isset($match['code'])) {
            $desc = $this->fetchDescription($match['code'], $match['title']);
            if ($desc) {
                $updateData['description'] = $desc;
                $result['description'] = $desc;
            }
        }

        if (!empty($updateData)) {
            $product->update($updateData);
        }

        return $result;
    }

    private function fetchDescription(string $code, string $title): ?string
    {
        try {
            $slug = str_replace(' ', '-', $title);
            $url = 'https://isaco.ir/%D9%82%D8%B7%D8%B9%D8%A7%D8%AA/' . $code . '/' . urlencode($slug);

            $response = Http::timeout(20)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])
                ->get($url);

            if (!$response->ok()) {
                return null;
            }

            $dom = new DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $response->body());
            $xpath = new DOMXPath($dom);

            $paragraphs = $xpath->query('//p');
            $texts = [];

            foreach ($paragraphs as $p) {
                $text = trim($p->textContent);
                if (mb_strlen($text) > 30 && !str_contains($text, 'isaco') && !str_contains($text, 'ایساکو')) {
                    $texts[] = $text;
                }
            }

            if (empty($texts)) {
                return null;
            }

            $description = implode("\n", array_slice($texts, 0, 5));

            if (mb_strlen($description) < 50) {
                return null;
            }

            return $description;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function loadCatalog(): void
    {
        if (self::$catalog !== null) {
            return;
        }

        $cachePath = storage_path('app/isaco_catalog.json');

        if (file_exists($cachePath) && filemtime($cachePath) > time() - 86400 * 7) {
            self::$catalog = json_decode(file_get_contents($cachePath), true) ?: [];
        }

        if (empty(self::$catalog)) {
            self::$catalog = [];
            for ($page = 1; $page <= 17; $page++) {
                $products = $this->scrapeIsacoPage($page);
                self::$catalog = self::$catalog + $products;
                usleep(400000);
            }
            if (!empty(self::$catalog)) {
                file_put_contents($cachePath, json_encode(self::$catalog, JSON_UNESCAPED_UNICODE));
            }
        }

        $this->buildWordIndex();
    }

    private function scrapeIsacoPage(int $page): array
    {
        try {
            $url = 'https://isaco.ir/%D9%82%D8%B7%D8%B9%D8%A7%D8%AA?page=' . $page;
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])
                ->get($url);

            if (!$response->ok()) {
                return [];
            }
        } catch (\Throwable $e) {
            return [];
        }

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $response->body());
        $xpath = new DOMXPath($dom);
        $products = [];

        $links = $xpath->query('//a');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');

            $decodedHref = urldecode($href);
            if (!preg_match('#/قطعات/(\d{4,6})/[^\d]#u', $decodedHref, $m)
                && !preg_match('#/%D9%82%D8%B7%D8%B9%D8%A7%D8%AA/(\d{4,6})/[^\d]#', $href, $m)) {
                continue;
            }

            $code = $m[1];
            if (strlen($code) < 4) {
                continue;
            }

            if (isset($products[$code])) {
                continue;
            }

            $img = $xpath->query('.//img', $link);
            $imgSrc = '';
            if ($img->length > 0) {
                $imgSrc = $img->item(0)->getAttribute('src')
                    ?: $img->item(0)->getAttribute('data-src')
                    ?: '';
            }
            if (empty($imgSrc) || !str_contains($imgSrc, '/sImage/')) {
                continue;
            }

            $titleFromUrl = '';
            if (preg_match('#/\d{4,6}/(.+)$#u', $decodedHref, $titleMatch)) {
                $titleFromUrl = str_replace('-', ' ', urldecode($titleMatch[1]));
            }

            $textContent = trim(preg_replace('/\s+/u', ' ', $link->textContent));

            $title = mb_strlen($textContent) > 5 ? $textContent : $titleFromUrl;
            if (empty($title) || mb_strlen($title) < 4) {
                continue;
            }

            $fullImg = str_starts_with($imgSrc, 'http') ? $imgSrc : 'https://www.isaco.ir' . $imgSrc;
            $fullImg = str_replace('/thumbnail/', '/', $fullImg);

            $products[$code] = [
                'code' => $code,
                'title' => $title,
                'image' => $fullImg,
            ];
        }

        return $products;
    }

    private function buildWordIndex(): void
    {
        if (!empty(self::$wordIndex)) {
            return;
        }

        foreach (self::$catalog as $code => $item) {
            $normalized = $this->normalize($item['title']);
            self::$normalizedTitles[$code] = $normalized;

            foreach (explode(' ', $normalized) as $word) {
                if (mb_strlen($word) >= 3) {
                    self::$wordIndex[$word][$code] = true;
                }
            }
        }
    }

    private function findBestMatch(string $title): ?array
    {
        $normalized = $this->normalize($title);
        $words = array_filter(explode(' ', $normalized), fn($w) => mb_strlen($w) >= 3);

        if (count($words) < 2) {
            return null;
        }

        $candidates = [];
        foreach ($words as $word) {
            foreach (self::$wordIndex[$word] ?? [] as $code => $_) {
                $candidates[$code] = ($candidates[$code] ?? 0) + 1;
            }
        }

        if (empty($candidates)) {
            return null;
        }

        arsort($candidates);
        $topCodes = array_slice(array_keys($candidates), 0, 5);

        $bestScore = 0;
        $bestCode = null;
        $wordCount = count($words);

        foreach ($topCodes as $code) {
            $isacoNorm = self::$normalizedTitles[$code];

            $matchedWords = 0;
            foreach ($words as $w) {
                if (mb_strpos($isacoNorm, $w) !== false) {
                    $matchedWords++;
                }
            }

            $score = ($matchedWords / $wordCount) * 100;

            if ($score > $bestScore && $score >= 60 && $matchedWords >= 2) {
                $bestScore = $score;
                $bestCode = $code;
            }
        }

        return $bestCode ? self::$catalog[$bestCode] : null;
    }

    private function normalize(string $title): string
    {
        $title = str_replace(['ي', 'ك', 'ة'], ['ی', 'ک', 'ه'], $title);
        $title = preg_replace('/[\-_()\.،,:\d]/u', ' ', $title);
        $title = preg_replace('/\b(مجموعه|کامل|قطعه|سمت|راست|چپ|جلو|عقب|با|بدون|و|دست|عددی)\b/u', ' ', $title);
        $glued = ['ترمز', 'چرخ', 'سیلندر', 'موتور', 'کلاچ', 'گیربکس', 'رادیاتور', 'فرمان', 'دیسک', 'روغن', 'پمپ', 'سنسور', 'سوپاپ', 'لنت', 'کاسه', 'بلبرینگ', 'گردگیر', 'اگزوز', 'کویل', 'شیلنگ', 'فیلتر', 'بوستر', 'پدال', 'کالیپر'];
        foreach ($glued as $w) {
            $title = str_replace($w, ' ' . $w . ' ', $title);
        }
        $title = preg_replace('/\s+/u', ' ', $title);
        return trim($title);
    }

    private function downloadImage(string $url, int $productId): ?string
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get($url);

            if (!$response->ok() || strlen($response->body()) < 5000) {
                return null;
            }

            $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = "products/{$productId}.{$ext}";

            Storage::disk('public')->put($filename, $response->body());
            return $filename;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
