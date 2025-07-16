<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class PayoutProfileTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // DB::table('payout_profile_templates')->insert([
        //     [
        //         'name' => 'default',
        //         'description' => 'Standard payout profile with higher multipliers',
        //         'profile' => json_encode(['2D' => 90, '3D' => 800]),
        //         'max_commission_sharing_rate' => 50.00,
        //         'is_default' => true,
        //         'is_active' => true,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'name' => 'conservative',
        //         'description' => 'Conservative payout profile with lower risk',
        //         'profile' => json_encode(['2D' => 70, '3D' => 600]),
        //         'max_commission_sharing_rate' => 25.00,
        //         'is_default' => false,
        //         'is_active' => true,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'name' => 'aggressive',
        //         'description' => 'High risk, high reward payout profile',
        //         'profile' => json_encode(['2D' => 95, '3D' => 900]),
        //         'max_commission_sharing_rate' => 60.00,
        //         'is_default' => false,
        //         'is_active' => true,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        // ]);
    }
}
