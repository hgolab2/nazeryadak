<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use DOMDocument;
use DOMXPath;

class FetchIsacoImages extends Command
{
    protected $signature = 'products:fetch-images {--page=0 : Start from specific page (0=all)} {--force : Re-fetch even if image exists}';
    protected $description = 'Fetch product images from ISACO catalog by title matching';

    private array $isacoCatalog = [];
    private string $cacheFile = 'isaco_catalog.json';

    public function handle()
    {
        $this->info('=== دریافت تصاویر از ایساکو ===');

        $this->loadOrBuildCatalog();

        $this->matchAndDownload();
    }

    private function loadOrBuildCatalog(): void
    {
        $cachePath = storage_path('app/' . $this->cacheFile);

        if (file_exists($cachePath) && !$this->option('force')) {
            $this->isacoCatalog = json_decode(file_get_contents($cachePath), true) ?: [];
            $this->info("Loaded " . count($this->isacoCatalog) . " ISACO products from cache.");

            if (count($this->isacoCatalog) > 100) {
                return;
            }
        }

        $this->info("Scraping ISACO catalog...");
        $this->isacoCatalog = [];

        $totalPages = 17;
        $startPage = (int) $this->option('page');

        $bar = $this->output->createProgressBar($totalPages);
        $bar->start();

        for ($page = ($startPage ?: 1); $page <= $totalPages; $page++) {
            $products = $this->scrapeIsacoPage($page);
            $this->isacoCatalog = array_merge($this->isacoCatalog, $products);
            $bar->advance();
            usleep(500000);
        }

        $bar->finish();
        $this->newLine();

        file_put_contents($cachePath, json_encode($this->isacoCatalog, JSON_UNESCAPED_UNICODE));
        $this->info("Scraped " . count($this->isacoCatalog) . " ISACO products.");
    }

    private function scrapeIsacoPage(int $page): array
    {
        $url = 'https://isaco.ir/%D9%82%D8%B7%D8%B9%D8%A7%D8%AA?page=' . $page;

        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get($url);

            if (!$response->ok()) {
                $this->warn("Failed to fetch page {$page}: HTTP " . $response->status());
                return [];
            }

            $html = $response->body();
        } catch (\Throwable $e) {
            $this->warn("Error fetching page {$page}: " . $e->getMessage());
            return [];
        }

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);

        $products = [];

        $links = $xpath->query('//a[contains(@href, "قطعات") or contains(@href, "%D9%82%D8%B7%D8%B9%D8%A7%D8%AA")]');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');

            if (!preg_match('#/(\d{3,6})/#u', $href, $codeMatch)) {
                continue;
            }

            $img = $xpath->query('.//img', $link);
            $imgSrc = '';
            if ($img->length > 0) {
                $imgSrc = $img->item(0)->getAttribute('src');
                if (empty($imgSrc)) {
                    $imgSrc = $img->item(0)->getAttribute('data-src') ?: '';
                }
            }

            $title = trim($link->textContent);
            $title = preg_replace('/\s+/u', ' ', $title);

            if (empty($title) || mb_strlen($title) < 3 || empty($imgSrc)) {
                continue;
            }

            $code = $codeMatch[1];

            $fullImg = $imgSrc;
            if (!str_starts_with($imgSrc, 'http')) {
                $fullImg = 'https://www.isaco.ir' . $imgSrc;
            }
            $fullImg = str_replace('/thumbnail/', '/', $fullImg);

            $products[$code] = [
                'code' => $code,
                'title' => $title,
                'image' => $fullImg,
                'thumbnail' => str_contains($imgSrc, '/thumbnail/')
                    ? (str_starts_with($imgSrc, 'http') ? $imgSrc : 'https://www.isaco.ir' . $imgSrc)
                    : $fullImg,
            ];
        }

        return $products;
    }

    private array $wordIndex = [];
    private array $normalizedIsaco = [];

    private function matchAndDownload(): void
    {
        if (empty($this->isacoCatalog)) {
            $this->error("No ISACO catalog data available.");
            return;
        }

        $this->buildWordIndex();

        $products = Product::where(function ($q) {
            $q->whereNull('file_path')->orWhere('file_path', '');
        })->get();

        $this->info("Matching " . $products->count() . " products...");

        if (!Storage::disk('public')->exists('products')) {
            Storage::disk('public')->makeDirectory('products');
        }

        $matched = 0;
        $failed = 0;
        $noMatch = 0;
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            $best = $this->findBestMatch($product->title);

            if ($best) {
                $saved = $this->downloadImage($best['image'], $product->id);
                if ($saved) {
                    $product->update(['file_path' => '/storage/' . $saved]);
                    $matched++;
                } else {
                    $failed++;
                }
            } else {
                $noMatch++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done! Matched: {$matched}, Download failed: {$failed}, No match: {$noMatch}");
    }

    private function buildWordIndex(): void
    {
        $this->info("Building search index...");

        foreach ($this->isacoCatalog as $code => $isaco) {
            $normalized = $this->normalizeTitle($isaco['title']);
            $this->normalizedIsaco[$code] = $normalized;

            $words = explode(' ', $normalized);
            foreach ($words as $word) {
                if (mb_strlen($word) >= 3) {
                    $this->wordIndex[$word][$code] = true;
                }
            }
        }
    }

    private function findBestMatch(string $title): ?array
    {
        $normalized = $this->normalizeTitle($title);
        $words = explode(' ', $normalized);
        $significantWords = array_filter($words, fn($w) => mb_strlen($w) >= 3);

        if (empty($significantWords)) {
            return null;
        }

        $candidateScores = [];
        foreach ($significantWords as $word) {
            if (isset($this->wordIndex[$word])) {
                foreach ($this->wordIndex[$word] as $code => $_) {
                    $candidateScores[$code] = ($candidateScores[$code] ?? 0) + 1;
                }
            }
        }

        if (empty($candidateScores)) {
            return null;
        }

        arsort($candidateScores);
        $topCandidates = array_slice(array_keys($candidateScores), 0, 10, true);

        $bestScore = 0;
        $bestMatch = null;
        $wordCount = count($significantWords);

        foreach ($topCandidates as $code) {
            $isacoNorm = $this->normalizedIsaco[$code];

            $matchedWords = 0;
            foreach ($significantWords as $word) {
                if (mb_strpos($isacoNorm, $word) !== false) {
                    $matchedWords++;
                }
            }

            $wordScore = ($matchedWords / $wordCount) * 100;

            similar_text($normalized, $isacoNorm, $strPercent);

            $score = max($wordScore, $strPercent);

            if ($score > $bestScore && $score >= 45) {
                $bestScore = $score;
                $bestMatch = $this->isacoCatalog[$code];
            }
        }

        return $bestMatch;
    }

    private function normalizeTitle(string $title): string
    {
        $title = str_replace(['ي', 'ك', 'ة'], ['ی', 'ک', 'ه'], $title);
        $title = preg_replace('/[\-_()\.،,]/u', ' ', $title);
        $title = preg_replace('/\s+/u', ' ', $title);
        return trim($title);
    }

    private function downloadImage(string $url, int $productId): ?string
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get($url);

            if (!$response->ok()) {
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
