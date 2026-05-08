<?php

namespace Database\Seeders;

use App\Models\BookingAgent;
use Illuminate\Database\Seeder;

class BookingAgentSeeder extends Seeder
{
    public function run(): void
    {
        $agents = [
            ['name' => 'Majestic Tours', 'email' => 'majestic@clearboatbahamas.com', 'phone' => null, 'commission_percent' => 0],
            ['name' => 'Carnival Cruise Lines', 'email' => 'carnival@clearboatbahamas.com', 'phone' => null, 'commission_percent' => 0],
            ['name' => 'Royal Caribbean Cruise Lines', 'email' => 'royalcaribbean@clearboatbahamas.com', 'phone' => null, 'commission_percent' => 0],
            ['name' => 'Disney Cruise Lines', 'email' => 'disney@clearboatbahamas.com', 'phone' => null, 'commission_percent' => 0],
            ['name' => 'Rasanno Ltd', 'email' => 'rasanno@clearboatbahamas.com', 'phone' => null, 'commission_percent' => 0],
            ['name' => 'Island Routes', 'email' => 'islandroutes@clearboatbahamas.com', 'phone' => null, 'commission_percent' => 0],
            ['name' => 'Leisure Travel', 'email' => 'leisure@clearboatbahamas.com', 'phone' => null, 'commission_percent' => 0],
            ['name' => 'The Cove', 'email' => 'thecove@clearboatbahamas.com', 'phone' => null, 'commission_percent' => 0],
            ['name' => 'Bahamas A Sus Ordenes', 'email' => 'susordenes@clearboatbahamas.com', 'phone' => null, 'commission_percent' => 0],
        ];

        foreach ($agents as $agent) {
            BookingAgent::firstOrCreate(
                ['email' => $agent['email']],
                $agent
            );
        }
    }
}
