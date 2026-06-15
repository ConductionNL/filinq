<?php

/**
 * Unit tests for the cross-app signing event contract
 *
 * Verifies the docudesk-signing-events contract classes: the request event's
 * immutable getters + writable result slot, and the conclusion event's
 * immutable getters + fromRequest() factory.
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
 * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Event;

use OCA\DocuDesk\Event\DocumentSigningRequestedEvent;
use OCA\DocuDesk\Event\SigningConcludedEvent;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the signing event contract classes.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class SigningEventsTest extends TestCase
{

    /**
     * The request event exposes constructed values and a writable result slot.
     *
     * @return void
     */
    public function testRequestedEventGettersAndResultSlot(): void
    {
        $event = new DocumentSigningRequestedEvent(
            sourceApp: 'shillinq',
            subjectRegister: 'finance',
            subjectSchema: 'invoice',
            subjectId: 'inv-1',
            subjectLabel: 'Invoice 1',
            documentReference: 'file-1',
            signers: [['userId' => 'bob']],
            signatureLevel: 'AdES',
            signingMode: 'parallel',
            externalReference: 'ext-1',
            correlationId: 'corr-1'
        );

        $this->assertSame('shillinq', $event->getSourceApp());
        $this->assertSame('finance', $event->getSubjectRegister());
        $this->assertSame('invoice', $event->getSubjectSchema());
        $this->assertSame('inv-1', $event->getSubjectId());
        $this->assertSame('Invoice 1', $event->getSubjectLabel());
        $this->assertSame('file-1', $event->getDocumentReference());
        $this->assertSame([['userId' => 'bob']], $event->getSigners());
        $this->assertSame('AdES', $event->getSignatureLevel());
        $this->assertSame('parallel', $event->getSigningMode());
        $this->assertSame('ext-1', $event->getExternalReference());
        $this->assertSame('corr-1', $event->getCorrelationId());

        // Result slot defaults, then writes.
        $this->assertNull($event->getSigningRequestId());
        $this->assertFalse($event->isHandled());

        $event->setSigningRequestId('req-1');
        $event->setHandled(true);
        $this->assertSame('req-1', $event->getSigningRequestId());
        $this->assertTrue($event->isHandled());

    }//end testRequestedEventGettersAndResultSlot()

    /**
     * fromRequest() maps the persisted request + status onto the conclusion event.
     *
     * @return void
     */
    public function testConcludedEventFromRequest(): void
    {
        $request = [
            'id'                => 'req-9',
            'signerIds'         => ['s1', 's2'],
            'signedAt'          => '2026-06-15T10:00:00+00:00',
            'sourceApp'         => 'shillinq',
            'subjectRegister'   => 'finance',
            'subjectSchema'     => 'invoice',
            'subjectId'         => 'inv-9',
            'externalReference' => 'ext-9',
            'correlationId'     => 'corr-9',
        ];

        $event = SigningConcludedEvent::fromRequest(
            request: $request,
            status: 'signed',
            signedDocumentRef: 'file-signed-9'
        );

        $this->assertSame('req-9', $event->getSigningRequestId());
        $this->assertSame('signed', $event->getStatus());
        $this->assertSame('file-signed-9', $event->getSignedDocumentRef());
        $this->assertSame(['s1', 's2'], $event->getSigners());
        $this->assertSame('2026-06-15T10:00:00+00:00', $event->getSignedAt());
        $this->assertSame('shillinq', $event->getSourceApp());
        $this->assertSame('finance', $event->getSubjectRegister());
        $this->assertSame('invoice', $event->getSubjectSchema());
        $this->assertSame('inv-9', $event->getSubjectId());
        $this->assertSame('ext-9', $event->getExternalReference());
        $this->assertSame('corr-9', $event->getCorrelationId());

    }//end testConcludedEventFromRequest()

    /**
     * fromRequest() tolerates a minimal internal request (no provenance).
     *
     * @return void
     */
    public function testConcludedEventFromMinimalRequest(): void
    {
        $event = SigningConcludedEvent::fromRequest(
            request: ['uuid' => 'req-min'],
            status: 'cancelled'
        );

        $this->assertSame('req-min', $event->getSigningRequestId());
        $this->assertSame('cancelled', $event->getStatus());
        $this->assertNull($event->getSignedDocumentRef());
        $this->assertSame([], $event->getSigners());
        $this->assertSame('', $event->getSourceApp());

    }//end testConcludedEventFromMinimalRequest()
}//end class
