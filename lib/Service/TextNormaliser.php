<?php

/**
 * TextNormaliser — injectable seam over the intl transliteration API.
 *
 * The `normalized` policy match type compares accent-stripped, lower-cased
 * text. That needs ext-intl, which is not installed in every CI image, so the
 * lookup is attempted once per instance and falls back to `mb_strtolower()`
 * when the extension is absent.
 *
 * Wrapping it in its own collaborator keeps the extension probe out of the
 * matcher and lets callers substitute a deterministic normaliser in tests.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

/**
 * Accent-stripping, lower-casing text normaliser with an intl-free fallback.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 */
class TextNormaliser
{

    /**
     * The transliteration ruleset applied to every value.
     */
    private const RULESET = 'Any-Latin; Latin-ASCII; Lower';

    /**
     * Lazily-built transliterator, or null when ext-intl is unavailable.
     *
     * @var object|null
     */
    private ?object $transliterator = null;

    /**
     * Whether the transliterator lookup has already been attempted.
     *
     * @var boolean
     */
    private bool $lookupAttempted = false;

    /**
     * Lower-case and accent-strip a string.
     *
     * Falls back to `mb_strtolower()` when the PHP intl extension is not
     * available (e.g. bare-CLI CI environments without ext-intl).
     *
     * @param string $value Source string.
     *
     * @return string Normalised string.
     *
     * @spec openspec/specs/entity-publication-policies/spec.md
     */
    public function normalise(string $value): string
    {
        $transliterator = $this->transliterator();
        if ($transliterator !== null) {
            $transliterated = transliterator_transliterate($transliterator, $value);
            if (is_string($transliterated) === true) {
                return trim($transliterated);
            }
        }

        return trim(mb_strtolower($value));

    }//end normalise()

    /**
     * Resolve the transliterator once per instance.
     *
     * @return object|null The transliterator, or null when ext-intl is absent
     *                     or the ruleset could not be compiled.
     */
    private function transliterator(): ?object
    {
        if ($this->lookupAttempted === true) {
            return $this->transliterator;
        }

        $this->lookupAttempted = true;

        if (function_exists('transliterator_create') === true) {
            $this->transliterator = transliterator_create(self::RULESET);
        }

        return $this->transliterator;

    }//end transliterator()
}//end class
