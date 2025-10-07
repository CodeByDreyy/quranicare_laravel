<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Simple test route
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working!',
        'timestamp' => now()
    ]);
});

// Test Quran routes
Route::get('/quran/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'Quran API endpoint is working!',
        'models_available' => [
            'QuranSurah' => class_exists('App\Models\QuranSurah'),
            'QuranAyah' => class_exists('App\Models\QuranAyah'),
        ]
    ]);
});

Route::get('/quran/surahs', function () {
    try {
        $surahs = \App\Models\QuranSurah::select([
            'id', 
            'number', 
            'name_arabic', 
            'name_indonesian', 
            'name_english',
            'name_latin', 
            'place', 
            'number_of_ayahs',
            'description_indonesian'
        ])
        ->orderBy('number', 'asc')
        ->take(5) // Only take 5 for testing
        ->get();

        return response()->json([
            'success' => true,
            'message' => 'Surahs retrieved successfully',
            'count' => $surahs->count(),
            'data' => $surahs
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve surahs',
            'error' => $e->getMessage()
        ], 500);
    }
});