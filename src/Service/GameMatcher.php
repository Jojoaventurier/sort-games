<?php

namespace App\Service;

class GameMatcher
{
    public function normalize(string $gameName): string
    {
        $name = strtolower($gameName);

        // 1. Remove leading scene/source prefixes
        $name = preg_replace(
            '/^(game|games|sr|rune|tenoke|elamigos|chronos|p2p|emu|gog|fitgirl)[\.\-_ ]+/i',
            '',
            $name
        );

        // 2. Remove bracketed / parenthesized metadata
        $name = preg_replace('/\[.*?\]/', '', $name);
        $name = preg_replace('/\([^)]*\)/', '', $name);

        // 3. Remove common release / group tags
        $name = preg_replace(
            '/\b(p2p|repack|goldberg|drmfree|portable|early access|ultimate edition|premium edition|supporter edition)\b/i',
            '',
            $name
        );

        // 4. Remove version & build patterns
        $name = preg_replace('/\bv?\d+(\.\d+)+[a-z]?\b/i', '', $name);
        $name = preg_replace('/\bbuild\b[\.\s_-]*\d+/i', '', $name);
        $name = preg_replace('/\bupdate\b[\.\s_-]*\d+/i', '', $name);

        // 5. Remove trailing numeric identifiers (Steam IDs, scene IDs)
        $name = preg_replace('/\b\d{4,}\b$/', '', $name);

        // 6. Normalize separators
        $name = str_replace(['.', '_', '-'], ' ', $name);

        // 7. Remove leftover junk tokens
        $name = preg_replace('/\b(win|iso|x64|x86|dx\d+)\b/i', '', $name);

        // 8. Final cleanup
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    private const STOPWORDS = [
    'the', 'of', 'and', 'a', 'an', 'in', 'to', 'for', 'with', 'on'
    ];

    public function fuzzy(string $normalizedName): string
    {
        $words = explode(' ', $normalizedName);

        $filtered = array_filter($words, function ($word) {
            return !in_array($word, self::STOPWORDS, true);
        });

        return implode(' ', $filtered);
    }

}
