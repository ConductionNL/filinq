<?php

/**
 * MatchValueNormaliser
 *
 * This file is part of the DocuDesk app for Nextcloud.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category  Service
 * @package   OCA\DocuDesk
 * @author    Conduction <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://github.com/ConductionNL/docudesk
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Policy;

use Transliterator;

/**
 * The single definition of "normalised" for policy match values.
 *
 * Extracted from `PolicyMatchService` rather than reimplemented. It now has two
 * callers and they MUST agree: the matcher normalises entity text at match time,
 * and the CRUD layer normalises a rule's value at write time so the stored
 * criterion is the normalised form the operator was shown.
 *
 * Deliberately static, with no constructor dependencies. Adding
 * `PolicyMatchService` to `PolicyCrudService`'s constructor would have widened an
 * already-6-argument signature, and this repository already carries nine failing
 * tests caused by exactly that (`ArgumentCountError`, 6 passed / 7 expected on
 * `AnonymizationService` and `SettingsService`). Not worth repeating for a pure
 * string transform.
 *
 * The operation is IDEMPOTENT — normalising an already-normalised value is a
 * no-op — which is what makes write-time normalisation safe: the matcher
 * normalises the stored value again and reaches the same result either way.
 *
 * @category Service
 * @package  OCA\DocuDesk
 * @author   Conduction <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://github.com/ConductionNL/docudesk
 */
final class MatchValueNormaliser
{

    /**
     * Match type whose value is compared in normalised form.
     *
     * @var string
     */
    public const TYPE_NORMALIZED = 'normalized';

    /**
     * Match type whose value is compared byte-for-byte.
     *
     * @var string
     */
    public const TYPE_EXACT = 'exact';

    /**
     * Cached transliterator. Building it is the expensive part, and a rule set
     * can hold many values.
     *
     * @var Transliterator|null
     */
    private static ?Transliterator $transliterator = null;

    /**
     * Whether transliterator construction has been attempted.
     *
     * Distinct from the null check above: `Transliterator::create()` returns null
     * when intl lacks the ruleset, and without this flag every call would retry
     * a construction already known to fail.
     *
     * @var boolean
     */
    private static bool $attempted = false;


    /**
     * Lower-case, accent-strip and trim a value for normalised matching.
     *
     * Falls back to `mb_strtolower` + `trim` when intl cannot supply the
     * transliterator. That fallback is WEAKER — it lowercases but does not strip
     * diacritics or transliterate non-Latin scripts — so on such an instance
     * `Jansén` and `Jansen` are different values. Accepted rather than hidden:
     * the alternative is failing the write outright, and a rule that matches
     * slightly less is better than a rule that cannot be saved.
     *
     * @param string $value The raw value.
     *
     * @return string The normalised value.
     */
    public static function normalise(string $value): string
    {
        if (self::$attempted === false) {
            self::$attempted      = true;
            self::$transliterator = Transliterator::create('Any-Latin; Latin-ASCII; Lower()');
        }

        if (self::$transliterator !== null) {
            $transliterated = self::$transliterator->transliterate($value);
            if ($transliterated !== false) {
                return trim($transliterated);
            }
        }

        return trim(mb_strtolower($value));

    }//end normalise()


    /**
     * Normalise the values of every `normalized` rule in a match-rule list.
     *
     * Rules of any other type are returned untouched — `exact` must stay
     * byte-for-byte, and `bsn`/`kvk` carry an identifier or the `*` wildcard,
     * neither of which is text to be folded.
     *
     * @param array<int, mixed> $matchRules The rule list as submitted.
     *
     * @return array<int, mixed> The rule list with normalised values applied.
     */
    public static function normaliseRuleValues(array $matchRules): array
    {
        foreach ($matchRules as $index => $rule) {
            if (is_array($rule) === false) {
                continue;
            }

            if ((string) ($rule['type'] ?? '') !== self::TYPE_NORMALIZED) {
                continue;
            }

            if (isset($rule['value']) === false || is_string($rule['value']) === false) {
                continue;
            }

            $matchRules[$index]['value'] = self::normalise(value: $rule['value']);
        }

        return $matchRules;

    }//end normaliseRuleValues()


}//end class
