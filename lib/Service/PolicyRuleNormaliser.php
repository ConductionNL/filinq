<?php

/**
 * PolicyRuleNormaliser — turns raw policy rows into the matcher's cache shape.
 *
 * Both policy surfaces (`publicationProhibition` and `scope: "entity"`
 * `publicationConsent`) are stored as free-form OpenRegister objects. Before
 * PolicyMatchService can match against them they must be filtered on `active`
 * and their validity window, and reduced to the minimal `{uuid, kind,
 * entityType, matchRules, validFrom, validUntil, primaryName}` shape.
 *
 * That admission logic is a concern of its own — it decides which stored rows
 * are eligible to influence a match at all — so it lives here rather than in
 * the matcher.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use DateTimeImmutable;
use Exception;

/**
 * Admission + normalisation of stored policy rows.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 */
class PolicyRuleNormaliser
{
    /**
     * Normalise a raw policy row into the matcher's cache shape.
     *
     * Applies the `active` and validity-window filters; returns null when the
     * row must not be matched (inactive, window closed, no usable match rules,
     * or no resolvable UUID).
     *
     * @param string               $kind   Rule kind, as recorded on the cache entry.
     * @param array<string, mixed> $object Raw object data.
     *
     * @return array<string, mixed>|null The cache entry, or null when inadmissible.
     *
     * @spec openspec/specs/entity-publication-policies/spec.md
     */
    public function normaliseRule(string $kind, array $object): ?array
    {
        if (($object['active'] ?? true) !== true) {
            return null;
        }

        $matchRules = ($object['matchRules'] ?? null);
        if (is_array($matchRules) === false || count($matchRules) === 0) {
            return null;
        }

        $window = $this->validityWindow(object: $object);
        if ($window === null) {
            return null;
        }

        $uuid = $this->readUuid(object: $object);
        if ($uuid === '') {
            return null;
        }

        return [
            'uuid'        => $uuid,
            'kind'        => $kind,
            'entityType'  => (string) ($object['entityType'] ?? 'OTHER'),
            'matchRules'  => $this->wellFormedRules(matchRules: $matchRules),
            'validFrom'   => $window['from'],
            'validUntil'  => $window['until'],
            'primaryName' => (string) ($object['primaryName'] ?? $object['entityText'] ?? ''),
        ];

    }//end normaliseRule()

    /**
     * Resolve the row's validity window, or null when it is currently closed.
     *
     * @param array<string, mixed> $object Raw object data.
     *
     * @return array{from: DateTimeImmutable|null, until: DateTimeImmutable|null}|null
     *         The parsed bounds, or null when now falls outside them.
     */
    private function validityWindow(array $object): ?array
    {
        $now        = new DateTimeImmutable();
        $validFrom  = $this->parseDateTime(value: (string) ($object['validFrom'] ?? ''));
        $validUntil = $this->parseDateTime(value: (string) ($object['validUntil'] ?? ''));

        if ($validFrom !== null && $validFrom > $now) {
            return null;
        }

        if ($validUntil !== null && $validUntil < $now) {
            return null;
        }

        return [
            'from'  => $validFrom,
            'until' => $validUntil,
        ];

    }//end validityWindow()

    /**
     * Keep only the `{type, value}` entries of a raw matchRules array.
     *
     * @param array<mixed> $matchRules Raw match rules as stored.
     *
     * @return array<int, array<string, mixed>> The well-formed rules, re-indexed.
     */
    private function wellFormedRules(array $matchRules): array
    {
        return array_values(
            array_filter(
                $matchRules,
                static fn ($r): bool => is_array($r) === true
                    && isset($r['type'], $r['value']) === true
            )
        );

    }//end wellFormedRules()

    /**
     * Read a row's UUID from its `@self` envelope or its top-level keys.
     *
     * @param array<string, mixed> $object Raw object data.
     *
     * @return string The UUID, or an empty string when the row carries none.
     */
    private function readUuid(array $object): string
    {
        $self = ($object['@self'] ?? []);

        return (string) (
            $self['id'] ?? $self['uuid'] ?? $object['id'] ?? $object['uuid'] ?? ''
        );

    }//end readUuid()

    /**
     * Parse an ISO-8601 string into DateTimeImmutable; null on failure.
     *
     * @param string $value The raw value (may be empty).
     *
     * @return DateTimeImmutable|null The parsed instant, or null.
     */
    private function parseDateTime(string $value): ?DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }

    }//end parseDateTime()
}//end class
