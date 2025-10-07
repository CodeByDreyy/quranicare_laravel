<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌟 Starting QuraniCare Database Seeding...');
        
        // Seeding order is important due to foreign key constraints
        $seeders = [
            // Core authentication and user data
            AdminSeeder::class,
            UserSeeder::class,
            
            // Content seeders (independent of users)
            QuranSeeder::class,              // Quran Surahs & Ayahs
            DzikirSeeder::class,             // Dzikir & Doa content
            AudioRelaxSeeder::class,         // Audio relax & nasheed
            BreathingExerciseSeeder::class,  // Breathing exercises
            PsychologyMaterialSeeder::class, // Psychology materials
            AiKnowledgeBaseSeeder::class,    // AI knowledge base
            NotificationSeeder::class,       // Notification templates
            
            // User dependent data (requires users to exist first)
            MoodSeeder::class,               // Mood tracking data
            SakinahTrackerSeeder::class,     // Activity tracking data
        ];

        foreach ($seeders as $seeder) {
            $seederName = class_basename($seeder);
            $this->command->info("📄 Running {$seederName}...");
            
            try {
                $this->call($seeder);
                $this->command->info("✅ {$seederName} completed successfully!");
            } catch (\Exception $e) {
                $this->command->error("❌ {$seederName} failed: " . $e->getMessage());
                $this->command->warn("Continuing with next seeder...");
            }
        }
        
        $this->command->info('');
        $this->command->info('🎉 QuraniCare Database Seeding Completed!');
        $this->command->info('');
        $this->command->info('📋 Summary of seeded data:');
        $this->command->info('👥 Sample Users: Users created');
        $this->command->info('📖 Quran Data: Surahs and Ayahs');
        $this->command->info('🤲 Dzikir & Doa: Islamic prayers and dhikr');
        $this->command->info('🎵 Audio Content: Relaxation audio and nasheed');
        $this->command->info('🫁 Breathing Exercises: Meditation and breathing techniques');
        $this->command->info('🧠 Psychology Materials: Mental health resources');
        $this->command->info('🤖 AI Knowledge Base: Chat support content');
        $this->command->info('📢 Notifications: System notification templates');
        $this->command->info('😊 Mood Tracking: 30 days of mood data for each user');
        $this->command->info('📊 Activity Tracking: Comprehensive activity logs for Sakinah Tracker');
        $this->command->info('');
        $this->command->info('👤 Sample User Login:');
        $this->command->info('   Email: abdullah.rahman@email.com');
        $this->command->info('   Password: password123');
    }
}
