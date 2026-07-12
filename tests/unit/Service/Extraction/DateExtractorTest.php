<?php

/**
 * Unit tests for DateExtractor
 *
 * @category  Tests
 * @package   OCA\DocuDesk\Tests\Unit\Service\Extraction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service\Extraction;

use OCA\DocuDesk\Service\Extraction\DateExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Tests for DateExtractor.
 */
class DateExtractorTest extends TestCase
{

    private DateExtractor $extractor;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new DateExtractor();

    }//end setUp()

    /**
     * "Factuurdatum: 15-03-2024" normalises to ISO 8601.
     *
     * @return void
     */
    public function testDutchDateNormalisedToIso(): void
    {
        $result = $this->extractor->extractLabelled('Factuurdatum: 15-03-2024', ['factuurdatum']);

        $this->assertSame('2024-03-15', $result['value']);

    }//end testDutchDateNormalisedToIso()

    /**
     * An ISO date is passed through unchanged.
     *
     * @return void
     */
    public function testIsoDateExtracted(): void
    {
        $result = $this->extractor->extractLabelled('Datum: 2024-03-15', ['datum']);

        $this->assertSame('2024-03-15', $result['value']);

    }//end testIsoDateExtracted()

    /**
     * A Dutch long-form date ("D MMMM YYYY") normalises to ISO 8601.
     *
     * @return void
     */
    public function testDutchLongFormDateNormalised(): void
    {
        $result = $this->extractor->extractLabelled('Vervaldatum: 15 maart 2024', ['vervaldatum']);

        $this->assertSame('2024-03-15', $result['value']);

    }//end testDutchLongFormDateNormalised()

    /**
     * Unparseable input yields null, never a thrown exception.
     *
     * @return void
     */
    public function testUnparseableInputYieldsNullNoThrow(): void
    {
        $result = $this->extractor->extractLabelled('Factuurdatum: binnenkort', ['factuurdatum']);

        $this->assertNull($result['value']);
        $this->assertSame(0.0, $result['confidence']);

    }//end testUnparseableInputYieldsNullNoThrow()

    /**
     * An invalid calendar date (32nd of April) is rejected.
     *
     * @return void
     */
    public function testInvalidCalendarDateRejected(): void
    {
        $result = $this->extractor->extractLabelled('Datum: 32-04-2024', ['datum']);

        $this->assertNull($result['value']);

    }//end testInvalidCalendarDateRejected()

    /**
     * extractAll() returns every distinct date in document order.
     *
     * @return void
     */
    public function testExtractAllReturnsDistinctDatesInOrder(): void
    {
        $results = $this->extractor->extractAll('Factuurdatum: 15-03-2024. Vervaldatum: 14-04-2024.');

        $this->assertCount(2, $results);
        $this->assertSame('2024-03-15', $results[0]['value']);
        $this->assertSame('2024-04-14', $results[1]['value']);

    }//end testExtractAllReturnsDistinctDatesInOrder()

    /**
     * A "Subtotaal"-style longer label does not falsely match a shorter label.
     *
     * @return void
     */
    public function testLabelBoundaryDoesNotMatchSubstring(): void
    {
        $result = $this->extractor->extractLabelled('Uiterste betaaldatum: 14-04-2024', ['datum']);

        // "datum" is not a whole-word label here ("betaaldatum" contains it as
        // a substring but not as a \b-delimited word), so no match.
        $this->assertNull($result['value']);

    }//end testLabelBoundaryDoesNotMatchSubstring()
}//end class
