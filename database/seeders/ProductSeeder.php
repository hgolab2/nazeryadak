<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::truncate();

        $productsData = [
            [
                'title' => 'لنت ترمز جلو پژو 206',
                'description' => 'لنت ترمز دیسکی چرخ جلو مناسب پژو 206 و رانا. دارای استاندارد کیفیت و تضمین اصالت.',
                'price' => 850000,
                'regular_price' => 950000,
                'stock' => 50,
                'is_active' => true,
                'car_model' => 'پژو 206',
            ],
            [
                'title' => 'فیلتر روغن موتور سمند EF7',
                'description' => 'فیلتر روغن با کیفیت مناسب موتورهای EF7. قابل استفاده در سمند، دنا و پژو پارس.',
                'price' => 120000,
                'regular_price' => 150000,
                'stock' => 200,
                'is_active' => true,
                'car_model' => 'سمند',
            ],
            [
                'title' => 'دیسک و صفحه کلاچ پراید',
                'description' => 'مجموعه کامل دیسک و صفحه کلاچ مناسب پراید 131 و 111.',
                'price' => 3200000,
                'regular_price' => 3500000,
                'stock' => 30,
                'is_active' => true,
                'car_model' => 'پراید',
            ],
            [
                'title' => 'رادیاتور آب تیبا',
                'description' => 'رادیاتور آب آلومینیومی مناسب تیبا 1 و تیبا 2.',
                'price' => 4500000,
                'regular_price' => 5000000,
                'stock' => 15,
                'is_active' => true,
                'car_model' => 'تیبا',
            ],
            [
                'title' => 'چراغ جلو پژو پارس',
                'description' => 'چراغ جلو کامل مناسب پژو پارس با لامپ هالوژن.',
                'price' => 2800000,
                'stock' => 0,
                'is_active' => false,
                'car_model' => 'پژو پارس',
            ],
        ];

        foreach ($productsData as $data) {
            $data['slug'] = Str::slug($data['title']);
            $data['sku'] = 'YDK-' . rand(10000, 99999);
            Product::create($data);
        }
    }
}
