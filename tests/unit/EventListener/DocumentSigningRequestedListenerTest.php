<?php

/**
 * Unit tests for DocumentSigningRequestedListener
 *
 * Verifies the cross-app delegated-signing contract (docudesk-signing-events):
 * the listener maps a DocumentSigningRequestedEvent onto
 * SigningService::createRequest, writes the result slot on success, and is
 * fail-soft (no exception escapes, event left unhandled) on failure.
 *
 * @category  Tests
 * @package   OCA\DocuDesk\Tests\Unit\EventListener
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

namespace OCA\DocuDesk\Tests\Unit\EventListener;

use OCA\DocuDesk\Event\DocumentSigningRequestedEvent;
use OCA\DocuDesk\Event\SigningProvenance;
use OCA\DocuDesk\EventListener\DocumentSigningRequestedListener;
use OCA\DocuDesk\Service\SigningService;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests for DocumentSigningRequestedListener.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class DocumentSigningRequestedListenerTest extends TestCase
{

    /**
     * Build a representative request event.
     *
     * @return DocumentSigningRequestedEvent
     */
    private function makeEvent(): DocumentSigningRequestedEvent
    {
        return new DocumentSigningRequestedEvent(
            provenance: new SigningProvenance(
                sourceApp: 'shillinq',
                subjectRegister: 'finance',
                subjectSchema: 'invoice',
                subjectId: 'inv-42',
                externalReference: 'ext-42',
                correlationId: 'corr-42'
            ),
            subjectLabel: 'Invoice 42',
            documentReference: 'file-99',
            signers: [['userId' => 'bob']],
            signatureLevel: 'SES',
            signingMode: 'sequential'
        );

    }//end makeEvent()

    /**
     * A handled request writes the signing-request id and handled flag.
     *
     * @return void
     */
    public function testHandleSuccessWritesResultSlot(): void
    {
        $signingService = $this->createMock(SigningService::class);
        $signingService->expects($this->once())
            ->method('createRequest')
            ->willReturnCallback(
                function (array $data): array {
                    // Provenance is threaded onto the create data.
                    $this->assertSame('shillinq', $data['sourceApp']);
                    $this->assertSame('inv-42', $data['subjectId']);
                    $this->assertSame('file-99', $data['documentFileId']);
                    return ['id' => 'req-777'];
                }
            );

        $listener = new DocumentSigningRequestedListener(
            signingService: $signingService,
            logger: $this->createMock(LoggerInterface::class)
        );

        $event = $this->makeEvent();
        $listener->handle($event);

        $this->assertTrue($event->isHandled());
        $this->assertSame('req-777', $event->getSigningRequestId());

    }//end testHandleSuccessWritesResultSlot()

    /**
     * A service failure is swallowed; the event is left unhandled.
     *
     * @return void
     */
    public function testHandleFailureLeavesEventUnhandled(): void
    {
        $signingService = $this->createMock(SigningService::class);
        $signingService->method('createRequest')
            ->willThrowException(new RuntimeException('boom'));

        $listener = new DocumentSigningRequestedListener(
            signingService: $signingService,
            logger: $this->createMock(LoggerInterface::class)
        );

        $event = $this->makeEvent();
        $listener->handle($event);

        $this->assertFalse($event->isHandled());
        $this->assertNull($event->getSigningRequestId());

    }//end testHandleFailureLeavesEventUnhandled()

    /**
     * A non-matching event type is ignored.
     *
     * @return void
     */
    public function testHandleIgnoresOtherEvents(): void
    {
        $signingService = $this->createMock(SigningService::class);
        $signingService->expects($this->never())->method('createRequest');

        $listener = new DocumentSigningRequestedListener(
            signingService: $signingService,
            logger: $this->createMock(LoggerInterface::class)
        );

        $listener->handle(new Event());
        $this->addToAssertionCount(1);

    }//end testHandleIgnoresOtherEvents()
}//end class
