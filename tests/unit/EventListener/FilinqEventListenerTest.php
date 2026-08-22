<?php

/**
 * Unit tests for FilinqEventListener
 *
 * The listener is a service-locator shim: it resolves its collaborators from
 * the server container, logs the dispatch, routes the event to the matching
 * FilinqEventHandler method, and then runs the validation fallback. These
 * tests drive it through a fake container and assert the routing by observing
 * the effect on the injected PolicyRetroactiveService.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\FilinqEventListener;
use OCA\Filinq\Service\MetadataService;
use OCA\Filinq\Service\PolicyRetroactiveService;
use OCA\Filinq\Service\SettingsService;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for FilinqEventListener
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FilinqEventListenerTest extends TestCase {

	/**
	 * The listener under test.
	 *
	 * @var FilinqEventListener
	 */
	private FilinqEventListener $listener;

	/**
	 * Mock logger resolved from the fake container.
	 *
	 * @var MockObject&LoggerInterface
	 */
	private MockObject $logger;

	/**
	 * Mock metadata service resolved from the fake container.
	 *
	 * @var MockObject&MetadataService
	 */
	private MockObject $metadataService;

	/**
	 * Mock settings service resolved from the fake container.
	 *
	 * @var MockObject&SettingsService
	 */
	private MockObject $settingsService;

	/**
	 * Mock retroactive policy applicator — the observable routing edge.
	 *
	 * @var MockObject&PolicyRetroactiveService
	 */
	private MockObject $retroactive;

	/**
	 * The previous \OC::$server value, restored in tearDown().
	 *
	 * @var object|null
	 */
	private ?object $previousServer = null;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(originalClassName: LoggerInterface::class);
		$this->metadataService = $this->createMock(originalClassName: MetadataService::class);
		$this->settingsService = $this->createMock(originalClassName: SettingsService::class);
		$this->retroactive = $this->createMock(originalClassName: PolicyRetroactiveService::class);

		// Keep the real EnrichmentRunner a cheap no-op: with every toggle off
		// it logs and returns before touching the metadata service.
		$this->settingsService->method('getFeatureToggles')->willReturn(
			[
				'enable_language_detection' => false,
				'enable_keyword_extraction' => false,
				'enable_topic_classification' => false,
			]
		);

		$this->previousServer = \OC::$server;
		$this->installContainer();

		$this->listener = new FilinqEventListener();

	}//end setUp()

	/**
	 * Restore the global service locator so tests stay independent.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		\OC::$server = $this->previousServer;

		parent::tearDown();

	}//end tearDown()

	/**
	 * Install a fake service locator over this test's mocks.
	 *
	 * Anything the listener does not itself resolve — notably the services the
	 * ValidationRunner fallback asks for — throws, which is exactly what an
	 * instance without those optional services does at runtime.
	 *
	 * @return void
	 */
	private function installContainer(): void {
		$services = [
			LoggerInterface::class => $this->logger,
			MetadataService::class => $this->metadataService,
			SettingsService::class => $this->settingsService,
			PolicyRetroactiveService::class => $this->retroactive,
		];

		\OC::$server = new class ($services) {

			/**
			 * Constructor.
			 *
			 * @param array<string, object> $services The resolvable services.
			 */
			public function __construct(private readonly array $services) {
			}

			/**
			 * Resolve a service.
			 *
			 * @param string $id The requested class name.
			 *
			 * @return object
			 */
			public function get(string $id): object {
				if (isset($this->services[$id]) === false) {
					throw new \Exception('Service not registered: ' . $id);
				}

				return $this->services[$id];
			}
		};

	}//end installContainer()

	/**
	 * Install a fake service locator whose every lookup fails.
	 *
	 * @return void
	 */
	private function installFailingContainer(): void {
		\OC::$server = new class {

			/**
			 * Resolve a service — always fails.
			 *
			 * @param string $id The requested class name.
			 *
			 * @return object
			 */
			public function get(string $id): object {
				throw new \Exception('Container unavailable for ' . $id);
			}
		};

	}//end installFailingContainer()

	/**
	 * Build an ObjectEntity carrying the supplied payload.
	 *
	 * @param array<string, mixed> $payload The object payload.
	 *
	 * @return ObjectEntity
	 */
	private function makeObject(array $payload): ObjectEntity {
		$object = new ObjectEntity();
		$object->setUuid('uuid-1');
		$object->setRegister('register-1');
		$object->setSchema('schema-1');
		$object->setObject($payload);

		return $object;
	}//end makeObject()

	/**
	 * A canonical publicationProhibition payload.
	 *
	 * @return array<string, mixed>
	 */
	private function prohibitionPayload(): array {
		return [
			'matchRules' => [['field' => 'name', 'value' => 'x']],
			'reason' => 'Privacy',
		];
	}//end prohibitionPayload()

	/**
	 * Test that the listener implements the Nextcloud listener contract
	 *
	 * @return void
	 */
	public function testListenerImplementsTheEventListenerContract(): void {
		$this->assertInstanceOf(expected: IEventListener::class, actual: $this->listener);

	}//end testListenerImplementsTheEventListenerContract()

	/**
	 * Test that every dispatch is logged with the concrete event class
	 *
	 * @return void
	 */
	public function testHandleLogsTheEventTypeBeingProcessed(): void {
		$seen = [];
		$this->logger->method('info')->willReturnCallback(
			function (string|\Stringable $message, array $context = []) use (&$seen): void {
				$seen[(string)$message] = $context;
			}
		);

		$this->listener->handle(new ObjectDeletedEvent($this->makeObject([])));

		$this->assertArrayHasKey(key: 'Filinq: Processing event', array: $seen);
		$this->assertSame(
			expected: ObjectDeletedEvent::class,
			actual: $seen['Filinq: Processing event']['eventType']
		);

	}//end testHandleLogsTheEventTypeBeingProcessed()

	/**
	 * Test that a creation event is routed to the created handler
	 *
	 * Proven through the retroactive edge: only handleObjectCreated() dispatches
	 * a prohibition mutation with reason 'created'.
	 *
	 * @return void
	 */
	public function testHandleRoutesObjectCreatedEvent(): void {
		$this->retroactive->expects($this->once())
			->method('applyProhibitionMutation')
			->with($this->identicalTo($this->prohibitionPayload()))
			->willReturn(0);
		$this->retroactive->expects($this->never())->method('applyRuleRemoval');

		$this->listener->handle(new ObjectCreatedEvent($this->makeObject($this->prohibitionPayload())));

	}//end testHandleRoutesObjectCreatedEvent()

	/**
	 * Test that an update event is routed to the updated handler
	 *
	 * @return void
	 */
	public function testHandleRoutesObjectUpdatedEvent(): void {
		$this->retroactive->expects($this->once())
			->method('applyProhibitionMutation')
			->willReturn(0);
		$this->retroactive->expects($this->never())->method('applyRuleRemoval');

		$this->listener->handle(
			new ObjectUpdatedEvent(
				$this->makeObject($this->prohibitionPayload()),
				$this->makeObject($this->prohibitionPayload())
			)
		);

	}//end testHandleRoutesObjectUpdatedEvent()

	/**
	 * Test that a deletion event is routed to the deleted handler
	 *
	 * Deletion is the only path that reaches applyRuleRemoval().
	 *
	 * @return void
	 */
	public function testHandleRoutesObjectDeletedEvent(): void {
		$this->retroactive->expects($this->once())->method('applyRuleRemoval');
		$this->retroactive->expects($this->never())->method('applyProhibitionMutation');

		$this->listener->handle(new ObjectDeletedEvent($this->makeObject($this->prohibitionPayload())));

	}//end testHandleRoutesObjectDeletedEvent()

	/**
	 * Test that an unrelated event is a logged no-op, not an error
	 *
	 * @return void
	 */
	public function testHandleIgnoresAnUnrelatedEvent(): void {
		$this->logger->expects($this->once())
			->method('debug')
			->with(
				$this->stringContains('Ignoring unhandled event type'),
				$this->anything()
			);
		$this->retroactive->expects($this->never())->method('applyProhibitionMutation');
		$this->retroactive->expects($this->never())->method('applyStandingConsentMutation');
		$this->retroactive->expects($this->never())->method('applyRuleRemoval');

		$this->listener->handle($this->createMock(originalClassName: Event::class));

	}//end testHandleIgnoresAnUnrelatedEvent()

	/**
	 * Test that the unhandled-event debug line names the ignored event class
	 *
	 * @return void
	 */
	public function testUnhandledEventDebugLineNamesTheEventClass(): void {
		$event = $this->createMock(originalClassName: Event::class);

		$context = null;
		$this->logger->method('debug')->willReturnCallback(
			function (string|\Stringable $message, array $payload = []) use (&$context): void {
				$context = $payload;
			}
		);

		$this->listener->handle($event);

		$this->assertIsArray(actual: $context);
		$this->assertSame(expected: $event::class, actual: $context['eventType']);

	}//end testUnhandledEventDebugLineNamesTheEventClass()

	/**
	 * Test that an event carrying no object is handled without throwing
	 *
	 * @return void
	 */
	public function testHandleToleratesAnEventWithoutAnObject(): void {
		$this->logger->expects($this->atLeastOnce())
			->method('warning')
			->with($this->stringContains('received with null object'));

		$this->listener->handle(new ObjectCreatedEvent(null));

	}//end testHandleToleratesAnEventWithoutAnObject()

	/**
	 * Test that a failed service resolution is caught and logged
	 *
	 * MetadataService is resolved second, so a container that only fails on it
	 * still yields a usable logger for the error report.
	 *
	 * @return void
	 */
	public function testHandleLogsAnErrorWhenAServiceCannotBeResolved(): void {
		$logger = $this->logger;
		\OC::$server = new class ($logger) {

			/**
			 * Constructor.
			 *
			 * @param LoggerInterface $logger The only resolvable service.
			 */
			public function __construct(private readonly LoggerInterface $logger) {
			}

			/**
			 * Resolve a service — only the logger is available.
			 *
			 * @param string $id The requested class name.
			 *
			 * @return object
			 */
			public function get(string $id): object {
				if ($id === LoggerInterface::class) {
					return $this->logger;
				}

				throw new \Exception('Service not registered: ' . $id);
			}
		};

		$captured = [];
		$this->logger->expects($this->once())
			->method('error')
			->willReturnCallback(
				function (string|\Stringable $message, array $context = []) use (&$captured): void {
					$captured = [(string)$message, $context];
				}
			);

		$this->listener->handle(new ObjectCreatedEvent($this->makeObject([])));

		$this->assertSame(expected: 'Filinq: Error in event handler', actual: $captured[0]);
		$this->assertSame(expected: ObjectCreatedEvent::class, actual: $captured[1]['eventType']);
		$this->assertStringContainsString(
			needle: MetadataService::class,
			haystack: $captured[1]['exception']
		);

	}//end testHandleLogsAnErrorWhenAServiceCannotBeResolved()

	/**
	 * Test that a wholly unavailable container fails silently rather than throwing
	 *
	 * When even the logger cannot be resolved, logHandlerError() swallows the
	 * secondary failure: an event listener must never propagate out of an
	 * unrelated app's object save.
	 *
	 * @return void
	 */
	public function testHandleSwallowsFailureWhenEvenTheLoggerIsUnavailable(): void {
		$this->installFailingContainer();

		$this->listener->handle(new ObjectCreatedEvent($this->makeObject([])));

		$this->addToAssertionCount(count: 1);

	}//end testHandleSwallowsFailureWhenEvenTheLoggerIsUnavailable()

	/**
	 * Test that the validation fallback runs after the handler and cannot break it
	 *
	 * DocumentValidationService is deliberately absent from the fake container,
	 * so the fallback's lookup fails; the listener must still have completed
	 * its routing and must report the skip at debug level.
	 *
	 * @return void
	 */
	public function testValidationFallbackFailureDoesNotBreakRouting(): void {
		$this->retroactive->expects($this->once())
			->method('applyProhibitionMutation')
			->willReturn(0);

		$messages = [];
		$this->logger->method('debug')->willReturnCallback(
			function (string|\Stringable $message, array $context = []) use (&$messages): void {
				$messages[] = (string)$message;
			}
		);

		$this->listener->handle(new ObjectCreatedEvent($this->makeObject($this->prohibitionPayload())));

		$this->assertNotEmpty(actual: array_filter(
			$messages,
			static fn (string $message): bool => str_contains($message, 'validation fallback skipped')
		));

	}//end testValidationFallbackFailureDoesNotBreakRouting()
}//end class
