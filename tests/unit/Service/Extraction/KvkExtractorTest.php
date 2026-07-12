<?php

/**
 * Unit tests for KvkExtractor
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

use OCA\DocuDesk\Service\Extraction\KvkExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Tests for KvkExtractor.
 */
class KvkExtractorTest extends TestCase
{

    private KvkExtractor $extractor;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new KvkExtractor();

    }//end setUp()

    /**
     * "KvK: 12345678" is extracted as the KvK number.
     *
     * @return void
     */
    public function testLabelledKvkExtracted(): void
    {
        $result = $this->extractor->extract('Hostbaar B.V. KvK: 12345678');

        $this->assertSame('12345678', $result['value']);
        $this->assertGreaterThan(0.0, $result['confidence']);

    }//end testLabelledKvkExtracted()

    /**
     * No "KvK" label present yields a null value even if 8 digits appear.
     *
     * @return void
     */
    public function testUnlabelledDigitsNotExtracted(): void
    {
        $result = $this->extractor->extract('Referentie: 12345678');

        $this->assertNull($result['value']);
        $this->assertSame(0.0, $result['confidence']);

    }//end testUnlabelledDigitsNotExtracted()
}//end class
