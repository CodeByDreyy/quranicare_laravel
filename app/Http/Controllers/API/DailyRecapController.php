<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Mood;
use App\Models\MoodStatistic;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DailyRecapController extends Controller
{
    /**
     * Get daily mood recap for a specific date
     */
    public function getDailyRecap(Request $request, string $date): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $parsedDate = Carbon::parse($date);
            
            // Get mood entries for the specific date
            $moods = Mood::where('user_id', $user->id)
                ->where('mood_date', $parsedDate->toDateString())
                ->orderBy('mood_time', 'asc')
                ->get();

            // Get mood statistics for the date
            $moodStats = MoodStatistic::where('user_id', $user->id)
                ->where('date', $parsedDate->toDateString())
                ->first();

            // Get week summary (7 days including selected date)
            $weekStart = $parsedDate->copy()->startOfWeek();
            $weekEnd = $parsedDate->copy()->endOfWeek();
            
            $weekStats = MoodStatistic::where('user_id', $user->id)
                ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->orderBy('date')
                ->get();

            // Calculate weekly averages
            $weeklyAverage = $weekStats->avg('mood_score') ?? 0;
            $weeklyTotalEntries = $weekStats->sum('total_entries');

            // Get mood streak (consecutive days with mood records)
            $moodStreak = $this->calculateMoodStreak($user->id, $parsedDate);

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $parsedDate->toDateString(),
                    'date_formatted' => $parsedDate->locale('id')->isoFormat('dddd, D MMMM YYYY'),
                    'mood_entries' => $moods->map(function($mood) {
                        return [
                            'id' => $mood->id,
                            'mood_type' => $mood->mood_type,
                            'mood_emoji' => $this->getMoodEmoji($mood->mood_type),
                            'mood_label' => $this->getMoodLabel($mood->mood_type),
                            'mood_color' => $this->getMoodColor($mood->mood_type),
                            'notes' => $mood->notes,
                            'time' => $mood->mood_time,
                            'time_formatted' => Carbon::parse($mood->mood_time)->format('H:i'),
                        ];
                    }),
                    'daily_stats' => $moodStats ? [
                        'total_entries' => $moodStats->total_entries,
                        'dominant_mood' => $moodStats->dominant_mood,
                        'dominant_mood_emoji' => $this->getMoodEmoji($moodStats->dominant_mood),
                        'dominant_mood_label' => $this->getMoodLabel($moodStats->dominant_mood),
                        'mood_score' => (float) $moodStats->mood_score,
                        'mood_counts' => json_decode($moodStats->mood_counts, true),
                    ] : null,
                    'weekly_context' => [
                        'week_start' => $weekStart->toDateString(),
                        'week_end' => $weekEnd->toDateString(),
                        'weekly_average_score' => round($weeklyAverage, 2),
                        'weekly_total_entries' => $weeklyTotalEntries,
                        'days_with_records' => $weekStats->count(),
                        'week_stats' => $weekStats->map(function($stat) {
                            return [
                                'date' => $stat->date,
                                'date_name' => Carbon::parse($stat->date)->locale('id')->dayName,
                                'mood_score' => (float) $stat->mood_score,
                                'dominant_mood' => $stat->dominant_mood,
                                'total_entries' => $stat->total_entries,
                            ];
                        })
                    ],
                    'insights' => [
                        'mood_streak' => $moodStreak,
                        'mood_trend' => $this->getMoodTrend($user->id, $parsedDate),
                        'recommendations' => $this->getMoodRecommendations($moodStats),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get daily recap',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly mood overview
     */
    public function getMonthlyMoodOverview(Request $request, int $year, int $month): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $monthlyStats = MoodStatistic::where('user_id', $user->id)
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->orderBy('date')
                ->get();

            // Calculate monthly summary
            $totalEntries = $monthlyStats->sum('total_entries');
            $averageScore = $monthlyStats->where('mood_score', '>', 0)->avg('mood_score') ?? 0;
            $daysWithRecords = $monthlyStats->count();

            // Count mood distribution
            $moodDistribution = [];
            foreach (['senang', 'biasa_saja', 'sedih', 'murung', 'marah'] as $mood) {
                $moodDistribution[$mood] = $monthlyStats->where('dominant_mood', $mood)->count();
            }

            // Get calendar data for the month
            $calendarData = [];
            foreach ($monthlyStats as $stat) {
                $calendarData[$stat->date] = [
                    'mood_score' => (float) $stat->mood_score,
                    'dominant_mood' => $stat->dominant_mood,
                    'total_entries' => $stat->total_entries,
                    'emoji' => $this->getMoodEmoji($stat->dominant_mood),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'month' => $month,
                    'year' => $year,
                    'month_name' => $startDate->locale('id')->monthName,
                    'summary' => [
                        'total_entries' => $totalEntries,
                        'average_score' => round($averageScore, 2),
                        'days_with_records' => $daysWithRecords,
                        'total_days' => $endDate->day,
                        'tracking_percentage' => round(($daysWithRecords / $endDate->day) * 100, 1),
                    ],
                    'mood_distribution' => $moodDistribution,
                    'calendar_data' => $calendarData,
                    'daily_stats' => $monthlyStats->map(function($stat) {
                        return [
                            'date' => $stat->date,
                            'day' => Carbon::parse($stat->date)->day,
                            'mood_score' => (float) $stat->mood_score,
                            'dominant_mood' => $stat->dominant_mood,
                            'total_entries' => $stat->total_entries,
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get monthly overview',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate mood tracking streak
     */
    private function calculateMoodStreak(int $userId, Carbon $fromDate): int
    {
        $streak = 0;
        $currentDate = $fromDate->copy();

        while (true) {
            $hasRecord = MoodStatistic::where('user_id', $userId)
                ->where('date', $currentDate->toDateString())
                ->exists();

            if (!$hasRecord) {
                break;
            }

            $streak++;
            $currentDate->subDay();
        }

        return $streak;
    }

    /**
     * Get mood trend analysis
     */
    private function getMoodTrend(int $userId, Carbon $date): array
    {
        $last7Days = MoodStatistic::where('user_id', $userId)
            ->where('date', '<=', $date->toDateString())
            ->orderBy('date', 'desc')
            ->limit(7)
            ->get();

        if ($last7Days->count() < 2) {
            return ['trend' => 'insufficient_data', 'message' => 'Belum cukup data untuk analisis tren'];
        }

        $scores = $last7Days->pluck('mood_score')->reverse()->toArray();
        $trend = $this->calculateTrend($scores);

        $messages = [
            'improving' => 'Mood Anda menunjukkan tren yang membaik! 📈',
            'declining' => 'Mood Anda sepertinya menurun. Jaga kesehatan mental ya! 💙',
            'stable' => 'Mood Anda cukup stabil dalam seminggu terakhir 😊',
        ];

        return [
            'trend' => $trend,
            'message' => $messages[$trend] ?? 'Data mood sedang dianalisis',
            'scores' => $scores
        ];
    }

    /**
     * Calculate trend from array of scores
     */
    private function calculateTrend(array $scores): string
    {
        if (count($scores) < 2) return 'stable';

        $firstHalf = array_slice($scores, 0, ceil(count($scores) / 2));
        $secondHalf = array_slice($scores, floor(count($scores) / 2));

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        $difference = $secondAvg - $firstAvg;

        if ($difference > 0.3) return 'improving';
        if ($difference < -0.3) return 'declining';
        return 'stable';
    }

    /**
     * Get mood recommendations based on current mood
     */
    private function getMoodRecommendations(?MoodStatistic $moodStats): array
    {
        if (!$moodStats) {
            return [
                'message' => 'Mulai catat mood Anda hari ini untuk mendapatkan insight yang lebih baik!',
                'suggestions' => [
                    'Luangkan waktu untuk refleksi diri',
                    'Baca Al-Quran untuk ketenangan hati',
                    'Lakukan dzikir dan doa'
                ]
            ];
        }

        $recommendations = [
            'senang' => [
                'message' => 'Alhamdulillah! Mood Anda hari ini sangat baik.',
                'suggestions' => [
                    'Bagikan kebahagiaan dengan orang lain',
                    'Perbanyak syukur dan dzikir',
                    'Manfaatkan mood baik untuk produktivitas'
                ]
            ],
            'biasa_saja' => [
                'message' => 'Mood Anda cukup stabil hari ini.',
                'suggestions' => [
                    'Coba lakukan aktivitas yang menyenangkan',
                    'Dengarkan murottal atau musik relaksasi',
                    'Berinteraksi dengan teman atau keluarga'
                ]
            ],
            'sedih' => [
                'message' => 'Hari yang berat, tapi ingat bahwa ini akan berlalu.',
                'suggestions' => [
                    'Curhat dengan orang terdekat',
                    'Perbanyak istighfar dan doa',
                    'Lakukan aktivitas yang Anda sukai'
                ]
            ],
            'murung' => [
                'message' => 'Sepertinya Anda butuh waktu untuk diri sendiri.',
                'suggestions' => [
                    'Lakukan meditasi atau breathing exercise',
                    'Jalan-jalan di alam terbuka',
                    'Istirahat yang cukup'
                ]
            ],
            'marah' => [
                'message' => 'Sabar adalah kunci. Tenangkan diri Anda.',
                'suggestions' => [
                    'Ambil napas dalam-dalam',
                    'Wudhu dan sholat untuk ketenangan',
                    'Hindari mengambil keputusan saat marah'
                ]
            ]
        ];

        return $recommendations[$moodStats->dominant_mood] ?? $recommendations['biasa_saja'];
    }

    /**
     * Get mood emoji representation
     */
    private function getMoodEmoji(string $moodType): string
    {
        $emojis = [
            'senang' => '😊',
            'sedih' => '😢',
            'biasa_saja' => '😐',
            'marah' => '😡',
            'murung' => '😟',
        ];

        return $emojis[$moodType] ?? '😐';
    }

    /**
     * Get mood label in Indonesian
     */
    private function getMoodLabel(string $moodType): string
    {
        $labels = [
            'senang' => 'Senang',
            'sedih' => 'Sedih', 
            'biasa_saja' => 'Biasa Saja',
            'marah' => 'Marah',
            'murung' => 'Murung',
        ];

        return $labels[$moodType] ?? 'Tidak Diketahui';
    }

    /**
     * Get mood color representation
     */
    private function getMoodColor(string $moodType): string
    {
        $colors = [
            'senang' => '#10B981',      // Green
            'sedih' => '#EF4444',       // Red
            'biasa_saja' => '#F59E0B',  // Yellow/Orange
            'marah' => '#DC2626',       // Dark Red
            'murung' => '#6B7280',      // Gray
        ];

        return $colors[$moodType] ?? '#6B7280';
    }
}