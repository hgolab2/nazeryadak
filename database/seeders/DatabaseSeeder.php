<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * دیتابیس برنامه را با داده‌های اولیه پر کنید.
     */
    public function run(): void
    {
        // اضافه کردن ProductSeeder
        $this->call(ProductSeeder::class);
    }
}
