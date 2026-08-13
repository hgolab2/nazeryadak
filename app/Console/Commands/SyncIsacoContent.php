<?php

namespace App\Console\Commands;

use App\Models\Product;
use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SyncIsacoContent extends Command
{
    protected $signature = 'products:sync-isaco-content
        {--apply : Write changes to database}
        {--limit=0 : Limit matched ISACO codes}
        {--code= : Sync one ISACO code only}
        {--force : Re-download and rewrite existing local files}';

    protected $description = 'Sync ISACO gallery images and clean product descriptions by SKU prefix';

    private string $baseUrl = 'https://isaco.ir';

    public function handle(): int
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        DB::disableQueryLog();

        $apply = (bool) $this->option('apply');
        $codes = $this->targetCodes();

        $this->info(($apply ? 'APPLY' : 'DRY RUN') . ' - target ISACO codes: ' . count($codes));

        $done = $matchedProducts = $withGallery = $withDescription = $failed = 0;
        foreach ($codes as $code) {
            $products = Product::where('sku', 'like', $code . '%')->get();
            if ($products->isEmpty()) {
                continue;
            }

            try {
                $content = $this->fetchProductContent($code);
                if (!$content) {
                    $failed++;
                    $this->warn("{$code}: no content");
                    continue;
                }

                $done++;
                $matchedProducts += $products->count();
                if ($content['gallery']) {
                    $withGallery++;
                }
                if ($content['description']) {
                    $withDescription++;
                }

                if ($apply) {
                    $galleryPaths = $this->downloadImages($code, $content['gallery'], 'gallery');
                    $inlinePaths = $this->downloadImages($code, $content['inline_images'], 'content');
                    $description = $this->buildDescriptionHtml($content, $inlinePaths);

                    foreach ($products as $product) {
                        $product->update([
                            'short_description' => $content['short_description'],
                            'description' => $description,
                            'isaco_code' => $code,
                            'isaco_url' => $content['url'],
                            'file_path' => $galleryPaths[0] ?? $product->file_path,
                        ]);

                        if ($galleryPaths) {
                            DB::table('product_images')->where('product_id', $product->id)->delete();
                            foreach ($galleryPaths as $index => $path) {
                                DB::table('product_images')->insert([
                                    'product_id' => $product->id,
                                    'path' => $path,
                                    'alt' => $content['title'],
                                    'is_primary' => $index === 0 ? 1 : 0,
                                    'sort_order' => $index + 1,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }
                }

                $this->line("{$code}: products={$products->count()} gallery=" . count($content['gallery']) . ' inline=' . count($content['inline_images']));
                usleep(200000);
            } catch (\Throwable $e) {
                $failed++;
                $this->warn("{$code}: " . $e->getMessage());
            }
        }

        $this->info("Done. codes={$done}, products={$matchedProducts}, with_gallery={$withGallery}, with_description={$withDescription}, failed={$failed}");

        return self::SUCCESS;
    }

    private function targetCodes(): array
    {
        if ($this->option('code')) {
            return [(string) $this->option('code')];
        }

        $codes = Product::whereRaw("sku REGEXP '^[0-9]{5}'")
            ->selectRaw('LEFT(sku, 5) as code')
            ->distinct()
            ->orderBy('code')
            ->pluck('code')
            ->all();

        $limit = (int) $this->option('limit');
        return $limit > 0 ? array_slice($codes, 0, $limit) : $codes;
    }

    private function fetchProductContent(string $code): ?array
    {
        $searchUrl = $this->baseUrl . '/%D9%82%D8%B7%D8%B9%D8%A7%D8%AA/' . $code;
        $response = Http::timeout(30)->withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($searchUrl);
        if (!$response->ok()) {
            return null;
        }

        $finalUrl = (string) $response->effectiveUri();
        $html = $response->body();
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);

        $title = trim(preg_replace('/\s+/u', ' ', $xpath->query('//h1')->item(0)?->textContent ?? ''));
        if ($title === '') {
            return null;
        }

        $gallery = [];
        $inline = [];
        foreach ($xpath->query('//img') as $img) {
            $src = $img->getAttribute('src') ?: $img->getAttribute('data-src') ?: '';
            if ($src === '') {
                continue;
            }

            $full = str_starts_with($src, 'http') ? $src : $this->baseUrl . $src;
            if (str_contains($src, '/parts/images/' . $code . '/')) {
                $gallery[] = $full;
            } elseif (str_contains($src, '/editors/')) {
                $inline[] = $full;
            }
        }

        $paragraphs = $this->extractCleanTextBlocks($xpath);
        $short = $paragraphs[0] ?? '';

        return [
            'code' => $code,
            'url' => $finalUrl,
            'title' => $title,
            'short_description' => $short,
            'description' => $paragraphs,
            'gallery' => array_values(array_unique($gallery)),
            'inline_images' => array_values(array_unique($inline)),
        ];
    }

    private function extractCleanTextBlocks(DOMXPath $xpath): array
    {
        $blocked = ['صفحه اصلی', 'نمایندگی', 'فروشگاه های', 'فروشگاه‌ها', 'منوهای اصلی', 'تماس باما', 'ایران، تهران', 'ورود | ثبت نام'];
        $items = [];

        foreach ($xpath->query('//p|//li') as $node) {
            $text = trim(preg_replace('/\s+/u', ' ', $node->textContent));
            if (mb_strlen($text) < 25) {
                continue;
            }
            foreach ($blocked as $bad) {
                if (str_contains($text, $bad)) {
                    continue 2;
                }
            }
            if (preg_match('/^\.(mui|css|swiper|slick|Mui)/i', $text)) {
                continue;
            }
            $items[] = $text;
        }

        return array_values(array_unique(array_slice($items, 0, 20)));
    }

    private function downloadImages(string $code, array $urls, string $prefix): array
    {
        $paths = [];
        $dir = public_path('upload/products/isaco/' . $code);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        foreach ($urls as $index => $url) {
            $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = $prefix . '-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . '.' . strtolower($ext);
            $local = $dir . DIRECTORY_SEPARATOR . $filename;
            if ($this->option('force') || !file_exists($local)) {
                $response = Http::timeout(30)->withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($url);
                if (!$response->ok() || strlen($response->body()) < 1000) {
                    continue;
                }
                file_put_contents($local, $response->body());
            }
            $paths[] = '/upload/products/isaco/' . $code . '/' . $filename;
        }

        return $paths;
    }

    private function buildDescriptionHtml(array $content, array $inlinePaths): string
    {
        $html = '<h2>' . e($content['title']) . '</h2>';
        foreach ($content['description'] as $index => $text) {
            $html .= '<p>' . e($text) . '</p>';
            if ($index === 1 && isset($inlinePaths[0])) {
                $html .= '<figure><img src="' . e($inlinePaths[0]) . '" alt="' . e($content['title']) . '"></figure>';
            }
            if ($index === 5 && isset($inlinePaths[1])) {
                $html .= '<figure><img src="' . e($inlinePaths[1]) . '" alt="' . e($content['title']) . '"></figure>';
            }
        }

        return $html;
    }
}
