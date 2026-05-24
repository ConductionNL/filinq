<?php
/**
 * Text Analysis Service
 *
 * Service for analyzing text content: keyword extraction and document type
 * standardization. Delegates language detection and topic classification
 * to LanguageClassifier.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-46
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-70
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-71
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-72
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
     * Constructor for TextAnalysisService
     *
     * @param LanguageClassifier $languageClassifier Language and topic classifier
     *
     * @return void
     */
    public function __construct(
        private readonly LanguageClassifier $languageClassifier
    ) {

    }//end __construct()

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
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-46
     */
    public function detectLanguage(string $text): ?string
    {
        return $this->languageClassifier->detectLanguage($text);

    }//end detectLanguage()

    /**
     * Extract keywords from text content
     *
     * @param string $text Text content to analyze
     *
     * @return array<string> Extracted keywords
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-70
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
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-71
     */
    public function classifyTopic(string $text): ?string
    {
        return $this->languageClassifier->classifyTopic($text);

    }//end classifyTopic()

    /**
     * Standardize document type classification
     *
     * @param string $documentType Document type to standardize
     *
     * @return string Standardized document type
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-72
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
