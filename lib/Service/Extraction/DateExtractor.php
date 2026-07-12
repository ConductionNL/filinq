<?php
/**
 * Date Extractor
 *
 * Pure, side-effect-free heuristic extractor that locates dates in ISO
 * (`YYYY-MM-DD`) and Dutch (`DD-MM-YYYY`, `D MMMM YYYY`) forms and
 * normalises them to ISO 8601 (REQ-FIN-02). Unparseable input yields a null
 * value, never a thrown exception.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Extraction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Extraction;

use DateTimeImmutable;
use Exception;

/**
 * Extracts and normalises ISO/Dutch-format dates from free text.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Extraction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/financial-document-field-extraction/tasks.md#2-3
 */
class DateExtractor
{

    /**
     * Confidence assigned to a date found immediately after a matched label.
     *
     * @var float
     */
    private const LABELLED_CONFIDENCE = 0.8;

    /**
     * Confidence assigned to a date found without an adjacent label.
     *
     * @var float
     */
    private const UNLABELLED_CONFIDENCE = 0.6;

    /**
     * Dutch month names, in genitive/nominative form, mapped to month numbers.
     *
     * @var array<string, int>
     */
    private const DUTCH_MONTHS = [
        'januari'   => 1,
        'februari'  => 2,
        'maart'     => 3,
        'april'     => 4,
        'mei'       => 5,
        'juni'      => 6,
        'juli'      => 7,
        'augustus'  => 8,
        'september' => 9,
        'oktober'   => 10,
        'november'  => 11,
        'december'  => 12,
    ];

    /**
     * Extract the first date immediately following one of the given labels.
     *
     * @param string        $text   The text to search.
     * @param array<string> $labels Case-insensitive labels (e.g. "factuurdatum").
     *
     * @return array{value: string|null, confidence: float} The ISO 8601 date
     *         and its confidence, or a null value with confidence 0.
     *
     * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
     */
    public function extractLabelled(string $text, array $labels): array
    {
        if ($labels === []) {
            return ['value' => null, 'confidence' => 0.0];
        }

        $labelPattern = implode('|', array_map(static fn (string $label): string => preg_quote($label, '/'), $labels));
        $datePattern  = $this->datePattern();

        $matched = preg_match(
            '/\b(?:'.$labelPattern.')\b\s*[:\-]?\s*('.$datePattern.')/i',
            $text,
            $matches
        );

        if ($matched === 1) {
            $normalised = $this->normalise(raw: $matches[1]);
            if ($normalised !== null) {
                return [
                    'value'      => $normalised,
                    'confidence' => self::LABELLED_CONFIDENCE,
                ];
            }
        }

        return ['value' => null, 'confidence' => 0.0];

    }//end extractLabelled()

    /**
     * Extract every parseable date in the text, in document order, normalised
     * to ISO 8601 and de-duplicated.
     *
     * @param string $text The text to search.
     *
     * @return array<int, array{value: string, confidence: float}> Dates found.
     *
     * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
     */
    public function extractAll(string $text): array
    {
        $matchCount = preg_match_all('/'.$this->datePattern().'/i', $text, $matches);
        if ($matchCount === false || $matchCount === 0) {
            return [];
        }

        $seen    = [];
        $results = [];
        foreach ($matches[0] as $raw) {
            $normalised = $this->normalise(raw: $raw);
            if ($normalised === null || isset($seen[$normalised]) === true) {
                continue;
            }

            $seen[$normalised] = true;
            $results[]         = [
                'value'      => $normalised,
                'confidence' => self::UNLABELLED_CONFIDENCE,
            ];
        }

        return $results;

    }//end extractAll()

    /**
     * Build the combined regex alternation for ISO / Dutch numeric / Dutch
     * long-form dates.
     *
     * @return string The regex alternation (without delimiters).
     */
    private function datePattern(): string
    {
        $monthNames = implode('|', array_keys(self::DUTCH_MONTHS));

        return '(?:[0-9]{4}-[0-9]{1,2}-[0-9]{1,2})'
        // ISO 8601.
            .'|(?:[0-9]{1,2}-[0-9]{1,2}-[0-9]{4})'
        // Dutch numeric DD-MM-YYYY.
            .'|(?:[0-9]{1,2}\s+(?:'.$monthNames.')\s+[0-9]{4})';
        // Dutch long-form.
    }//end datePattern()

    /**
     * Normalise a matched raw date string to ISO 8601 (`YYYY-MM-DD`).
     *
     * @param string $raw The raw matched date text.
     *
     * @return string|null The normalised date, or null when it does not
     *                      represent a valid calendar date.
     */
    private function normalise(string $raw): ?string
    {
        $raw = trim($raw);

        // ISO 8601: YYYY-MM-DD.
        if (preg_match('/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/', $raw, $parts) === 1) {
            return $this->buildIsoDate(year: (int) $parts[1], month: (int) $parts[2], day: (int) $parts[3]);
        }

        // Dutch numeric: DD-MM-YYYY.
        if (preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/', $raw, $parts) === 1) {
            return $this->buildIsoDate(year: (int) $parts[3], month: (int) $parts[2], day: (int) $parts[1]);
        }

        // Dutch long-form: D MMMM YYYY.
        if (preg_match('/^([0-9]{1,2})\s+([a-zA-Z]+)\s+([0-9]{4})$/', $raw, $parts) === 1) {
            $monthName = strtolower($parts[2]);
            if (isset(self::DUTCH_MONTHS[$monthName]) === false) {
                return null;
            }

            return $this->buildIsoDate(year: (int) $parts[3], month: self::DUTCH_MONTHS[$monthName], day: (int) $parts[1]);
        }

        return null;

    }//end normalise()

    /**
     * Build a validated ISO 8601 date string, or null when the parts do not
     * form a real calendar date.
     *
     * @param int $year  Four-digit year.
     * @param int $month Month (1-12).
     * @param int $day   Day of month.
     *
     * @return string|null The `YYYY-MM-DD` string, or null when invalid.
     */
    private function buildIsoDate(int $year, int $month, int $day): ?string
    {
        if (checkdate(month: $month, day: $day, year: $year) === false) {
            return null;
        }

        try {
            $date = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
        } catch (Exception $e) {
            return null;
        }

        return $date->format('Y-m-d');

    }//end buildIsoDate()
}//end class
