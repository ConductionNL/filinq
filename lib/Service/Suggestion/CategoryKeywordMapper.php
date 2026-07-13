<?php

/**
 * Category Keyword Mapper
 *
 * Pure, side-effect-free helper implementing the cold-start GL-account
 * fallback: matches admin-editable keyword/category rules against a
 * supplier name or document text when no booking history exists
 * (REQ-GLS-03). DocuDesk ships zero rules — the rule table is entirely
 * tenant-authored, so no chart of accounts is ever hardcoded here.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Suggestion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Suggestion;

/**
 * Matches admin-edited keyword/category rules against free text.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Suggestion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
 */
class CategoryKeywordMapper
{

    /**
     * Fixed confidence assigned to a cold-start keyword-rule match — always
     * lower than a meaningfully history-backed suggestion.
     *
     * @var float
     */
    public const COLD_START_CONFIDENCE = 0.4;

    /**
     * Match the highest-priority enabled rule whose keyword substring-matches
     * the given text.
     *
     * @param string                           $text  Supplier name and/or document text to match
     *                                                against (case-insensitive).
     * @param array<int, array<string, mixed>> $rules Admin-authored mapping rules (each `{keywords[],
     *                                                accountCode, accountLabel?, priority?,
     *                                                enabled?}`), in any order.
     *
     * @return array<string, mixed>|null The matched suggestion (`{code, label, confidence, rationale}`),
     *         or null when no enabled rule matches.
     *
     * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
     */
    public function match(string $text, array $rules): ?array
    {
        $haystack = mb_strtolower($text);
        if ($haystack === '' || $rules === []) {
            return null;
        }

        $ordered = $rules;
        usort(
            $ordered,
            static fn (array $left, array $right): int => (int) ($right['priority'] ?? 0) <=> (int) ($left['priority'] ?? 0)
        );

        foreach ($ordered as $rule) {
            $match = $this->matchRule(haystack: $haystack, rule: $rule);
            if ($match !== null) {
                return $match;
            }
        }

        return null;

    }//end match()

    /**
     * Attempt to match a single rule's keywords against the haystack.
     *
     * @param string               $haystack Lower-cased search text.
     * @param array<string, mixed> $rule     Candidate rule (`{keywords[], accountCode, accountLabel?,
     *                                       priority?, enabled?}`).
     *
     * @return array<string, mixed>|null The matched suggestion, or null when this rule does not match.
     */
    private function matchRule(string $haystack, array $rule): ?array
    {
        if (($rule['enabled'] ?? true) === false) {
            return null;
        }

        $keywords = ($rule['keywords'] ?? []);
        foreach ($keywords as $keyword) {
            $needle = mb_strtolower((string) $keyword);
            if ($needle === '' || str_contains($haystack, $needle) === false) {
                continue;
            }

            $code = (string) ($rule['accountCode'] ?? '');
            return [
                'code'       => $code,
                'label'      => ($rule['accountLabel'] ?? null),
                'confidence' => self::COLD_START_CONFIDENCE,
                'rationale'  => sprintf("Keyword '%s' matched mapping rule \u{2192} %s", $keyword, $code),
            ];
        }

        return null;

    }//end matchRule()
}//end class
