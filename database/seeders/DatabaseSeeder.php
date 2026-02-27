<?php
// database/seeders/DatabaseSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cash;
use App\Models\Type;
use App\Models\Category;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ١. دروستکردنی جۆرەکان
        $this->seedTypes();

        // ٢. دروستکردنی قاسە
        $this->seedCash();

        // ٣. دروستکردنی کاتیگۆریەکان
        $this->seedCategories();
    }

    private function seedTypes()
    {
        $types = [
            [
                'name' => 'بەنزین',
                'key' => 'fuel',
                'color' => 'warning',
                'description' => 'هەموو جۆرەکانی بەنزین'
            ],
            [
                'name' => 'ڕۆن',
                'key' => 'oil',
                'color' => 'success',
                'description' => 'ڕۆنی مەکینە و گیربۆکس'
            ],
            [
                'name' => 'گاز',
                'key' => 'gas',
                'color' => 'info',
                'description' => 'گازی پێکهاتوو و گازی شل'
            ],
        ];

        foreach ($types as $type) {
            Type::firstOrCreate(
                ['key' => $type['key']],
                $type
            );
        }

        $this->command->info('✅ جۆرەکان بە سەرکەوتوویی دروستکران');
    }

    private function seedCash()
    {
        if (Cash::count() == 0) {
            Cash::create([
                'balance' => 1000000,
                'total_income' => 0,
                'total_expense' => 0,
                'last_update' => now(),
            ]);
            $this->command->info('✅ قاسە بە سەرکەوتوویی دروستکرا');
        }
    }

    private function seedCategories()
    {
        if (Category::count() == 0) {
            $fuelType = Type::where('key', 'fuel')->first();
            $oilType = Type::where('key', 'oil')->first();
            $gasType = Type::where('key', 'gas')->first();

            Category::create([
                'name' => 'بەنزین 95',
                'type_id' => $fuelType->id,
                'current_price' => 1250,
                'purchase_price' => 1150,
                'stock_liters' => 0,
            ]);

            Category::create([
                'name' => 'بەنزین 92',
                'type_id' => $fuelType->id,
                'current_price' => 1150,
                'purchase_price' => 1050,
                'stock_liters' => 0,
            ]);

            Category::create([
                'name' => 'ڕۆنی مەکینە 20W50',
                'type_id' => $oilType->id,
                'current_price' => 15000,
                'purchase_price' => 13000,
                'stock_liters' => 0,
            ]);

            Category::create([
                'name' => 'گازی پێکهاتوو',
                'type_id' => $gasType->id,
                'current_price' => 1000,
                'purchase_price' => 850,
                'stock_liters' => 0,
            ]);

            $this->command->info('✅ کاتیگۆریەکان بە سەرکەوتوویی دروستکران');
        }
    }
}
