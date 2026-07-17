<?php

/**
 * History Ranker
 *
 * Pure, side-effect-free helper that ranks candidate GL account codes for a
 * supplier by frequency over a bounded recency window of prior bookings
 * (REQ-GLS-02). This is the deterministic, zero-AI confidence floor: given
 * the same booking history it always produces the same ranked result.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Suggestion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Suggestion;

/**
 * Ranks GL account codes by windowed booking frequency.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Suggestion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */
class HistoryRanker
{

    /**
     * Number of most-recent bookings considered when ranking.
     *
     * @var int
     */
    private const HISTORY_WINDOW = 10;

    /**
     * Maximum number of ranked candidates returned.
     *
     * @var int
     */
    private const MAX_SUGGESTIONS = 3;

    /**
     * Rank candidate GL accounts by frequency over the most recent bookings.
     *
     * @param array<int, array<string, mixed>> $bookings       Booking history for a single resolved
     *                                                         supplier identity (each `{accountCode,
     *                                                         accountLabel?, bookedAt}`), in any order.
     * @param array<int, string>               $candidateCodes Optional allow-list of account codes; when
     *                                                         non-empty, only these codes may appear in
     *                                                         the result (REQ-GLS-02 candidate-constrained
     *                                                         scenario).
     *
     * @return array<int, array<string, mixed>> Ranked candidates (each `{code, label, confidence,
     *         rationale}`), highest confidence first, capped to {@see MAX_SUGGESTIONS}. Empty when there
     *         is no history.
     *
     * @spec openspec/specs/ai-gl-account-suggestion/spec.md
     */
    public function rank(array $bookings, array $candidateCodes=[]): array
    {
        if ($bookings === []) {
            return [];
        }

        $window     = $this->mostRecentWindow(bookings: $bookings);
        $windowSize = count($window);

        [$counts, $labels] = $this->tallyByCode(window: $window);
        if ($counts === []) {
            return [];
        }

        $results = $this->buildResults(counts: $counts, labels: $labels, windowSize: $windowSize, candidateCodes: $candidateCodes);

        usort($results, static fn (array $left, array $right): int => $right['confidence'] <=> $left['confidence']);

        return array_slice($results, 0, self::MAX_SUGGESTIONS);

    }//end rank()

    /**
     * Sort bookings by `bookedAt` descending and take the most recent window.
     *
     * @param array<int, array<string, mixed>> $bookings Booking history (each `{accountCode,
     *                                                   accountLabel?, bookedAt}`).
     *
     * @return array<int, array<string, mixed>> The most recent window.
     */
    private function mostRecentWindow(array $bookings): array
    {
        usort(
            $bookings,
            static fn (array $left, array $right): int => strcmp((string) ($right['bookedAt'] ?? ''), (string) ($left['bookedAt'] ?? ''))
        );

        return array_slice($bookings, 0, self::HISTORY_WINDOW);

    }//end mostRecentWindow()

    /**
     * Tally occurrences per account code within the window, and remember the
     * first-seen label for each code.
     *
     * @param array<int, array<string, mixed>> $window The recency window (each `{accountCode,
     *                                                 accountLabel?, bookedAt}`).
     *
     * @return array{0: array<string, int>, 1: array<string, string|null>} `[counts, labels]` keyed by account code.
     */
    private function tallyByCode(array $window): array
    {
        $counts = [];
        $labels = [];

        foreach ($window as $booking) {
            $code = trim((string) ($booking['accountCode'] ?? ''));
            if ($code === '') {
                continue;
            }

            $counts[$code] = ($counts[$code] ?? 0) + 1;
            if (array_key_exists($code, $labels) === false) {
                $labels[$code] = ($booking['accountLabel'] ?? null);
            }
        }

        return [$counts, $labels];

    }//end tallyByCode()

    /**
     * Build the ranked-candidate rows for each tallied code, honouring the
     * optional candidate allow-list.
     *
     * @param array<string, int>         $counts         Occurrence count per code (within the window).
     * @param array<string, string|null> $labels         First-seen label per code.
     * @param int                        $windowSize     Total bookings considered (the rationale denominator).
     * @param array<int, string>         $candidateCodes Optional allow-list of codes.
     *
     * @return array<int, array{code: string, label: string|null, confidence: float, rationale: string}>
     */
    private function buildResults(array $counts, array $labels, int $windowSize, array $candidateCodes): array
    {
        $results = [];

        foreach ($counts as $code => $count) {
            // PHP silently casts numeric-string array keys (e.g. "4300") to
            // int; cast back to string so callers always see the original
            // opaque account-code string (REQ-GLS-07).
            $code = (string) $code;
            if ($candidateCodes !== [] && in_array($code, $candidateCodes, true) === false) {
                continue;
            }

            $confidence = round($count / $windowSize, 2);
            $results[]  = [
                'code'       => $code,
                'label'      => ($labels[$code] ?? null),
                'confidence' => $confidence,
                'rationale'  => sprintf(
                    'Booked to %s in %d of the last %d invoices from this supplier',
                    $code,
                    $count,
                    $windowSize
                ),
            ];
        }//end foreach

        return $results;

    }//end buildResults()
}//end class
