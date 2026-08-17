<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductCategorizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CategorizeProducts extends Command
{
    protected $signature = 'products:categorize {--missing : فقط محصولاتی که هیچ مجموعه‌بندی ندارند}';
    protected $description = 'Categorize products based on Persian part title keywords';

    public function __construct(private ProductCategorizer $categorizer)
    {
        parent::__construct();
    }

    public function handle()
    {
        $groupingIds = ProductCategorizer::groupingIds();

        $orphaned = DB::table('product_in_category')
            ->whereNotIn('product_id', Product::pluck('id'))
            ->delete();
        $this->info("Deleted {$orphaned} orphaned records.");

        // با --missing مجموعه‌بندی‌های موجود (از جمله انتخاب‌های دستی مدیر)
        // دست‌نخورده می‌مانند و فقط جای خالی‌ها پر می‌شود.
        $onlyMissing = (bool) $this->option('missing');

        $products = Product::select(['id', 'title'])->get();

        if ($onlyMissing) {
            $categorized = DB::table('product_in_category')
                ->whereIn('category_id', $groupingIds)
                ->distinct()
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->flip();

            $products = $products->reject(fn ($product) => $categorized->has((int) $product->id))->values();
        } else {
            DB::table('product_in_category')->whereIn('category_id', $groupingIds)->delete();
        }

        $assigned = 0;
        $other = 0;
        $rows = [];
        $now = now();

        foreach ($products as $product) {
            $categoryId = $this->categorizer->detect((string) $product->title);
            $rows[] = [
                'product_id' => $product->id,
                'category_id' => $categoryId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $categoryId === 11 ? $other++ : $assigned++;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('product_in_category')->insert($chunk);
        }

        $this->info("Categorized {$assigned} products. {$other} assigned to OTHER.");

        $stats = DB::table('product_in_category')
            ->whereIn('category_id', $groupingIds)
            ->select('category_id', DB::raw('count(*) as cnt'))
            ->groupBy('category_id')
            ->orderBy('category_id')
            ->get();

        $this->table(['Category ID', 'Count'], $stats->map(fn ($s) => [$s->category_id, $s->cnt]));
    }
}
