<?php

/**
 * Unit tests for SigningConclusionEmitter
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Event\SigningConcludedEvent;
use OCA\Filinq\Event\SigningConcludedEventFactory;
use OCA\Filinq\Event\SigningProvenance;
use OCA\Filinq\Service\SigningConclusionEmitter;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Only delegated requests emit, the event carries the persisted request,
 * and a dispatch failure is logged, never thrown.
 */
#[CoversClass(SigningConclusionEmitter::class)]
#[UsesClass(SigningConcludedEventFactory::class)]
#[UsesClass(SigningConcludedEvent::class)]
#[UsesClass(SigningProvenance::class)]
class SigningConclusionEmitterTest extends TestCase {

	/**
	 * The dispatcher.
	 *
	 * @var IEventDispatcher&MockObject
	 */
	private IEventDispatcher $dispatcher;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * The subject.
	 *
	 * @var SigningConclusionEmitter
	 */
	private SigningConclusionEmitter $emitter;

	/**
	 * Build the subject with the real event factory.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->emitter = new SigningConclusionEmitter(
			eventDispatcher: $this->dispatcher,
			logger: $this->logger,
			eventFactory: new SigningConcludedEventFactory()
		);
	}//end setUp()

	/**
	 * A request without a source app emits nothing and logs nothing.
	 *
	 * @return void
	 */
	public function testAnInternalRequestEmitsNothing(): void {
		$this->dispatcher->expects($this->never())->method('dispatchTyped');
		$this->logger->expects($this->never())->method('info');
		$this->logger->expects($this->never())->method('error');

		$this->emitter->emitIfDelegated(request: ['id' => 'r1', 'sourceApp' => ''], status: 'signed');
		$this->emitter->emitIfDelegated(request: ['id' => 'r2'], status: 'signed');
	}//end testAnInternalRequestEmitsNothing()

	/**
	 * A delegated request dispatches an event built from the persisted request and logs it.
	 *
	 * @return void
	 */
	public function testADelegatedRequestDispatchesTheConclusion(): void {
		$captured = null;
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->willReturnCallback(function ($event) use (&$captured): void {
				$captured = $event;
			});
		$this->logger->expects($this->once())
			->method('info')
			->with(
				'Filinq: dispatched SigningConcludedEvent',
				['signingRequestId' => 'req-9', 'sourceApp' => 'shillinq', 'status' => 'signed']
			);
		$this->logger->expects($this->never())->method('error');

		$this->emitter->emitIfDelegated(
			request: [
				'id' => 'req-9',
				'sourceApp' => 'shillinq',
				'signerIds' => ['alice', 'bob'],
				'externalReference' => 'INV-1',
				'correlationId' => 'corr-1',
			],
			status: 'signed',
			signedDocumentRef: 'file:42'
		);

		$this->assertInstanceOf(SigningConcludedEvent::class, $captured);
		$this->assertSame('req-9', $captured->getSigningRequestId());
		$this->assertSame('signed', $captured->getStatus());
		$this->assertSame('file:42', $captured->getSignedDocumentRef());
		$this->assertSame(['alice', 'bob'], $captured->getSigners());
		$this->assertSame('shillinq', $captured->getSourceApp());
		$this->assertSame('INV-1', $captured->getExternalReference());
		$this->assertSame('corr-1', $captured->getCorrelationId());
	}//end testADelegatedRequestDispatchesTheConclusion()

	/**
	 * A dispatch failure is logged as an error and never escapes.
	 *
	 * @return void
	 */
	public function testADispatchFailureIsLoggedNotThrown(): void {
		$this->dispatcher->method('dispatchTyped')->willThrowException(new \RuntimeException('listener broke'));
		$this->logger->expects($this->never())->method('info');
		$this->logger->expects($this->once())
			->method('error')
			->with(
				'Filinq: signing request concluded but SigningConcludedEvent dispatch failed',
				['sourceApp' => 'shillinq', 'status' => 'declined', 'exception' => 'listener broke']
			);

		$this->emitter->emitIfDelegated(request: ['id' => 'r3', 'sourceApp' => 'shillinq'], status: 'declined');
	}//end testADispatchFailureIsLoggedNotThrown()
}//end class
