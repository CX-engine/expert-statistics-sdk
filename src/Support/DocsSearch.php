<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Support;

/**
 * Plain lexical (term-overlap) ranking over the doc catalog's content - no
 * embeddings, no vector store, no external service. Deliberately simple:
 * this is the retrieval half of a small RAG pipeline for the docs assistant
 * (see Services\PrismDocsAssistantResponder), grounding its answers in real
 * doc text instead of letting the model guess at UI behaviour from its
 * training data.
 */
final class DocsSearch
{
    /**
     * Ranks every catalog section by how many query terms it contains
     * (title matches weighted higher than body matches), returns the top
     * $limit section ids. A section with zero matching terms is excluded
     * entirely rather than padding the result with irrelevant sections.
     *
     * @return array<int, string> section ids, best match first
     */
    public static function topSectionIds(string $query, string $locale, int $limit = 3): array
    {
        $terms = self::terms($query);

        if ($terms === []) {
            return [];
        }

        $scored = [];

        foreach (DocsCatalog::sections() as $section) {
            $title = mb_strtolower(str_replace('-', ' ', $section['id']));
            $body = mb_strtolower(DocsContent::raw($section['id'], $locale));

            $score = 0;
            foreach ($terms as $term) {
                $score += 3 * substr_count($title, $term);
                $score += substr_count($body, $term);
            }

            if ($score > 0) {
                $scored[$section['id']] = $score;
            }
        }

        arsort($scored);

        return array_slice(array_keys($scored), 0, max(0, $limit));
    }

    /**
     * @return array<int, string>
     */
    private static function terms(string $query): array
    {
        $normalized = mb_strtolower(trim($query));
        $normalized = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $normalized) ?? '';

        $stopwords = [
            // English + French filler words, short enough that matching
            // them against every section's body would just add noise.
            'the', 'a', 'an', 'is', 'are', 'to', 'of', 'and', 'or', 'in', 'on',
            'for', 'how', 'do', 'i', 'can', 'what', 'where', 'my',
            'le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'et', 'ou',
            'est', 'sont', 'comment', 'je', 'mon', 'ma', 'mes', 'pour', 'où',
        ];

        $words = array_filter(
            explode(' ', $normalized),
            fn (string $word) => mb_strlen($word) >= 3 && ! in_array($word, $stopwords, true)
        );

        return array_values(array_unique($words));
    }
}
