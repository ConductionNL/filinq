<?php

/**
 * Unit tests for ObjectEventRegistrar.
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

use OCA\Filinq\EventListener\DocumentRegistrationWriteGuard;
use OCA\Filinq\EventListener\DossierCheckedOnListener;
use OCA\Filinq\EventListener\FilinqEventListener;
use OCA\Filinq\EventListener\ObjectEventRegistrar;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Which listeners the object-event registrar wires, at register and at boot.
 *
 * @covers \OCA\Filinq\EventListener\ObjectEventRegistrar
 */
class ObjectEventRegistrarTest extends TestCase {

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
	 * Every listener is registered for its event, in order, with the optional peers named by string.
	 *
	 * @return void
	 */
	public function testRegisterWiresEveryListener(): void {
		$registered = [];
		$context = $this->createMock(IRegistrationContext::class);
		$context->expects(self::exactly(9))->method('registerEventListener')->willReturnCallback(
			static function (string $event, string $listener) use (&$registered): void {
				$registered[] = [$event, $listener];
			}
		);

		(new ObjectEventRegistrar())->register($context);

		self::assertSame(
			[
				['OCA\Filinq\Event\IntakeDocumentReceivedEvent', 'OCA\Filinq\EventListener\IntakeDocumentReceivedListener'],
				[ObjectCreatedEvent::class, FilinqEventListener::class],
				[ObjectUpdatedEvent::class, FilinqEventListener::class],
				['OCA\OpenRegister\Event\ObjectUpdatingEvent', DocumentRegistrationWriteGuard::class],
				['OCA\OpenRegister\Event\ObjectCreatingEvent', 'OCA\Filinq\EventListener\ContractNoticeDeadlineListener'],
				['OCA\OpenRegister\Event\ObjectUpdatingEvent', 'OCA\Filinq\EventListener\ContractNoticeDeadlineListener'],
				[ObjectDeletedEvent::class, FilinqEventListener::class],
				[ObjectDeletedEvent::class, 'OCA\Filinq\EventListener\PseudonymMapLinkDeletedListener'],
				['OCA\OpenRegister\Event\EntityRelationDecisionUpdatedEvent', 'OCA\Filinq\EventListener\EntityRelationDecisionListener'],
			],
			$registered
		);
	}//end testRegisterWiresEveryListener()

	/**
	 * Without OpenRegister's filtered subscription, boot falls back to an unfiltered
	 * listener and says so loudly.
	 *
	 * @return void
	 */
	public function testBootFallsBackToAnUnfilteredListenerAndWarns(): void {
		self::assertFalse(class_exists('OCA\OpenRegister\Event\ObjectEventSubscription'), 'This test needs the subscription class absent.');

		$dispatcher = $this->createMock(IEventDispatcher::class);
		$dispatcher->expects(self::once())->method('addServiceListener')
			->with(ObjectUpdatedEvent::class, DossierCheckedOnListener::class);
		$container = $this->createMock(ContainerInterface::class);
		$container->expects(self::once())->method('get')->with(IEventDispatcher::class)->willReturn($dispatcher);

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects(self::once())->method('warning')->with(
			'OpenRegister ObjectEventSubscription unavailable: ' . DossierCheckedOnListener::class
			. ' fell back to an UNFILTERED registration for ' . ObjectUpdatedEvent::class
			. ' and will be invoked on every object write instance-wide.',
			['app' => 'filinq']
		);
		$server = $this->createMock(ContainerInterface::class);
		$server->method('get')->with(LoggerInterface::class)->willReturn($logger);
		\OC::$server = $server;

		(new ObjectEventRegistrar())->boot($container, 'filinq');
	}//end testBootFallsBackToAnUnfilteredListenerAndWarns()

	/**
	 * With OpenRegister's filtered subscription, boot subscribes the dossier listener
	 * narrowed to the filinq register and dossier schema, without the fallback.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function testBootSubscribesFilteredWhenOpenRegisterOffersIt(): void {
		require_once __DIR__ . '/../../fixtures/openregister/ObjectEventSubscription.php';

		$dispatcher = $this->createMock(IEventDispatcher::class);
		$dispatcher->expects(self::never())->method('addServiceListener');
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->with(IEventDispatcher::class)->willReturn($dispatcher);
		\OC::$server = null;

		(new ObjectEventRegistrar())->boot($container, 'filinq');

		self::assertSame(
			[[$dispatcher, ObjectUpdatedEvent::class, DossierCheckedOnListener::class, ['filinq'], ['dossier']]],
			\OCA\OpenRegister\Event\ObjectEventSubscription::$calls
		);
	}//end testBootSubscribesFilteredWhenOpenRegisterOffersIt()
}//end class
