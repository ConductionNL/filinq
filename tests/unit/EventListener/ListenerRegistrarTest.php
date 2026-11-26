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
use OCA\Filinq\EventListener\DocumentStampRequestedListener;
use OCA\Filinq\Event\DocumentStampRequestedEvent;
use OCA\Filinq\EventListener\DocumentSigningRequestedListener;
use OCA\Filinq\EventListener\DossierCheckedOnListener;
use OCA\Filinq\EventListener\IntegrationLeafRegistrar;
use OCA\Filinq\EventListener\ListenerRegistrar;
use OCA\Filinq\EventListener\PdfUaAttachListener;
use OCA\Filinq\EventListener\SigningTaskListener;
use OCA\Filinq\Flow\FilinqFlowNodeListener;
use OCA\Filinq\EventListener\VendorRenderedListener;
use OCA\Filinq\EventListener\WatchedFileArrivedListener;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use OCA\Filinq\Flow\RegisterFlowOperationsListener;
use OCP\WorkflowEngine\Events\LoadSettingsScriptsEvent;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;
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

		self::assertCount(expectedCount: 39, haystack: $registered);
		self::assertSame(['OCA\Filinq\Event\IntakeDocumentReceivedEvent', 'OCA\Filinq\EventListener\IntakeDocumentReceivedListener'], $registered[0]);
		self::assertSame(
			[
				['OCA\OpenRegister\Event\TaskTransitionedEvent', SigningTaskListener::class],
				['OCA\OpenRegister\Event\TaskTerminalEvent', SigningTaskListener::class],
				['OCA\OpenRegister\Event\TaskSequenceCompletedEvent', SigningTaskListener::class],
				[DocumentSigningRequestedEvent::class, DocumentSigningRequestedListener::class],
			],
			array_slice($registered, 13, 4)
		);
		self::assertSame(
			array_fill(0, 7, IntegrationLeafRegistrar::LEAF_EVENT),
			array_column(array_slice($registered, 17, 7), 0)
		);
		self::assertSame(
			[
				['OCA\OpenRegister\Service\Flow\RegisterFlowNodesEvent', FilinqFlowNodeListener::class],
				[DocumentGenerationRequestedEvent::class, DocumentGenerationRequestedListener::class],
				[DocumentStampRequestedEvent::class, DocumentStampRequestedListener::class],
				[RegisterOperationsEvent::class, RegisterFlowOperationsListener::class],
				[LoadSettingsScriptsEvent::class, RegisterFlowOperationsListener::class],
				['OCA\Integriq\Event\DocumentRenderedEvent', VendorRenderedListener::class],
				['OCA\Integriq\Event\WatchedFileArrivedEvent', WatchedFileArrivedListener::class],
			],
			array_slice($registered, 24, 7)
		);
		self::assertSame(
			[
				['OCP\Files\Events\Node\NodeCreatedEvent', PdfUaAttachListener::class],
				['OCP\Files\Events\Node\NodeWrittenEvent', PdfUaAttachListener::class],
			],
			array_slice($registered, 31, 2)
		);
		self::assertSame(
			[
				['OCP\Files\Events\Node\NodeWrittenEvent', 'OCA\Filinq\EventListener\DocumentFileActionListener'],
				['OCP\Files\Events\Node\BeforeNodeDeletedEvent', 'OCA\Filinq\EventListener\DocumentFileActionListener'],
				['OCP\Share\Events\ShareCreatedEvent', 'OCA\Filinq\EventListener\DocumentFileActionListener'],
				['OCA\Filinq\Event\Webhook\DocumentSignedEvent', 'OCA\Filinq\EventListener\DocumentFileActionListener'],
				['OCP\Files\Events\BeforeDirectFileDownloadEvent', 'OCA\Filinq\EventListener\DocumentDownloadedListener'],
				['OCA\Filinq\Event\Webhook\DocumentAnonymisedEvent', 'OCA\Filinq\EventListener\BridgeResultListener'],
			],
			array_slice($registered, 33)
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
		$container = $this->createMock(originalClassName: ContainerInterface::class);
		$container->expects(self::exactly(count: 2))->method('get')->willReturnMap(
			[[IEventDispatcher::class, $dispatcher], [LoggerInterface::class, $this->createMock(originalClassName: LoggerInterface::class)]]
		);

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
