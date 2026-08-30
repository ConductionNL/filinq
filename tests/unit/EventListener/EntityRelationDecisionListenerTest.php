<?php
/**
 * Tests for the EntityRelation decision listener.
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

// REQUIRED EXPLICITLY, not autoloaded. This file's `class_alias` must run
// BEFORE the listener's `is_a($event, 'OCA\OpenRegister\Event\...')` check is
// reached, and `tests/` is not on the PSR-4 map, so leaving it to the
// autoloader would mean the alias never executes and every case fails on a
// class that "does not exist".
require_once __DIR__ . '/FakeEntityRelationDecisionUpdatedEvent.php';

use OCA\Filinq\EventListener\EntityRelationDecisionListener;
use OCA\Filinq\Service\ConsentCrudService;
use OCA\Filinq\Service\ConsentService;
use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * THE THREE NEGATIVE SCENARIOS NEED A POSITIVE CONTROL, and that is the whole
 * point of this file's shape.
 *
 * "A bases-only change does not create a consent record" and "a reversal does
 * not create a consent record" were both SATISFIED before this listener
 * existed — by nothing happening at all. A test suite that only asserted the
 * negatives would have passed against an app with no listener, which is exactly
 * the state ConductionNL/filinq#805 describes.
 *
 * So `testSkipActivationCreatesAConsentRequest` is not merely one more case: it
 * is the control that gives the other three meaning. If it ever fails, the
 * negatives below stop being evidence of anything.
 *
 * @spec openspec/specs/consent-management/spec.md
 */
class EntityRelationDecisionListenerTest extends TestCase {

	private ConsentService $consentService;

	private ConsentCrudService $consentCrud;

	private IAppManager $appManager;

	private ContainerInterface $container;

	private EntityRelationDecisionListener $listener;

	/**
	 * Build the listener with all collaborators mocked.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->consentService = $this->createMock(ConsentService::class);
		$this->consentCrud = $this->createMock(ConsentCrudService::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->container = $this->createMock(ContainerInterface::class);

		$this->listener = new EntityRelationDecisionListener(
			$this->consentService,
			$this->consentCrud,
			$this->appManager,
			$this->container,
			$this->createMock(LoggerInterface::class)
		);
	}//end setUp()

	/**
	 * A stand-in for OpenRegister's event, matched by class name.
	 *
	 * The listener checks `is_a($event, '...EntityRelationDecisionUpdatedEvent')`
	 * by NAME rather than `instanceof`, because OpenRegister is an optional peer
	 * and an `instanceof` against an absent class is a fatal. That means a test
	 * double must genuinely carry that class name — hence `class_alias` onto a
	 * local fake rather than a PHPUnit mock of a class this suite cannot load.
	 *
	 * @param bool  $activated What `isSkipAnonymizationActivated()` returns.
	 * @param mixed $relation  The relation the event carries.
	 *
	 * @return Event
	 */
	private function makeEvent(bool $activated, mixed $relation): Event {
		return new FakeEntityRelationDecisionUpdatedEvent($activated, $relation);
	}//end makeEvent()

	/**
	 * A relation double exposing only what the listener reads.
	 *
	 * @param int|null $fileId   The document the relation sits on.
	 * @param int|null $entityId The entity it points at.
	 *
	 * @return object
	 */
	private function makeRelation(?int $fileId, ?int $entityId): object {
		return new class($fileId, $entityId) {
			public function __construct(private ?int $fileId, private ?int $entityId) {
			}

			public function getFileId(): ?int {
				return $this->fileId;
			}

			public function getEntityId(): ?int {
				return $this->entityId;
			}

			public function getId(): ?int {
				return 1;
			}
		};
	}//end makeRelation()

	/**
	 * Wire the container to return an entity mapper yielding this entity.
	 *
	 * @param string $type  The entity type.
	 * @param string $value The entity text.
	 * @param string $uuid  The entity uuid, used as the idempotency key.
	 *
	 * @return void
	 */
	private function withEntity(string $type, string $value, string $uuid): void {
		$entity = new class($type, $value, $uuid) {
			public function __construct(
				private string $type,
				private string $value,
				private string $uuid
			) {
			}

			public function getType(): string {
				return $this->type;
			}

			public function getValue(): string {
				return $this->value;
			}

			public function getUuid(): string {
				return $this->uuid;
			}
		};

		$mapper = new class($entity) {
			public function __construct(private object $entity) {
			}

			public function find(int $id): object {
				return $this->entity;
			}
		};

		$this->appManager->method('getInstalledApps')->willReturn(['openregister', 'filinq']);
		$this->container->method('get')->willReturn($mapper);
	}//end withEntity()

	/**
	 * THE POSITIVE CONTROL. Activation creates a consent request.
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 *
	 * @return void
	 */
	public function testSkipActivationCreatesAConsentRequest(): void {
		$this->consentCrud->method('getConsentConfig')
			->willReturn(['register' => 'filinq', 'schema' => 'publicationConsent']);
		$this->withEntity('PERSON', 'Anneke Jansen', 'entity-uuid-1');

		$this->consentService->expects($this->once())
			->method('createConsentRequest')
			->with(
				'42',
				'PERSON',
				'Anneke Jansen',
				'filinq',
				'publicationConsent',
				$this->callback(
					static function (array $extra): bool {
						// The idempotency key is the point: without it a second
						// decision on the same entity duplicates the record and
						// strands the first one's workflow state.
						return ($extra['entityKey'] ?? null) === 'entity-uuid-1';
					}
				)
			)
			->willReturn(['id' => 'consent-1', 'wasUpdated' => false]);

		$this->listener->handle($this->makeEvent(true, $this->makeRelation(42, 7)));
	}//end testSkipActivationCreatesAConsentRequest()

	/**
	 * A bases-only edit leaves skipAnonymization out of the diff entirely.
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 *
	 * @return void
	 */
	public function testBasesOnlyChangeCreatesNothing(): void {
		$this->consentService->expects($this->never())->method('createConsentRequest');
		$this->listener->handle($this->makeEvent(false, $this->makeRelation(42, 7)));
	}//end testBasesOnlyChangeCreatesNothing()

	/**
	 * A reversal matches the field but not the direction.
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 *
	 * @return void
	 */
	public function testReversalCreatesNothing(): void {
		$this->consentService->expects($this->never())->method('createConsentRequest');
		$this->listener->handle($this->makeEvent(false, $this->makeRelation(42, 7)));
	}//end testReversalCreatesNothing()

	/**
	 * An unrelated event is ignored without touching anything.
	 *
	 * @spec exclude The name-based type check is a defensive guard for the case
	 *  where OpenRegister is absent; no spec scenario describes dispatching an
	 *  unrelated event at this listener.
	 *
	 * @return void
	 */
	public function testAnUnrelatedEventIsIgnored(): void {
		$this->consentService->expects($this->never())->method('createConsentRequest');
		$this->listener->handle(new Event());
	}//end testAnUnrelatedEventIsIgnored()

	/**
	 * With no consent register configured the listener declines rather than throws.
	 *
	 * @spec exclude No scenario covers the unconfigured-register path; it is an
	 *  operational guard, and its contract is that the operator's PATCH still
	 *  succeeds.
	 *
	 * @return void
	 */
	public function testUnconfiguredRegisterDeclinesQuietly(): void {
		$this->consentCrud->method('getConsentConfig')->willReturn(null);
		$this->consentService->expects($this->never())->method('createConsentRequest');

		$this->listener->handle($this->makeEvent(true, $this->makeRelation(42, 7)));
	}//end testUnconfiguredRegisterDeclinesQuietly()

	/**
	 * A failure inside consent creation MUST NOT propagate.
	 *
	 * Nextcloud dispatches synchronously inside the request that changed the
	 * relation, so a throw here would fail the operator's PATCH — turning a
	 * bookkeeping problem into "you cannot save this decision".
	 *
	 * @spec exclude Describes the listener's error posture rather than a
	 *  behaviour any scenario states; asserted here so the posture cannot
	 *  regress silently.
	 *
	 * @return void
	 */
	public function testAFailureNeverEscapes(): void {
		$this->consentCrud->method('getConsentConfig')
			->willReturn(['register' => 'filinq', 'schema' => 'publicationConsent']);
		$this->withEntity('PERSON', 'Anneke Jansen', 'entity-uuid-1');
		$this->consentService->method('createConsentRequest')
			->willThrowException(new RuntimeException('OpenRegister unavailable'));

		$this->listener->handle($this->makeEvent(true, $this->makeRelation(42, 7)));

		$this->addToAssertionCount(1);
	}//end testAFailureNeverEscapes()

	/**
	 * A relation with no fileId has no document to attach a record to.
	 *
	 * @spec exclude Guard for malformed input; no scenario describes a relation
	 *  without a fileId.
	 *
	 * @return void
	 */
	public function testARelationWithoutAFileIdCreatesNothing(): void {
		$this->consentCrud->method('getConsentConfig')
			->willReturn(['register' => 'filinq', 'schema' => 'publicationConsent']);
		$this->consentService->expects($this->never())->method('createConsentRequest');

		$this->listener->handle($this->makeEvent(true, $this->makeRelation(null, 7)));
	}//end testARelationWithoutAFileIdCreatesNothing()
}//end class
