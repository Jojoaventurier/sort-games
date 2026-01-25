<?php

namespace App\Service;

class GameMatcher
{
    public function normalize(string $gameName): string
    {
        // 1. Convert to lowercase
        $name = strtolower($gameName);
        
        // 2. Remove common release tags and version patterns
        $patterns = [
            '/\[.*?\]/',                // Anything in brackets [FitGirl Repack]
            '/\(.*?\)/',                // Anything in parentheses (Build 123)
            '/v\d+(\.\d+)*/',           // Version numbers like v1.0.2
            '/build\.\d+/',             // Build numbers
            '/-p2p|-goldberg|-repack/i', // Specific release groups
            '/-insaneramzes/i'
        ];
        
        $name = preg_replace($patterns, '', $name);
        
        // 3. Replace dots/underscores with spaces and trim
        $name = str_replace(['.', '_', '-'], ' ', $name);
        
        // 4. Clean up double spaces
        return trim(preg_replace('/\s+/', ' ', $name));
    }
}