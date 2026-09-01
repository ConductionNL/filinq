<?php

/**
 * Unit tests for SignerEventTranslator
 *
 * Verifies the scalar surface the translator exposes to SigningTaskListener:
 * position-enabled re-emission plus provider invocation, the approved /
 * rejected split on the rejecting flag, sequence-completed re-emission, and
 * the provider-resolution failure being logged rather than thrown.
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
 * @spec openspec/changes/migrate-signing-to-or-tasks/tasks.md#4-1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\Event\SignerChainCompletedEvent;
use OCA\Filinq\Event\SignerStepApprovedEvent;
use OCA\Filinq\Event\SignerStepPendingEvent;
use OCA\Filinq\Event\SignerStepRejectedEvent;
use OCA\Filinq\EventListener\SignerEventTranslator;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use OCA\Filinq\Service\Signing\SigningProviderInterface;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests for SignerEventTranslator.
 *
 * @covers \OCA\Filinq\EventListener\SignerEventTranslator
 *
 * @uses \OCA\Filinq\Event\SignerStepPendingEvent
 * @uses \OCA\Filinq\Event\SignerStepApprovedEvent
 * @uses \OCA\Filinq\Event\SignerStepRejectedEvent
 * @uses \OCA\Filinq\Event\SignerChainCompletedEvent
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
final class SignerEventTranslatorTest extends TestCase {

	/**
	 * Provider factory mock.
	 *
	 * @var SigningProviderFactory&MockObject
	 */
	private SigningProviderFactory $providerFactory;

	/**
	 * Dispatcher mock.
	 *
	 * @var IEventDispatcher&MockObject
	 */
	private IEventDispatcher $dispatcher;

	/**
	 * Logger mock.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * Translator under test.
	 *
	 * @var SignerEventTranslator
	 */
	private SignerEventTranslator $translator;

	/**
	 * Wire the translator with fresh mocks.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->providerFactory = $this->createMock(SigningProviderFactory::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->translator = new SignerEventTranslator(
			providerFactory: $this->providerFactory,
			dispatcher: $this->dispatcher,
			logger: $this->logger
		);

	}//end setUp()

	/**
	 * Position enabled: pending event carries the scalars verbatim and the
	 * active provider is resolved.
	 *
	 * @return void
	 */
	public function testOnPositionEnabledDispatchesPendingAndResolvesProvider(): void {
		$provider = $this->createMock(SigningProviderInterface::class);
		$provider->method('getIdentifier')->willReturn('native');
		$this->providerFactory->expects($this->once())
			->method('getActiveProvider')
			->willReturn($provider);

		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(
				$this->callback(
					function (Event $emitted): bool {
						$this->assertInstanceOf(SignerStepPendingEvent::class, $emitted);
						$this->assertSame('seq-1', $emitted->getSequenceUuid());
						$this->assertSame('task-1', $emitted->getTaskUuid());
						$this->assertSame(1, $emitted->getPosition());
						$this->assertSame('signers', $emitted->getRole());
						$this->assertSame('sign-req-1', $emitted->getObjectUuid());

						return true;
					}
				)
			);

		$this->translator->onPositionEnabled(
			sequenceUuid: 'seq-1',
			taskUuid: 'task-1',
			position: 1,
			role: 'signers',
			objectUuid: 'sign-req-1'
		);

	}//end testOnPositionEnabledDispatchesPendingAndResolvesProvider()

	/**
	 * A provider-resolution failure on position-enabled is logged, not
	 * thrown, and the pending event is still dispatched first.
	 *
	 * @return void
	 */
	public function testProviderResolutionFailureIsLoggedNotThrown(): void {
		$this->providerFactory->method('getActiveProvider')
			->willThrowException(new RuntimeException('no provider configured'));
		$this->logger->expects($this->once())->method('error');
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->isInstanceOf(SignerStepPendingEvent::class));

		$this->translator->onPositionEnabled(
			sequenceUuid: 'seq-1',
			taskUuid: 'task-1',
			position: 1,
			role: null,
			objectUuid: 'sign-req-1'
		);

	}//end testProviderResolutionFailureIsLoggedNotThrown()

	/**
	 * A non-rejecting decision dispatches the approved event.
	 *
	 * @return void
	 */
	public function testApprovingDecisionDispatchesApproved(): void {
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(
				$this->callback(
					function (Event $emitted): bool {
						$this->assertInstanceOf(SignerStepApprovedEvent::class, $emitted);
						$this->assertSame('alice', $emitted->getUserId());
						$this->assertSame('akkoord', $emitted->getComment());

						return true;
					}
				)
			);

		$this->translator->onTaskDecided(
			sequenceUuid: 'seq-1',
			taskUuid: 'task-1',
			position: 1,
			userId: 'alice',
			comment: 'akkoord',
			objectUuid: 'sign-req-1',
			isRejecting: false
		);

	}//end testApprovingDecisionDispatchesApproved()

	/**
	 * A rejecting decision dispatches the rejected event, never the
	 * approved one.
	 *
	 * @return void
	 */
	public function testRejectingDecisionDispatchesRejected(): void {
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(
				$this->callback(
					function (Event $emitted): bool {
						$this->assertInstanceOf(SignerStepRejectedEvent::class, $emitted);
						$this->assertSame('niet akkoord', $emitted->getComment());

						return true;
					}
				)
			);

		$this->translator->onTaskDecided(
			sequenceUuid: 'seq-1',
			taskUuid: 'task-1',
			position: 1,
			userId: 'alice',
			comment: 'niet akkoord',
			objectUuid: 'sign-req-1',
			isRejecting: true
		);

	}//end testRejectingDecisionDispatchesRejected()

	/**
	 * Sequence completion dispatches the chain-completed event verbatim.
	 *
	 * @return void
	 */
	public function testOnSequenceCompletedDispatchesChainCompleted(): void {
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(
				$this->callback(
					function (Event $emitted): bool {
						$this->assertInstanceOf(SignerChainCompletedEvent::class, $emitted);
						$this->assertSame('seq-1', $emitted->getSequenceUuid());
						$this->assertSame('task-9', $emitted->getFinalTaskUuid());
						$this->assertSame('bob', $emitted->getUserId());
						$this->assertSame('signed', $emitted->getStatusOnApprove());
						$this->assertSame('sign-req-1', $emitted->getObjectUuid());

						return true;
					}
				)
			);

		$this->translator->onSequenceCompleted(
			sequenceUuid: 'seq-1',
			finalTaskUuid: 'task-9',
			userId: 'bob',
			statusOnApprove: 'signed',
			objectUuid: 'sign-req-1'
		);

	}//end testOnSequenceCompletedDispatchesChainCompleted()
}//end class
