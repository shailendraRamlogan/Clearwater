<?php

namespace Database\Seeders;

use App\Models\BookingFee;
use Illuminate\Database\Seeder;

class BookingFeeSeeder extends Seeder
{
    public function run(): void
    {
        BookingFee::create([
            'name' => 'Service Fee',
            'type' => 'percent',
            'value' => 5.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }
}
