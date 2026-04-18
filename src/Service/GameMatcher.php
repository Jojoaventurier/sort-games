<?php

namespace App\Service;

class GameMatcher
{
    public function normalize(string $gameName): string
    {
        // 1. Initial cleanup: lowercase and convert to UTF-8
        $name = mb_strtolower($gameName, 'UTF-8');

        // 2. Remove Byte Order Mark (BOM) if present
        $name = str_replace(["\xEF\xBB\xBF", "\u{FEFF}"], '', $name);

        // 3. Roman Numeral to Digits (Ensures "Witcher III" matches "Witcher 3")
        $romans = [
            ' viii' => ' 8', ' vii' => ' 7', ' vi' => ' 6', ' v' => ' 5',
            ' iv' => ' 4', ' iii' => ' 3', ' ii' => ' 2', ' i' => ' 1'
        ];
        $name = str_ireplace(array_keys($romans), array_values($romans), $name);

        // 4. Remove leading scene/source/distributor prefixes
        $name = preg_replace(
            '/^(game|games|sr|rune|tenoke|elamigos|chronos|p2p|emu|gog|fitgirl|dodi|flt|skidrow|razor1911|codex)[\.\-_ ]+/i',
            '',
            $name
        );

        // 5. Remove bracketed [ ] and parenthesized ( ) metadata
        $name = preg_replace('/\[.*?\]/', '', $name);
        $name = preg_replace('/\([^)]*\)/', '', $name);

        // 6. Remove common release / group / edition tags
        // ADDED: rune, tenoke, fitgirl, skidrow, fckdrm
        $name = preg_replace(
            '/\b(p2p|goldberg|rune|tenoke|fitgirl|skidrow|fckdrm|repack|drmfree|portable|early access|ultimate edition|premium edition|supporter edition|deluxe edition|goty|complete edition|remastered|directors cut)\b/i',
            '',
            $name
        );

        // 7. Remove version & build patterns (v1.0, Build.123, etc)
        $name = preg_replace('/\bv?\d+(\.\d+)+[a-z]?\b/i', '', $name);
        $name = preg_replace('/\bbuild\b[\.\s_-]*\d+/i', '', $name);
        $name = preg_replace('/\bupdate\b[\.\s_-]*\d+/i', '', $name);

        // 8. Remove trailing numeric identifiers (Steam IDs or long scene IDs)
        $name = preg_replace('/\b\d{4,}\b$/', '', $name);

        // 9. Normalize separators (Dots, Underscores, Dashes, Colons to Spaces)
        $name = str_replace(['.', '_', '-', ':'], ' ', $name);

        // 10. Remove leftover junk system tokens
        $name = preg_replace('/\b(win|iso|x64|x86|dx\d+|crack|multi\d+|reloaded)\b/i', '', $name);

        // 11. Remove "The " from the beginning
        if (str_starts_with($name, 'the ')) {
            $name = mb_substr($name, 4);
        }

        // 12. Final cleanup: Remove double spaces and trim
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