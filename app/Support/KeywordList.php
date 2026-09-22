<?php

namespace App\Support;

use Illuminate\Support\Str;

class KeywordList
{
    /**
     * @return list<string>
     */
    public static function parse(?string $input): array
    {
        if ($input === null || trim($input) === '') {
            return [];
        }

        $parts = preg_split('/[,\\n]+/', $input) ?: [];
        $keywords = [];

        foreach ($parts as $part) {
            $keyword = trim(preg_replace('/\s+/u', ' ', $part) ?? '');

            if ($keyword === '' || Str::length($keyword) > 60) {
                continue;
            }

            $keywords[] = $keyword;
        }

        return array_values(array_unique($keywords));
    }

    /**
     * @param  list<string>|null  $keywords
     */
    public static function display(?array $keywords): string
    {
        return implode(', ', $keywords ?? []);
    }
}
