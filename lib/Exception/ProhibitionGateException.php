<?php

/**
 * ProhibitionGateException — thrown when the anonymisation prohibition gate blocks a call.
 *
 * The gate can fire in two modes:
 *   1. **Backend unavailable**: the PolicyMatchService or EntityRelationMapper
 *      could not be reached (fail-closed mode). `$backendUnavailable` is set to
 *      a non-null string describing the outage; `$missingProhibitionMatches` and
 *      `$rejectedOverrides` are empty.
 *   2. **Rule block**: one or more prohibition-listed entities are absent from
 *      the to-be-anonymised set, or a user-submitted override was rejected.
 *      `$missingProhibitionMatches` and/or `$rejectedOverrides` are populated;
 *      `$backendUnavailable` is null.
 *
 * @category Exception
 * @package  OCA\DocuDesk\Exception
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Exception;

/**
 * Thrown when the anonymisation prohibition gate fires or fails closed.
 */
class ProhibitionGateException extends \RuntimeException
{
    /**
     * @param array<int, array<string, mixed>> $missingProhibitionMatches
     *   Prohibition-listed entities that are absent from the anonymised set.
     * @param array<int, array<string, mixed>> $rejectedOverrides
     *   User-submitted overrides that were rejected by the gate.
     * @param string|null                      $backendUnavailable
     *   Non-null when the gate failed closed due to a backend outage; contains
     *   a human-readable reason string. Null for a normal rule-match block.
     * @param string                           $message
     *   Optional exception message (defaults to a generic description).
     * @param int                              $code
     *   Exception code.
     * @param \Throwable|null                  $previous
     *   Previous exception for chaining.
     */
    public function __construct(
        private readonly array $missingProhibitionMatches=[],
        private readonly array $rejectedOverrides=[],
        private readonly ?string $backendUnavailable=null,
        string $message='Anonymisation prohibition gate blocked the request.',
        int $code=0,
        ?\Throwable $previous=null
    ) {
        parent::__construct($message, $code, $previous);
    }//end __construct()

    /**
     * Return the prohibition-listed entities that were absent from the anonymised set.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMissingProhibitionMatches(): array
    {
        return $this->missingProhibitionMatches;
    }//end getMissingProhibitionMatches()

    /**
     * Return the user-submitted overrides that were rejected by the gate.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRejectedOverrides(): array
    {
        return $this->rejectedOverrides;
    }//end getRejectedOverrides()

    /**
     * Return the backend-unavailability reason, or null for a rule-match block.
     *
     * @return string|null
     */
    public function getBackendUnavailable(): ?string
    {
        return $this->backendUnavailable;
    }//end getBackendUnavailable()
}//end class
