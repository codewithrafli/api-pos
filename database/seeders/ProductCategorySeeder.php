<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('product_categories')->insert([
            [
                'name' => 'Makanan Ringan',
                'description' => 'Berbagai jenis snack seperti keripik, biskuit, dan makanan ringan kemasan.'
            ],
            [
                'name' => 'Minuman',
                'description' => 'Minuman kemasan seperti air mineral, teh, kopi, dan minuman bersoda.'
            ],
            [
                'name' => 'Sembako',
                'description' => 'Kebutuhan pokok sehari-hari seperti beras, gula, minyak goreng, dan telur.'
            ],
            [
                'name' => 'Produk Susu',
                'description' => 'Susu cair, susu bubuk, susu kental manis, dan produk olahan susu lainnya.'
            ],
            [
                'name' => 'Makanan Instan',
                'description' => 'Mie instan, sereal, dan makanan siap saji lainnya.'
            ],
            [
                'name' => 'Produk Kebersihan',
                'description' => 'Sabun, sampo, pasta gigi, dan produk kebersihan diri.'
            ],
            [
                'name' => 'Perlengkapan Rumah Tangga',
                'description' => 'Deterjen, pembersih lantai, pewangi pakaian, dan kebutuhan rumah tangga lainnya.'
            ],
            [
                'name' => 'Rokok',
                'description' => 'Berbagai merek rokok dan produk tembakau.'
            ],
            [
                'name' => 'Obat & Kesehatan',
                'description' => 'Obat-obatan ringan, vitamin, dan alat kesehatan sederhana.'
            ],
            [
                'name' => 'Es Krim & Makanan Beku',
                'description' => 'Es krim dan produk makanan beku siap saji.'
            ],
        ]);
    }
}
