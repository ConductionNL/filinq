<?php
/**
 * Text Analysis Service
 *
 * Service for analyzing text content: language detection,
 * keyword extraction, topic classification, and document type
 * standardization.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

/**
 * Service for text content analysis operations
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class TextAnalysisService
{


    /**
     * Count word occurrences for a list of target words in text
     *
     * @param string        $text  The text to search in
     * @param array<string> $words The words to count
     *
     * @return int Total occurrence count
     */
    public function countWordOccurrences(string $text, array $words): int
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
     */
    public function detectLanguage(string $text): ?string
    {
        $text = strtolower($text);

        $dutchWords   = ['de', 'het', 'een', 'en', 'van', 'is', 'zijn', 'op', 'voor', 'met'];
        $dutchCount   = $this->countWordOccurrences($text, $dutchWords);
        $englishWords = ['the', 'be', 'to', 'of', 'and', 'a', 'in', 'that', 'have', 'it'];
        $englishCount = $this->countWordOccurrences($text, $englishWords);

        if ($dutchCount > $englishCount && $dutchCount > 5) {
            return 'nl';
        }

        if ($englishCount > 5) {
            return 'en';
        }

        return null;

    }//end detectLanguage()


    /**
     * Extract keywords from text content
     *
     * @param string $text Text content to analyze
     *
     * @return array<string> Extracted keywords
     */
    public function extractKeywords(string $text): array
    {
        $words      = str_word_count(strtolower($text), 1);
        $wordCounts = array_count_values($words);

        $stopWords = [
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
            'for',
            'not',
            'on',
            'with',
            'he',
            'as',
            'you',
            'do',
            'at',
        ];

        foreach ($stopWords as $stopWord) {
            unset($wordCounts[$stopWord]);
        }

        arsort($wordCounts);

        return array_slice(array_keys($wordCounts), 0, 10);

    }//end extractKeywords()


    /**
     * Classify document topic based on text content
     *
     * @param string $text Text content to analyze
     *
     * @return string|null Classified topic or null if classification fails
     */
    public function classifyTopic(string $text): ?string
    {
        $text = strtolower($text);

        $topics = [
            'legal'     => ['contract', 'agreement', 'law', 'legal', 'court', 'judge'],
            'financial' => ['invoice', 'payment', 'budget', 'financial', 'account', 'money'],
            'medical'   => ['patient', 'diagnosis', 'treatment', 'medical', 'health', 'doctor'],
            'technical' => ['system', 'software', 'technical', 'code', 'development', 'api'],
        ];

        $scores = [];
        foreach ($topics as $topic => $keywords) {
            $scores[$topic] = $this->countWordOccurrences($text, $keywords);
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


    /**
     * Standardize document type classification
     *
     * @param string $documentType Document type to standardize
     *
     * @return string Standardized document type
     */
    public function standardizeDocumentType(string $documentType): string
    {
        $documentType = strtolower(trim($documentType));

        $typeMap = [
            'pdf'        => 'pdf',
            'word'       => 'word',
            'doc'        => 'word',
            'docx'       => 'word',
            'excel'      => 'spreadsheet',
            'xls'        => 'spreadsheet',
            'xlsx'       => 'spreadsheet',
            'powerpoint' => 'presentation',
            'ppt'        => 'presentation',
            'pptx'       => 'presentation',
            'text'       => 'text',
            'txt'        => 'text',
            'html'       => 'html',
            'image'      => 'image',
            'jpg'        => 'image',
            'jpeg'       => 'image',
            'png'        => 'image',
        ];

        return $typeMap[$documentType] ?? $documentType;

    }//end standardizeDocumentType()


}//end class
