<?php

namespace Database\Seeders;

use App\Models\MoadianResult;
use Illuminate\Database\Seeder;

class MoadianResultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MoadianResult::factory()->count(5)->create();
    }
}
