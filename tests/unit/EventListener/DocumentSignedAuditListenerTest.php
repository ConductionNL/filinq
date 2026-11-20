<?php

/**
 * Unit tests for DocumentSignedAuditListener
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
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\Event\Webhook\DocumentSignedEvent;
use OCA\Filinq\Event\Webhook\DocumentWebhookSubject;
use OCA\Filinq\EventListener\DocumentSignedAuditListener;
use OCA\Filinq\Service\Audit\DocumentActionAudit;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @covers \OCA\Filinq\EventListener\DocumentSignedAuditListener
 */
#[CoversClass(DocumentSignedAuditListener::class)]
class DocumentSignedAuditListenerTest extends TestCase {

	/**
	 * A completed signing writes one `signed` entry on the signed file.
	 *
	 * @return void
	 */
	public function testACompletedSigningIsSigned(): void {
		$audit = $this->createMock(DocumentActionAudit::class);
		$audit->expects($this->once())->method('record')->with(
			fileId: 41,
			action: 'signed',
			context: ['signingRequestId' => 'req-1', 'signatureLevel' => 'advanced']
		)->willReturn(true);

		(new DocumentSignedAuditListener($audit, $this->createMock(LoggerInterface::class)))->handle($this->signed(fileId: '41'));

	}//end testACompletedSigningIsSigned()

	/**
	 * A signing that names no file, and any other event, write nothing.
	 *
	 * @return void
	 */
	public function testNothingButASignedFileIsWritten(): void {
		$audit = $this->createMock(DocumentActionAudit::class);
		$audit->expects($this->never())->method('record');
		$listener = new DocumentSignedAuditListener($audit, $this->createMock(LoggerInterface::class));

		$listener->handle($this->signed(fileId: null));
		$listener->handle($this->signed(fileId: 'not-a-number'));
		$listener->handle(new Event());

	}//end testNothingButASignedFileIsWritten()

	/**
	 * A failing write is logged and never escapes to the signing.
	 *
	 * @return void
	 */
	public function testAFailingWriteIsLogged(): void {
		$audit = $this->createMock(DocumentActionAudit::class);
		$audit->method('record')->willThrowException(new RuntimeException('trail down'));
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning');

		(new DocumentSignedAuditListener($audit, $logger))->handle($this->signed(fileId: 41));

	}//end testAFailingWriteIsLogged()

	/**
	 * A signed event for a file.
	 *
	 * @param int|string|null $fileId The signed file, as the record holds it.
	 *
	 * @return DocumentSignedEvent
	 */
	private function signed(int|string|null $fileId): DocumentSignedEvent {
		return new DocumentSignedEvent(
			subject: new DocumentWebhookSubject(fileId: $fileId, name: 'contract.pdf', register: 'filinq', schema: 'signingRequest', id: 'req-1', organisation: null),
			actor: 'anna',
			details: ['signingRequestId' => 'req-1', 'signedFileRef' => null, 'signatureLevel' => 'advanced'],
			occurredAt: '2026-11-20T10:00:00+00:00'
		);

	}//end signed()
}//end class
