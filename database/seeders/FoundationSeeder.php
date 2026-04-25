<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FoundationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create NetLogin Brasil (Master Tenant)
        $masterTenant = Tenant::create([
            'name' => 'NetLogin Brasil',
            'slug' => 'netlogin-brasil',
            'tax_id' => '00000000000100',
            'is_master' => true,
        ]);

        // 2. Create Master Admin User
        User::create([
            'name' => 'NetLogin Master',
            'email' => 'admin@netlogin.com.br',
            'password' => Hash::make('password'),
            'tenant_id' => $masterTenant->id,
            'user_type' => 'admin',
            'email_verified_at' => now(),
        ]);
    }
}
