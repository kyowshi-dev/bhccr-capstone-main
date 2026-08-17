<?php

namespace App\Services;

/**
 * Lightweight typo-tolerant ranking for autocomplete searches.
 *
 * Candidate rows are pre-fetched by the caller (e.g. first-letter or
 * substring LIKE) and re-ranked here with a Levenshtein edit distance
 * so that near-miss spellings still surface. Codes (ICD-10, etc.) should
 * never be fuzzy-matched; callers keep code matching exact/prefix-only.
 */
class FuzzySearchService
{
    /**
     * Normalize a string for comparison: lowercase, strip diacritics
     * (Peña -> Pena), drop non-alphanumeric characters, collapse spaces.
     */
    public function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));

        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($transliterated)) {
            $value = $transliterated;
        }

        $value = (string) preg_replace('/[^a-z0-9\s]/u', '', $value);

        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    /**
     * Maximum edit distance still considered a match, growing with query
     * length so short queries stay strict and longer ones tolerate typos.
     */
    public function maxDistance(string $query): int
    {
        return mb_strlen($this->normalize($query)) <= 4 ? 1 : 2;
    }

    /**
     * Rank rows by edit distance between the normalized query and the
     * searchable text derived from each row. Rows farther than the
     * distance threshold are dropped; the closest $limit rows are kept.
     *
     * Distance is the smaller of (a) the edit distance against the whole
     * string (catches merged multi-word inputs like "delrssario") and
     * (b) the summed best per-token distances (catches single-part typos
     * like "garsia" against "Garcia Maria").
     *
     * @param  iterable<int, \stdClass>  $rows
     * @param  callable(\stdClass): string  $textFn
     * @return array<int, \stdClass>
     */
    public function rank(iterable $rows, string $query, callable $textFn, int $limit = 10): array
    {
        $query = $this->normalize($query);
        $maxDistance = $this->maxDistance($query);

        if ($query === '') {
            return array_slice(is_array($rows) ? $rows : iterator_to_array($rows), 0, $limit);
        }

        $queryTokens = explode(' ', $query);

        $scored = [];

        foreach ($rows as $row) {
            $haystack = $this->normalize($textFn($row));

            if ($haystack === '') {
                continue;
            }

            $distance = $this->distance($queryTokens, $haystack);

            if ($distance <= $maxDistance) {
                $scored[] = ['row' => $row, 'distance' => $distance];
            }
        }

        usort($scored, fn (array $a, array $b): int => $a['distance'] <=> $b['distance']);

        return array_map(fn (array $s): \stdClass => $s['row'], array_slice($scored, 0, $limit));
    }

    /**
     * @param  list<string>  $queryTokens
     */
    private function distance(array $queryTokens, string $haystack): int
    {
        $fullDistance = levenshtein(implode(' ', $queryTokens), $haystack);

        $haystackTokens = explode(' ', $haystack);
        $tokenDistance = 0;

        foreach ($queryTokens as $queryToken) {
            $best = PHP_INT_MAX;

            foreach ($haystackTokens as $haystackToken) {
                $best = min($best, levenshtein($queryToken, $haystackToken));
            }

            $tokenDistance += $best;
        }

        return min($fullDistance, $tokenDistance);
    }
}
