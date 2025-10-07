<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'user tester ajammm',
                'email' => 'user@quranicare.com',
                'password' => Hash::make('user123'),
                'birth_date' => Carbon::parse('1995-06-15'),
                'gender' => 'male',
                'phone_number' => '+6281234567801',
                'bio' => 'Mahasiswa teknik informatika yang sedang belajar untuk menjadi Muslim yang lebih baik'
            ],
            [
                'name' => 'Abdullah Rahman',
                'email' => 'abdullah.rahman@email.com',
                'password' => Hash::make('user123'),
                'birth_date' => Carbon::parse('1995-06-15'),
                'gender' => 'male',
                'phone_number' => '+6281234567801',
                'bio' => 'Mahasiswa teknik informatika yang sedang belajar untuk menjadi Muslim yang lebih baik'
            ],
            [
                'name' => 'admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('admin123'),
                'birth_date' => Carbon::parse('1995-06-15'),
                'gender' => 'male',
                'phone_number' => '+6281234567801',
                'bio' => 'Mahasiswa teknik informatika yang sedang belajar untuk menjadi Muslim yang lebih baik'
            ],
            [
                'name' => 'Fatimah Azzahra',
                'email' => 'fatimah.azzahra@email.com',
                'password' => Hash::make('password123'),
                'birth_date' => Carbon::parse('1992-03-22'),
                'gender' => 'female',
                'phone_number' => '+6281234567802',
                'bio' => 'Ibu rumah tangga yang senang belajar ilmu agama dan mengaji Al-Quran'
            ],
            [
                'name' => 'Muhammad Yusuf',
                'email' => 'muhammad.yusuf@email.com',
                'password' => Hash::make('password123'),
                'birth_date' => Carbon::parse('1988-11-08'),
                'gender' => 'male',
                'phone_number' => '+6281234567803',
                'bio' => 'Pengusaha muda yang ingin menyeimbangkan kehidupan dunia dan akhirat'
            ],
            [
                'name' => 'Khadijah Binti Ahmad',
                'email' => 'khadijah.ahmad@email.com',
                'password' => Hash::make('password123'),
                'birth_date' => Carbon::parse('1990-09-14'),
                'gender' => 'female',
                'phone_number' => '+6281234567804',
                'bio' => 'Guru sekolah dasar yang gemar mengajarkan nilai-nilai Islam kepada anak-anak'
            ],
            [
                'name' => 'Ali Bin Abu Thalib',
                'email' => 'ali.abuthalib@email.com',
                'password' => Hash::make('password123'),
                'birth_date' => Carbon::parse('1985-12-25'),
                'gender' => 'male',
                'phone_number' => '+6281234567805',
                'bio' => 'Pekerja kantoran yang ingin meningkatkan kualitas ibadah dan spiritualitas'
            ]
        ];

        foreach ($users as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'email_verified_at' => Carbon::now()->subDays(rand(1, 30)),
                'password' => $userData['password'],
                'birth_date' => $userData['birth_date'],
                'gender' => $userData['gender'],
                'phone' => $userData['phone_number'],
                'profile_picture' => null,
                'bio' => $userData['bio'],
                'preferred_language' => 'id',
                'is_active' => true,
                'last_login_at' => Carbon::now()->subDays(rand(0, 7)),
                'created_at' => Carbon::now()->subDays(rand(7, 60)),
                'updated_at' => Carbon::now()->subDays(rand(0, 7))
            ]);
        }

        $this->command->info('Sample users seeded successfully!');
        $this->command->info('Created 10 sample users with Islamic names and profiles');
        $this->command->info('Default password for all users: password123');
    }
}