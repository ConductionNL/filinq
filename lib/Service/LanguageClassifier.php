<?php
/**
 * Language Classifier
 *
 * Service for language detection and topic classification from text content.
 * Contains word lists and scoring logic. Extracted from TextAnalysisService
 * to reduce class complexity (word lists are volume killers).
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

/**
 * Service for language detection and topic classification
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class LanguageClassifier
{

    /**
     * Common Dutch words for language detection
     *
     * @var string[]
     */
    private const DUTCH_WORDS = [
        'de',
        'het',
        'een',
        'en',
        'van',
        'is',
        'zijn',
        'op',
        'voor',
        'met',
    ];

    /**
     * Common English words for language detection
     *
     * @var string[]
     */
    private const ENGLISH_WORDS = [
        'the',
        'be',
        'to',
        'of',
        'and',
        'a',
        'in',
        'that',
        'have',
        'it',
    ];

    /**
     * Topic keyword mappings for classification
     *
     * @var array<string, string[]>
     */
    private const TOPIC_KEYWORDS = [
        'legal'     => ['contract', 'agreement', 'law', 'legal', 'court', 'judge'],
        'financial' => ['invoice', 'payment', 'budget', 'financial', 'account', 'money'],
        'medical'   => ['patient', 'diagnosis', 'treatment', 'medical', 'health', 'doctor'],
        'technical' => ['system', 'software', 'technical', 'code', 'development', 'api'],
    ];

    /**
     * Count word occurrences for a list of target words in text
     *
     * @param string        $text  The text to search in
     * @param array<string> $words The words to count
     *
     * @return int Total occurrence count
     *
     * @spec openspec/changes/retrofit-2026-05-24-metadata-enrichment/tasks.md#task-1
     */
    private function countWordOccurrences(string $text, array $words): int
    {
        $count = 0;
        foreach ($words as $word) {
            $count += substr_count($text, ' '.$word.' ');
        }

        return $count;

    }//end countWordOccurrences()

    /**
     * Detect language from text content
     *
     * @param string $text Text content to analyze
     *
     * @return string|null Detected language code or null if detection fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-metadata-enrichment/tasks.md#task-1
     */
    public function detectLanguage(string $text): ?string
    {
        $text = strtolower($text);

        $dutchCount   = $this->countWordOccurrences(text: $text, words: self::DUTCH_WORDS);
        $englishCount = $this->countWordOccurrences(text: $text, words: self::ENGLISH_WORDS);

        if ($dutchCount > $englishCount && $dutchCount > 5) {
            return 'nl';
        }

        if ($englishCount > 5) {
            return 'en';
        }

        return null;

    }//end detectLanguage()

    /**
     * Classify document topic based on text content
     *
     * @param string $text Text content to analyze
     *
     * @return string|null Classified topic or null if classification fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-metadata-enrichment/tasks.md#task-1
     */
    public function classifyTopic(string $text): ?string
    {
        $text = strtolower($text);

        $scores = [];
        foreach (self::TOPIC_KEYWORDS as $topic => $keywords) {
            $scores[$topic] = $this->countWordOccurrences(text: $text, words: $keywords);
        }

        $maxScore = max($scores);
        if ($maxScore > 0) {
            $topic = array_search($maxScore, $scores);
            if ($topic !== false) {
                return (string) $topic;
            }

            return null;
        }

        return null;

    }//end classifyTopic()
}//end class
