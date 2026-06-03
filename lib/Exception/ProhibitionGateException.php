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
     * Constructor.
     *
     * @param array<int, array<string, mixed>> $missingProhibitionMatches High-confidence matches missing from entities[].
     * @param array<int, array<string, mixed>> $rejectedOverrides         Overrides rejected for being above threshold.
     */
    public function __construct(
        array $missingProhibitionMatches=[],
        array $rejectedOverrides=[]
    ) {
        parent::__construct(
            message: 'Prohibition gate blocked the anonymise call.',
            code: 422
        );

        $this->missingProhibitionMatches = $missingProhibitionMatches;
        $this->rejectedOverrides         = $rejectedOverrides;

    }//end __construct()

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
