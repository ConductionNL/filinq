<?php
/**
 * Trace tests for the EntityRelation decision listener: what it logs and what it asks for.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://conduction.nl
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

require_once __DIR__ . '/FakeEntityRelationDecisionUpdatedEvent.php';

use OCA\Filinq\EventListener\EntityRelationDecisionListener;
use OCA\Filinq\Service\ConsentCrudService;
use OCA\Filinq\Service\ConsentService;
use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;

/**
 * Every path through the listener, recorded as the log lines it writes and the
 * consent request it makes.
 *
 * @covers \OCA\Filinq\EventListener\EntityRelationDecisionListener
 *
 * @spec openspec/specs/consent-management/spec.md
 */
class EntityRelationDecisionListenerTraceTest extends TestCase {

	private const CONFIG = ['register' => 'filinq', 'schema' => 'publicationConsent'];

	private const DECLINE_CONFIG = 'Publication consent register/schema not configured; the decision to publish '
		. 'an entity unredacted was recorded in OpenRegister but no consent request was created.';

	private const DECLINE_FILE = 'EntityRelation {relation} activated skipAnonymization but carries no fileId, '
		. 'so there is no document to attach a consent record to.';

	private const DECLINE_ENTITY = 'EntityRelation {relation} activated skipAnonymization but its entity {entity} '
		. 'could not be resolved; no consent request created.';

	/**
	 * The scenarios: event, configuration, installed apps, mapper behaviour, and the expected trace.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: array<int, array<int, mixed>>, 2: array<int, array<int, mixed>>}>
	 */
	public static function scenarios(): array {
		$entity = ['type' => 'PERSON', 'value' => 'Anneke Jansen', 'uuid' => 'uuid-1'];
		$create = [['42', 'PERSON', 'Anneke Jansen', 'filinq', 'publicationConsent', ['entityKey' => 'uuid-1']]];
		return [
			'unrelated event' => [
				['plain' => true, 'fileId' => 42, 'entityId' => 7, 'entity' => $entity],
				[],
				[],
			],
			'not activated' => [
				['activated' => false, 'config' => self::CONFIG, 'fileId' => 42, 'entityId' => 7, 'entity' => $entity],
				[],
				[],
			],
			'unconfigured' => [
				['config' => null, 'fileId' => 42, 'entityId' => 7, 'entity' => $entity],
				[['warning', self::DECLINE_CONFIG, []]],
				[],
			],
			'no file id' => [
				['fileId' => null, 'relationId' => null, 'entityId' => 7, 'entity' => $entity],
				[['warning', self::DECLINE_FILE, ['relation' => 'unknown']]],
				[],
			],
			'file id zero still creates' => [
				['fileId' => 0, 'entityId' => 7, 'entity' => $entity],
				[],
				[['0', 'PERSON', 'Anneke Jansen', 'filinq', 'publicationConsent', ['entityKey' => 'uuid-1']]],
			],
			'no entity id' => [
				['fileId' => 42, 'entityId' => null, 'entity' => $entity],
				[['warning', self::DECLINE_ENTITY, ['relation' => '5', 'entity' => 'unknown']]],
				[],
			],
			'openregister absent' => [
				['fileId' => 42, 'entityId' => 7, 'entity' => $entity, 'apps' => ['filinq']],
				[['warning', self::DECLINE_ENTITY, ['relation' => '5', 'entity' => '7']]],
				[],
			],
			'lookup fails' => [
				['fileId' => 42, 'entityId' => 7, 'entity' => 'throw'],
				[
					['debug', 'GdprEntity {entity} lookup failed: {message}', ['entity' => 7, 'message' => 'no such row']],
					['warning', self::DECLINE_ENTITY, ['relation' => '5', 'entity' => '7']],
				],
				[],
			],
			'entity without fields' => [
				['fileId' => 42, 'entityId' => 7, 'entity' => ['type' => null, 'value' => null, 'uuid' => null]],
				[],
				[['42', '', '', 'filinq', 'publicationConsent', ['entityKey' => '']]],
			],
			'creates' => [
				['fileId' => 42, 'entityId' => 7, 'entity' => $entity],
				[],
				$create,
			],
			'create fails' => [
				['fileId' => 42, 'entityId' => 7, 'entity' => $entity, 'createFails' => true],
				[['error', 'Failed to create a consent request from an EntityRelation decision: {message}', ['message' => 'register down']]],
				$create,
			],
		];
	}//end scenarios()

	/**
	 * The listener's log lines and consent requests match the recorded trace.
	 *
	 * @param array<string, mixed>          $case    The scenario.
	 * @param array<int, array<int, mixed>> $logs    The expected log lines: level, message, context.
	 * @param array<int, array<int, mixed>> $creates The expected createConsentRequest arguments.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testTrace(array $case, array $logs, array $creates): void {
		$logger = new class extends AbstractLogger {
			/**
			 * @var array<int, array<int, mixed>>
			 */
			public array $lines = [];

			/**
			 * Record one line, without the exception object.
			 *
			 * @param mixed             $level   The level.
			 * @param string|Stringable $message The message.
			 * @param array<mixed>      $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|Stringable $message, array $context = []): void {
				unset($context['exception']);
				$this->lines[] = [$level, (string)$message, $context];
			}
		};

		$calls = [];
		$consent = $this->createMock(ConsentService::class);
		$consent->method('createConsentRequest')->willReturnCallback(
			function (...$args) use (&$calls, $case): array {
				$calls[] = array_values($args);
				if (($case['createFails'] ?? false) === true) {
					throw new RuntimeException('register down');
				}

				return ['id' => 'consent-1', 'wasUpdated' => false];
			}
		);

		$crud = $this->createMock(ConsentCrudService::class);
		$crud->method('getConsentConfig')->willReturn(array_key_exists('config', $case) === true ? $case['config'] : self::CONFIG);

		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn($case['apps'] ?? ['openregister', 'filinq']);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			function (string $name) use ($case): object {
				$this->assertSame('OCA\OpenRegister\Db\GdprEntityMapper', $name);
				return $this->mapper($case['entity']);
			}
		);

		$listener = new EntityRelationDecisionListener($consent, $crud, $apps, $container, $logger);
		$relation = $this->relation(
			[
				'fileId' => $case['fileId'],
				'entityId' => $case['entityId'],
				'id' => array_key_exists('relationId', $case) === true ? $case['relationId'] : 5,
			]
		);
		$event = new FakeEntityRelationDecisionUpdatedEvent($case['activated'] ?? true, $relation);
		if (($case['plain'] ?? false) === true) {
			$event = new Event();
		}

		$listener->handle($event);

		$this->assertSame($logs, $logger->lines);
		$this->assertSame($creates, $calls);
	}//end testTrace()

	/**
	 * A relation double.
	 *
	 * @param array{fileId: ?int, entityId: ?int, id: ?int} $ids The relation's ids.
	 *
	 * @return object
	 */
	private function relation(array $ids): object {
		return new class($ids) {
			/**
			 * @param array{fileId: ?int, entityId: ?int, id: ?int} $ids The ids.
			 */
			public function __construct(
				private array $ids,
			) {
			}

			public function getFileId(): ?int {
				return $this->ids['fileId'];
			}

			public function getEntityId(): ?int {
				return $this->ids['entityId'];
			}

			public function getId(): ?int {
				return $this->ids['id'];
			}
		};
	}//end relation()

	/**
	 * A GdprEntity mapper double returning the given entity, or throwing.
	 *
	 * @param array<string, ?string>|string $entity The entity's fields, or 'throw'.
	 *
	 * @return object
	 */
	private function mapper(array|string $entity): object {
		return new class($entity) {
			/**
			 * @param array<string, ?string>|string $entity The entity.
			 */
			public function __construct(
				private array|string $entity,
			) {
			}

			public function find(int $id): object {
				if ($this->entity === 'throw') {
					throw new RuntimeException('no such row');
				}

				return new class($this->entity) {
					/**
					 * @param array<string, ?string> $fields The fields.
					 */
					public function __construct(
						private array $fields,
					) {
					}

					public function getType(): ?string {
						return $this->fields['type'];
					}

					public function getValue(): ?string {
						return $this->fields['value'];
					}

					public function getUuid(): ?string {
						return $this->fields['uuid'];
					}
				};
			}
		};
	}//end mapper()
}//end class
