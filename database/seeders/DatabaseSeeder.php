<?php

namespace Database\Seeders;

use App\Models\Container;
use App\Models\TrackingLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users
        User::create([
            'name'     => 'Admin WowoClean',
            'email'    => 'admin@wowoclean.com',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        User::create([
            'name'     => 'Operator Lapangan',
            'email'    => 'user@wowoclean.com',
            'password' => Hash::make('password123'),
            'role'     => 'user',
        ]);

        // Containers + Logs
        $c1 = Container::create([
            'container_id' => 'WH00001',
            'waste_type'   => 'Chemical',
            'weight_kg'    => 850,
            'status'       => 'Active',
        ]);
        TrackingLog::insert([
            ['container_id' => $c1->id, 'location' => 'Warehouse A',  'timestamp' => '2026-04-15 08:00:00', 'description' => 'Container received at warehouse', 'created_at' => now(), 'updated_at' => now()],
            ['container_id' => $c1->id, 'location' => 'Checkpoint 1', 'timestamp' => '2026-04-15 12:30:00', 'description' => 'Weight verification completed',   'created_at' => now(), 'updated_at' => now()],
        ]);

        $c2 = Container::create([
            'container_id' => 'GD00002',
            'waste_type'   => 'Plastic',
            'weight_kg'    => 1200,
            'status'       => 'Active',
        ]);
        TrackingLog::insert([
            ['container_id' => $c2->id, 'location' => 'Warehouse B', 'timestamp' => '2026-04-15 09:10:00', 'description' => 'Container packed', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $c3 = Container::create([
            'container_id' => 'AB00003',
            'waste_type'   => 'Metal',
            'weight_kg'    => 500,
            'status'       => 'Archived',
        ]);
        TrackingLog::insert([
            ['container_id' => $c3->id, 'location' => 'Warehouse C', 'timestamp' => '2026-04-14 10:00:00', 'description' => 'Archived after final disposal', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}