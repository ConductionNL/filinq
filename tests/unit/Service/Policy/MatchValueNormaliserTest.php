<?php

/**
 * MatchValueNormaliserTest
 *
 * This file is part of the DocuDesk app for Nextcloud.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category  Test
 * @package   OCA\DocuDesk
 * @author    Conduction <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://github.com/ConductionNL/docudesk
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service\Policy;

use OCA\DocuDesk\Service\Policy\MatchValueNormaliser;
use PHPUnit\Framework\TestCase;

/**
 * Covers the shared match-value normalisation.
 *
 * The prohibition form now offers an exact / not-exact switch instead of the four
 * raw match types, and when "not exact" is chosen the STORED criterion is the
 * normalised form. That makes this transform part of the data contract rather
 * than an internal detail of matching, so it is tested directly.
 *
 * Idempotency is the property that makes write-time normalisation safe: the
 * matcher normalises the stored value AGAIN at match time, so a value normalised
 * on save must survive that second pass unchanged.
 *
 * @category Test
 * @package  OCA\DocuDesk
 * @author   Conduction <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://github.com/ConductionNL/docudesk
 */
class MatchValueNormaliserTest extends TestCase
{


    /**
     * Casing is folded.
     *
     * @return void
     */
    public function testCaseIsFolded(): void
    {
        $this->assertSame('jan jansen', MatchValueNormaliser::normalise(value: 'Jan Jansen'));
        $this->assertSame('jan jansen', MatchValueNormaliser::normalise(value: 'JAN JANSEN'));
    }//end testCaseIsFolded()


    /**
     * Surrounding whitespace is trimmed, which matters because an operator
     * pasting a name commonly brings a trailing space with it.
     *
     * @return void
     */
    public function testSurroundingWhitespaceIsTrimmed(): void
    {
        $this->assertSame('jan jansen', MatchValueNormaliser::normalise(value: '  Jan Jansen  '));
    }//end testSurroundingWhitespaceIsTrimmed()


    /**
     * Diacritics are stripped, so an accented name still matches its unaccented
     * spelling. Skipped when intl cannot supply the transliterator, in which case
     * the documented fallback only lowercases.
     *
     * @return void
     */
    public function testDiacriticsAreStripped(): void
    {
        if (\Transliterator::create('Any-Latin; Latin-ASCII; Lower()') === null) {
            $this->markTestSkipped('intl transliterator unavailable; the weaker fallback is documented behaviour');
        }

        $this->assertSame('aniela otzu', MatchValueNormaliser::normalise(value: 'Aniéla Ötzü'));
        $this->assertSame('jansen', MatchValueNormaliser::normalise(value: 'Jansén'));
    }//end testDiacriticsAreStripped()


    /**
     * Normalising an already-normalised value changes nothing.
     *
     * This is what lets the CRUD layer normalise on write while the matcher
     * normalises again on read without the value degrading.
     *
     * @return void
     */
    public function testNormalisationIsIdempotent(): void
    {
        foreach (['Jan Jansen', '  Aniéla Ötzü ', 'ORGANISATIE B.V.', ''] as $value) {
            $once  = MatchValueNormaliser::normalise(value: $value);
            $twice = MatchValueNormaliser::normalise(value: $once);
            $this->assertSame($once, $twice, 'normalising twice must equal normalising once');
        }
    }//end testNormalisationIsIdempotent()


    /**
     * An empty value stays empty rather than erroring.
     *
     * @return void
     */
    public function testEmptyValueIsSafe(): void
    {
        $this->assertSame('', MatchValueNormaliser::normalise(value: ''));
        $this->assertSame('', MatchValueNormaliser::normalise(value: '   '));
    }//end testEmptyValueIsSafe()


    /**
     * Only `normalized` rules have their value rewritten.
     *
     * `exact` must stay byte-for-byte or the switch would silently change what an
     * exact rule matches, and `bsn`/`kvk` carry an identifier or the `*` wildcard
     * — neither is text to be folded.
     *
     * @return void
     */
    public function testOnlyNormalizedRulesAreRewritten(): void
    {
        $rules = MatchValueNormaliser::normaliseRuleValues(
            matchRules: [
                ['type' => 'normalized', 'value' => '  Jan JANSEN '],
                ['type' => 'exact', 'value' => '  Jan JANSEN '],
                ['type' => 'bsn', 'value' => '123456782'],
                ['type' => 'kvk', 'value' => '*'],
            ]
        );

        $this->assertSame('jan jansen', $rules[0]['value'], 'normalized is folded');
        $this->assertSame('  Jan JANSEN ', $rules[1]['value'], 'exact is untouched');
        $this->assertSame('123456782', $rules[2]['value'], 'bsn is untouched');
        $this->assertSame('*', $rules[3]['value'], 'the kvk wildcard survives');
    }//end testOnlyNormalizedRulesAreRewritten()


    /**
     * Other keys on a rule are preserved.
     *
     * @return void
     */
    public function testOtherRuleKeysArePreserved(): void
    {
        $rules = MatchValueNormaliser::normaliseRuleValues(
            matchRules: [['type' => 'normalized', 'value' => 'Jan', 'note' => 'keep me']]
        );

        $this->assertSame('keep me', $rules[0]['note']);
        $this->assertSame('normalized', $rules[0]['type']);
    }//end testOtherRuleKeysArePreserved()


    /**
     * Malformed rules are skipped rather than throwing.
     *
     * A write that fails because one rule in a list was the wrong shape would
     * lose the operator's whole form; skipping is the lesser harm.
     *
     * @return void
     */
    public function testMalformedRulesAreSkipped(): void
    {
        $rules = MatchValueNormaliser::normaliseRuleValues(
            matchRules: [
                'not-an-array',
                ['type' => 'normalized'],
                ['type' => 'normalized', 'value' => 123],
                ['value' => 'no type'],
                ['type' => 'normalized', 'value' => ' OK '],
            ]
        );

        $this->assertSame('not-an-array', $rules[0]);
        $this->assertSame(['type' => 'normalized'], $rules[1]);
        $this->assertSame(123, $rules[2]['value'], 'a non-string value is left alone');
        $this->assertSame('no type', $rules[3]['value']);
        $this->assertSame('ok', $rules[4]['value'], 'valid rules are still processed');
    }//end testMalformedRulesAreSkipped()


    /**
     * An empty rule list is returned unchanged.
     *
     * @return void
     */
    public function testEmptyRuleListIsSafe(): void
    {
        $this->assertSame([], MatchValueNormaliser::normaliseRuleValues(matchRules: []));
    }//end testEmptyRuleListIsSafe()
}//end class
