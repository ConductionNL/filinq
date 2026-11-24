<?php

/**
 * Unit tests for the signed action of DocumentFileActionListener
 *
 * A completed signing request is the `signed` action of the signed document,
 * naming the signing request and the signature level; an event without a file
 * writes nothing, and a failed write never escapes (specify-capability-rows
 * REQ-SCR-007).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\Event\Webhook\DocumentSignedEvent;
use OCA\Filinq\Event\Webhook\DocumentWebhookSubject;
use OCA\Filinq\EventListener\DocumentFileActionListener;
use OCA\Filinq\EventListener\ListenerRegistrar;
use OCA\Filinq\Service\Audit\DocumentActionAudit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @covers \OCA\Filinq\EventListener\DocumentFileActionListener
 */
#[CoversClass(DocumentFileActionListener::class)]
class DocumentFileActionListenerSignedTest extends TestCase {

	/**
	 * A completed signing writes one `signed` entry on the signed file.
	 *
	 * @return void
	 */
	public function testACompletedSigningIsSigned(): void {
		$audit = $this->createMock(originalClassName: DocumentActionAudit::class);
		$audit->expects($this->once())->method(constraint: 'record')->with(
			41,
			'signed',
			['signingRequestId' => 'req-1', 'signatureLevel' => 'advanced']
		)->willReturn(value: true);

		$this->listener(audit: $audit)->handle(event: $this->signed(fileId: '41'));

	}//end testACompletedSigningIsSigned()

	/**
	 * A signing that names no file writes nothing.
	 *
	 * @return void
	 */
	public function testASigningWithoutAFileWritesNothing(): void {
		$audit = $this->createMock(originalClassName: DocumentActionAudit::class);
		$audit->expects($this->never())->method(constraint: 'record');
		$listener = $this->listener(audit: $audit);

		$listener->handle(event: $this->signed(fileId: null));
		$listener->handle(event: $this->signed(fileId: 'not-a-number'));

	}//end testASigningWithoutAFileWritesNothing()

	/**
	 * A failing write is logged and never escapes to the signing.
	 *
	 * @return void
	 */
	public function testAFailingWriteIsLogged(): void {
		$audit = $this->createMock(originalClassName: DocumentActionAudit::class);
		$audit->expects($this->once())->method(constraint: 'record')->willThrowException(exception: new RuntimeException(message: 'trail down'));
		$logger = $this->createMock(originalClassName: LoggerInterface::class);
		$logger->expects($this->once())->method(constraint: 'warning');

		(new DocumentFileActionListener(audit: $audit, logger: $logger))->handle(event: $this->signed(fileId: 41));

	}//end testAFailingWriteIsLogged()

	/**
	 * The listener is registered for the signed event.
	 *
	 * @return void
	 */
	public function testTheListenerIsRegisteredForTheSignedEvent(): void {
		$this->assertContains(needle: DocumentSignedEvent::class, haystack: ListenerRegistrar::DOCUMENT_FILE_EVENTS);

	}//end testTheListenerIsRegisteredForTheSignedEvent()

	/**
	 * The listener over an audit double.
	 *
	 * @param DocumentActionAudit $audit The audit double.
	 *
	 * @return DocumentFileActionListener
	 */
	private function listener(DocumentActionAudit $audit): DocumentFileActionListener {
		return new DocumentFileActionListener(audit: $audit, logger: $this->createMock(originalClassName: LoggerInterface::class));

	}//end listener()

	/**
	 * A signed event for a file.
	 *
	 * @param int|string|null $fileId The signed file, as the record holds it.
	 *
	 * @return DocumentSignedEvent
	 */
	private function signed(int|string|null $fileId): DocumentSignedEvent {
		$subject = new DocumentWebhookSubject(
			fileId: $fileId,
			name: 'contract.pdf',
			register: 'filinq',
			schema: 'signingRequest',
			id: 'req-1',
			organisation: null
		);

		return new DocumentSignedEvent(
			subject: $subject,
			actor: 'anna',
			details: ['signingRequestId' => 'req-1', 'signedFileRef' => null, 'signatureLevel' => 'advanced'],
			occurredAt: '2026-11-20T10:00:00+00:00'
		);

	}//end signed()
}//end class
