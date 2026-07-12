<?php

/**
 * Unit tests for FinancialExtractionCompletedEvent
 *
 * @category  Tests
 * @package   OCA\DocuDesk\Tests\Unit\Event
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

namespace OCA\DocuDesk\Tests\Unit\Event;

use OCA\DocuDesk\Event\FinancialExtractionCompletedEvent;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the canonical financial-extraction completion event.
 */
class FinancialExtractionCompletedEventTest extends TestCase
{

    /**
     * Getters return the constructed values (immutable).
     *
     * @return void
     */
    public function testGettersReturnConstructedValues(): void
    {
        $fields = ['supplierName' => 'Hostbaar B.V.', 'totalIncl' => 121.00];

        $event = new FinancialExtractionCompletedEvent(
            documentUri: 'openregister://document/file/1',
            requestedBy: 'annemarie',
            sourceApp: 'shillinq',
            docType: 'supplier-invoice',
            fields: $fields,
            fieldConfidence: ['supplierName' => 0.82, 'totalIncl' => 0.97],
            overallConfidence: 0.9,
        );

        $this->assertSame('openregister://document/file/1', $event->getDocumentUri());
        $this->assertSame('annemarie', $event->getRequestedBy());
        $this->assertSame('shillinq', $event->getSourceApp());
        $this->assertSame('supplier-invoice', $event->getDocType());
        $this->assertSame($fields, $event->getFields());
        $this->assertSame(['supplierName' => 0.82, 'totalIncl' => 0.97], $event->getFieldConfidence());
        $this->assertSame(0.9, $event->getOverallConfidence());

    }//end testGettersReturnConstructedValues()

    /**
     * toPayload() builds the exact canonical wire shape (REQ-FIN-05).
     *
     * @return void
     */
    public function testToPayloadBuildsCanonicalShape(): void
    {
        $event = new FinancialExtractionCompletedEvent(
            documentUri: 'nc://file/42',
            requestedBy: 'bob',
            sourceApp: 'shillinq',
            docType: 'receipt',
            fields: ['totalIncl' => 18.50],
            fieldConfidence: ['totalIncl' => 0.71],
            overallConfidence: 0.71,
        );

        $this->assertSame(
            [
                'documentUri'       => 'nc://file/42',
                'requestedBy'       => 'bob',
                'sourceApp'         => 'shillinq',
                'docType'           => 'receipt',
                'fields'            => ['totalIncl' => 18.50],
                'fieldConfidence'   => ['totalIncl' => 0.71],
                'overallConfidence' => 0.71,
            ],
            $event->toPayload()
        );

    }//end testToPayloadBuildsCanonicalShape()
}//end class
