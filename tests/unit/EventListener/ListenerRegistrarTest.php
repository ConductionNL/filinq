<?php

/**
 * Unit tests for ListenerRegistrar.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\Event\DocumentGenerationRequestedEvent;
use OCA\Filinq\Event\DocumentSigningRequestedEvent;
use OCA\Filinq\EventListener\DocumentGenerationRequestedListener;
use OCA\Filinq\EventListener\DocumentSigningRequestedListener;
use OCA\Filinq\EventListener\DossierCheckedOnListener;
use OCA\Filinq\EventListener\IntegrationLeafRegistrar;
use OCA\Filinq\EventListener\ListenerRegistrar;
use OCA\Filinq\EventListener\SigningTaskListener;
use OCA\Filinq\Flow\FilinqFlowNodeListener;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * The top-level registrar delegates to every per-area registrar, in order.
 *
 * @covers \OCA\Filinq\EventListener\ListenerRegistrar
 * @uses   \OCA\Filinq\EventListener\ObjectEventRegistrar
 * @uses   \OCA\Filinq\EventListener\SigningEventRegistrar
 * @uses   \OCA\Filinq\EventListener\IntegrationLeafRegistrar
 * @uses   \OCA\Filinq\EventListener\DocumentGenerationRegistrar
 */
class ListenerRegistrarTest extends TestCase {

	/**
	 * The server container before the test.
	 *
	 * @var object|null
	 */
	private ?object $server;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->server = \OC::$server;
	}//end setUp()

	/**
	 * Restore the server container.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		\OC::$server = $this->server;
		parent::tearDown();
	}//end tearDown()

	/**
	 * Object, signing, leaf and generation listeners are registered in that order.
	 *
	 * @return void
	 */
	public function testRegisterDelegatesToEveryAreaInOrder(): void {
		$registered = [];
		$context = $this->createMock(IRegistrationContext::class);
		$context->method('registerEventListener')->willReturnCallback(
			static function (string $event, string $listener) use (&$registered): void {
				$registered[] = [$event, $listener];
			}
		);

		(new ListenerRegistrar())->register($context);

		self::assertCount(20, $registered);
		self::assertSame(['OCA\Filinq\Event\IntakeDocumentReceivedEvent', 'OCA\Filinq\EventListener\IntakeDocumentReceivedListener'], $registered[0]);
		self::assertSame(
			[
				['OCA\OpenRegister\Event\TaskTransitionedEvent', SigningTaskListener::class],
				['OCA\OpenRegister\Event\TaskTerminalEvent', SigningTaskListener::class],
				['OCA\OpenRegister\Event\TaskSequenceCompletedEvent', SigningTaskListener::class],
				[DocumentSigningRequestedEvent::class, DocumentSigningRequestedListener::class],
			],
			array_slice($registered, 9, 4)
		);
		self::assertSame(
			array_fill(0, 5, IntegrationLeafRegistrar::LEAF_EVENT),
			array_column(array_slice($registered, 13, 5), 0)
		);
		self::assertSame(
			[
				['OCA\OpenRegister\Service\Flow\RegisterFlowNodesEvent', FilinqFlowNodeListener::class],
				[DocumentGenerationRequestedEvent::class, DocumentGenerationRequestedListener::class],
			],
			array_slice($registered, 18)
		);
	}//end testRegisterDelegatesToEveryAreaInOrder()

	/**
	 * Boot hands the container to the object-event step, which subscribes the dossier
	 * listener and the document event translator.
	 *
	 * @return void
	 */
	public function testBootRunsTheObjectEventBootStep(): void {
		self::assertFalse(class_exists('OCA\OpenRegister\Event\ObjectEventSubscription'), 'This test needs the subscription class absent.');

		$dispatcher = $this->createMock(IEventDispatcher::class);
		$added = [];
		$dispatcher->expects(self::exactly(3))->method('addServiceListener')
			->willReturnCallback(static function (string $event, string $listener) use (&$added): void {
				$added[] = [$event, $listener];
			});
		$container = $this->createMock(ContainerInterface::class);
		$container->expects(self::once())->method('get')->with(IEventDispatcher::class)->willReturn($dispatcher);
		$server = $this->createMock(ContainerInterface::class);
		$server->method('get')->with(LoggerInterface::class)->willReturn($this->createMock(LoggerInterface::class));
		\OC::$server = $server;

		(new ListenerRegistrar())->boot($container, 'filinq');

		$translator = 'OCA\\Filinq\\EventListener\\DocumentEventTranslator';
		self::assertSame(
			[
				[ObjectUpdatedEvent::class, DossierCheckedOnListener::class],
				['OCA\\OpenRegister\\Event\\ObjectTransitionedEvent', $translator],
				['OCA\\OpenRegister\\Event\\ObjectCreatedEvent', $translator],
			],
			$added
		);
	}//end testBootRunsTheObjectEventBootStep()
}//end class
