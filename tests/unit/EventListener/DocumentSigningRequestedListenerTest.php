<?php

/**
 * Unit tests for DocumentSigningRequestedListener
 *
 * Verifies the cross-app delegated-signing contract (filinq-signing-events):
 * the listener maps a DocumentSigningRequestedEvent onto
 * SigningService::createRequest, writes the result slot on success, and is
 * fail-soft (no exception escapes, event left unhandled) on failure.
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
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\Event\DocumentSigningRequestedEvent;
use OCA\Filinq\Event\SigningProvenance;
use OCA\Filinq\EventListener\DocumentSigningRequestedListener;
use OCA\Filinq\Service\SigningService;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests for DocumentSigningRequestedListener.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class DocumentSigningRequestedListenerTest extends TestCase {

	/**
	 * Build a representative request event.
	 *
	 * @return DocumentSigningRequestedEvent
	 */
	private function makeEvent(): DocumentSigningRequestedEvent {
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
	public function testHandleSuccessWritesResultSlot(): void {
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
	 * A learner-facing consumer's guardian fields reach createRequest() untouched
	 * (signer-identity-rails REQ-DDSIR-011).
	 *
	 * A contract pin rather than a red-first test: the listener forwards the
	 * signer list as it is today. This fails the day someone narrows it to
	 * userId/displayName/email/order and drops the guardian link in silence.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function testTheGuardianFieldsOfALearnerFacingRequestReachCreateRequest(): void {
		$signers = [
			['userId' => 'sanne', 'displayName' => 'Sanne de Vries', 'birthDate' => '2012-06-01'],
			[
				'userId' => 'mark',
				'displayName' => 'Mark de Vries',
				'role' => 'guardian',
				'guardianFor' => 'sanne',
				'guardianAct' => 'co-sign',
				'guardianRef' => 'learniq/guardian/0001',
			],
		];

		$signingService = $this->createMock(SigningService::class);
		$signingService->expects($this->once())
			->method('createRequest')
			->willReturnCallback(
				function (array $data) use ($signers): array {
					$this->assertSame($signers, $data['signers']);
					$this->assertSame('learniq', $data['sourceApp']);
					return ['id' => 'req-opp-1'];
				}
			);

		$listener = new DocumentSigningRequestedListener(
			signingService: $signingService,
			logger: $this->createMock(LoggerInterface::class)
		);

		$event = new DocumentSigningRequestedEvent(
			provenance: new SigningProvenance(
				sourceApp: 'learniq',
				subjectRegister: 'learniq',
				subjectSchema: 'LearningPlan',
				subjectId: 'plan-1',
				externalReference: 'opp-2026-sanne',
				correlationId: 'corr-opp-1'
			),
			subjectLabel: 'OPP Sanne de Vries',
			documentReference: 'file-opp-1',
			signers: $signers
		);
		$listener->handle($event);

		$this->assertTrue($event->isHandled());

	}//end testTheGuardianFieldsOfALearnerFacingRequestReachCreateRequest()

	/**
	 * A service failure is swallowed; the event is left unhandled.
	 *
	 * @return void
	 */
	public function testHandleFailureLeavesEventUnhandled(): void {
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
	public function testHandleIgnoresOtherEvents(): void {
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
