<?php

echo "=== AUDIO FILE FORMAT CHECK ===\n\n";

$audioDir = 'storage/app/public/audio/islamic_music/';
$files = glob($audioDir . '*.mp3');

foreach ($files as $file) {
    $fileName = basename($file);
    $fileSize = filesize($file);
    
    echo "📀 {$fileName}\n";
    echo "   Size: " . number_format($fileSize) . " bytes\n";
    
    if ($fileSize > 0) {
        $handle = fopen($file, 'rb');
        $header = fread($handle, 12);
        fclose($handle);
        
        // Detect format
        $format = 'unknown';
        if (substr($header, 0, 3) === 'ID3' || substr($header, 0, 2) === "\xFF\xFB") {
            $format = 'MP3 ✅';
        } elseif (substr($header, 0, 4) === 'OggS') {
            $format = 'Ogg ⚠️';
        } elseif (substr($header, 4, 4) === 'ftyp') {
            $format = 'AAC/M4A ⚠️';
        } elseif (substr($header, 0, 4) === 'RIFF') {
            $format = 'WAV ⚠️';
        }
        
        echo "   Format: {$format}\n";
        echo "   Header: " . bin2hex(substr($header, 0, 8)) . "\n";
    } else {
        echo "   Format: Empty file ❌\n";
    }
    
    echo "\n";
}

echo "=== RECOMMENDATION ===\n";
echo "✅ MP3 files work in VS Code audio player\n";
echo "⚠️  Ogg/AAC files may not play in VS Code but work in browsers\n";
echo "❌ Empty files need proper file IDs from Google Drive\n";