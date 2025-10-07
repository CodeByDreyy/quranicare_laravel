<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin
        Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@quranicare.com',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('admin123'),
            'role' => 'super_admin',
            'permissions' => json_encode([
                'manage_admins' => true,
                'manage_content' => true,
                'manage_users' => true,
                'view_analytics' => true,
                'moderate_content' => true
            ]),
            'is_active' => true,
            'last_login_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        // Create Content Admin
        Admin::create([
            'name' => 'Ahmad Fauzi',
            'email' => 'ahmad.fauzi@quranicare.com',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('contentmanager123'),
            'role' => 'content_admin',
            'permissions' => json_encode([
                'manage_content' => true,
                'view_analytics' => true,
                'moderate_content' => true
            ]),
            'is_active' => true,
            'last_login_at' => Carbon::now()->subDays(1),
            'created_at' => Carbon::now()->subMonths(2),
            'updated_at' => Carbon::now()->subDays(1)
        ]);

        // Create Moderator
        Admin::create([
            'name' => 'Siti Nurhaliza',
            'email' => 'siti.nurhaliza@quranicare.com',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('moderator123'),
            'role' => 'moderator',
            'permissions' => json_encode([
                'moderate_content' => true,
                'view_analytics' => false
            ]),
            'is_active' => true,
            'last_login_at' => Carbon::now()->subHours(5),
            'created_at' => Carbon::now()->subMonths(1),
            'updated_at' => Carbon::now()->subHours(5)
        ]);

        $this->command->info('Admin users seeded successfully!');
        $this->command->info('Admin accounts created in admins table:');
        $this->command->info('1. Super Admin: admin@quranicare.com (admin123) - super_admin');
        $this->command->info('2. Content Manager: ahmad.fauzi@quranicare.com (contentmanager123) - content_admin');
        $this->command->info('3. Moderator: siti.nurhaliza@quranicare.com (moderator123) - moderator');
    }
}
