<?php

/**
 * ProhibitionGateException
 *
 * Thrown by AnonymizationService when the prohibition gate fires and the
 * anonymise call cannot proceed. Carries the structured 422 body so the
 * controller can surface it without duplicating gate logic.
 *
 * @category Exception
 * @package  OCA\DocuDesk\Exception
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Exception;

use RuntimeException;

/**
 * Exception signalling that the prohibition gate blocked the anonymise call.
 *
 * @category Exception
 * @package  OCA\DocuDesk\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
 */
class ProhibitionGateException extends RuntimeException
{

    /**
     * Entities that are prohibition-matched at high confidence but missing from
     * the to-be-anonymised set. Each entry: {entityId, entityName, ruleId,
     * ruleName, confidence}.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $missingProhibitionMatches;

    /**
     * Override attempts that were rejected because the match confidence was ≥
     * the high-confidence threshold. Each entry: {ruleId, entityId}.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $rejectedOverrides;

    /**
     * Optional backend-unavailability reason. Set when the gate fires
     * because a required dependency (PolicyMatchService, EntityRelationMapper,
     * matchProhibition()) was unavailable / threw. Lets callers distinguish
     * "rule fired" from "fail-closed due to outage".
     *
     * @var string|null
     */
    private ?string $backendUnavailable;

    /**
     * Constructor.
     *
     * @param array<int, array<string, mixed>> $missingProhibitionMatches High-confidence matches missing from entities[].
     * @param array<int, array<string, mixed>> $rejectedOverrides         Overrides rejected for being above threshold.
     * @param string|null                      $backendUnavailable        Optional fail-closed reason.
     */
    public function __construct(
        array $missingProhibitionMatches=[],
        array $rejectedOverrides=[],
        ?string $backendUnavailable=null
    ) {
        parent::__construct(
            message: $backendUnavailable !== null
                ? 'Prohibition gate failed closed: '.$backendUnavailable
                : 'Prohibition gate blocked the anonymise call.',
            code: $backendUnavailable !== null ? 503 : 422
        );

        $this->missingProhibitionMatches = $missingProhibitionMatches;
        $this->rejectedOverrides         = $rejectedOverrides;
        $this->backendUnavailable        = $backendUnavailable;

    }//end __construct()

    /**
     * Whether the gate fired because a backend dependency was unavailable.
     *
     * @return string|null Reason text, or null when the gate fired for a
     *                     normal rule-match.
     */
    public function getBackendUnavailable(): ?string
    {
        return $this->backendUnavailable;

    }//end getBackendUnavailable()

    /**
     * Get the missing high-confidence prohibition matches.
     *
     * @return array<int, array<string, mixed>>
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
     */
    public function getMissingProhibitionMatches(): array
    {
        return $this->missingProhibitionMatches;

    }//end getMissingProhibitionMatches()

    /**
     * Get the rejected override entries.
     *
     * @return array<int, array<string, mixed>>
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
     */
    public function getRejectedOverrides(): array
    {
        return $this->rejectedOverrides;

    }//end getRejectedOverrides()
}//end class
