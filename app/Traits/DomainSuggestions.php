<?php

namespace App\Traits;

use function Laravel\Prompts\select;

trait DomainSuggestions
{
    /**
     * Get domain suggestions based on user input with interactive selection
     *
     * @param string $input User input domain
     * @param array $availableDomains List of available domains
     * @param int $maxSuggestions Maximum number of suggestions to return
     * @return string|null Selected domain or null if no selection made
     */
    protected function getDomainSuggestionsWithSelection(string $input, array $availableDomains, int $maxSuggestions = 5): ?string
    {
        $suggestions = [];

        foreach ($availableDomains as $domain) {
            $similarity = $this->calculateSimilarity($input, $domain);

            if ($similarity > 0.6) {
                $suggestions[] = [
                    'domain' => $domain,
                    'similarity' => $similarity
                ];
            }
        }

        usort($suggestions, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        $suggestions = array_slice($suggestions, 0, $maxSuggestions);

        if (empty($suggestions)) {
            return null;
        }

        $options = [];
        foreach ($suggestions as $suggestion) {
            $options[$suggestion['domain']] = $suggestion['domain'];
        }
        $options['none'] = 'None of these';

        $selected = select(
            label: "Domain '{$input}' not found. Did you mean:",
            options: $options,
            scroll: min(count($options), 10)
        );

        return $selected === 'none' ? null : $selected;
    }

    /**
     * Calculate similarity between two strings
     * Uses a combination of algorithms for better matching
     */
    protected function calculateSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower($str1);
        $str2 = strtolower($str2);

        $levDistance = levenshtein($str1, $str2);
        $maxLength = max(strlen($str1), strlen($str2));
        $levSimilarity = 1 - ($levDistance / $maxLength);

        similar_text($str1, $str2, $percentage);
        $textSimilarity = $percentage / 100;

        return ($levSimilarity * 0.6) + ($textSimilarity * 0.4);
    }
}
