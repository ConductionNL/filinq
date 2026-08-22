<?php

/**
 * Unit tests for FilinqEventHandler
 *
 * Covers the three OpenRegister object-lifecycle handlers, the payload-shape
 * heuristics that route policy mutations to PolicyRetroactiveService, the
 * dossier grondslagen-summary auto-regen, and the content-change gate that
 * decides whether an update is re-enriched.
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

use OCA\Filinq\EventListener\EnrichmentRunner;
use OCA\Filinq\EventListener\FilinqEventHandler;
use OCA\Filinq\Service\LegalBasesSummaryService;
use OCA\Filinq\Service\MetadataService;
use OCA\Filinq\Service\PolicyRetroactiveService;
use OCA\Filinq\Service\SettingsService;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for FilinqEventHandler
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FilinqEventHandlerTest extends TestCase {

	/**
	 * The handler under test.
	 *
	 * @var FilinqEventHandler
	 */
	private FilinqEventHandler $handler;

	/**
	 * Mock enrichment runner — the handler's only outbound enrichment edge.
	 *
	 * @var MockObject&EnrichmentRunner
	 */
	private MockObject $enrichmentRunner;

	/**
	 * Mock metadata service.
	 *
	 * @var MockObject&MetadataService
	 */
	private MockObject $metadataService;

	/**
	 * Mock settings service.
	 *
	 * @var MockObject&SettingsService
	 */
	private MockObject $settingsService;

	/**
	 * Mock logger.
	 *
	 * @var MockObject&LoggerInterface
	 */
	private MockObject $logger;

	/**
	 * Mock retroactive policy applicator.
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

		$this->enrichmentRunner = $this->createMock(originalClassName: EnrichmentRunner::class);
		$this->metadataService = $this->createMock(originalClassName: MetadataService::class);
		$this->settingsService = $this->createMock(originalClassName: SettingsService::class);
		$this->logger = $this->createMock(originalClassName: LoggerInterface::class);
		$this->retroactive = $this->createMock(originalClassName: PolicyRetroactiveService::class);

		$this->handler = new FilinqEventHandler(enrichmentRunner: $this->enrichmentRunner);

		$this->previousServer = \OC::$server;

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
	 * Build an ObjectEntity carrying the supplied payload.
	 *
	 * @param array<string, mixed> $payload The object payload.
	 * @param string $uuid The object UUID.
	 * @param string $register The register identifier.
	 * @param string $schema The schema identifier.
	 *
	 * @return ObjectEntity
	 */
	private function makeObject(
		array $payload,
		string $uuid = 'uuid-1',
		string $register = 'register-1',
		string $schema = 'schema-1',
	): ObjectEntity {
		$object = new ObjectEntity();
		$object->setUuid($uuid);
		$object->setRegister($register);
		$object->setSchema($schema);
		$object->setObject($payload);

		return $object;
	}//end makeObject()

	/**
	 * Install a fake service locator resolving exactly one class.
	 *
	 * @param string $class The class the container answers for.
	 * @param object $service The instance to return.
	 *
	 * @return void
	 */
	private function installContainer(string $class, object $service): void {
		\OC::$server = new class ($class, $service) {

			/**
			 * Constructor.
			 *
			 * @param string $class   The resolvable class name.
			 * @param object $service The instance to hand back.
			 */
			public function __construct(
				private readonly string $class,
				private readonly object $service,
			) {
			}

			/**
			 * Resolve a service.
			 *
			 * @param string $id The requested class name.
			 *
			 * @return object
			 */
			public function get(string $id): object {
				if ($id !== $this->class) {
					throw new \Exception('Unexpected service requested: ' . $id);
				}

				return $this->service;
			}
		};

	}//end installContainer()

	/**
	 * A canonical publicationProhibition payload.
	 *
	 * @return array<string, mixed>
	 */
	private function prohibitionPayload(): array {
		return [
			'matchRules' => [['field' => 'name', 'value' => 'x']],
			'reason' => 'Privacy',
			'legalAuthority' => 'AVG art. 6',
		];
	}//end prohibitionPayload()

	/**
	 * A canonical dossier payload with a review timestamp.
	 *
	 * @param string $checkedOn The review timestamp.
	 * @param array<string, mixed> $extra Extra top-level keys.
	 *
	 * @return array<string, mixed>
	 */
	private function dossierPayload(string $checkedOn = '2026-08-22T10:00:00+00:00', array $extra = []): array {
		return array_merge(
			[
				'name' => 'Dossier A',
				'bases' => [],
				'checkedOn' => $checkedOn,
				'@self' => ['id' => 'dossier-uuid'],
			],
			$extra
		);
	}//end dossierPayload()

	/**
	 * Test that the handler can be constructed with its default runner
	 *
	 * @return void
	 */
	public function testHandlerUsesADefaultEnrichmentRunner(): void {
		$this->assertInstanceOf(expected: FilinqEventHandler::class, actual: new FilinqEventHandler());

	}//end testHandlerUsesADefaultEnrichmentRunner()

	/**
	 * Test that a creation event with no object warns and skips enrichment
	 *
	 * @return void
	 */
	public function testHandleObjectCreatedWithNullObjectWarnsAndSkips(): void {
		$this->logger->expects($this->once())
			->method('warning')
			->with($this->stringContains('ObjectCreatedEvent received with null object'));
		$this->enrichmentRunner->expects($this->never())->method('enrichObject');
		$this->retroactive->expects($this->never())->method('applyProhibitionMutation');

		$this->handler->handleObjectCreated(
			new ObjectCreatedEvent(null),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testHandleObjectCreatedWithNullObjectWarnsAndSkips()

	/**
	 * Test that a plain created object is enriched with the 'new object' context
	 *
	 * @return void
	 */
	public function testHandleObjectCreatedEnrichesPlainObject(): void {
		$object = $this->makeObject(['title' => 'Report']);

		$this->enrichmentRunner->expects($this->once())
			->method('enrichObject')
			->with(
				$this->identicalTo($object),
				$this->identicalTo($this->metadataService),
				$this->identicalTo($this->settingsService),
				$this->identicalTo($this->logger),
				$this->identicalTo('new object')
			);
		$this->retroactive->expects($this->never())->method('applyProhibitionMutation');
		$this->retroactive->expects($this->never())->method('applyStandingConsentMutation');
		$this->retroactive->expects($this->never())->method('applyRuleRemoval');

		$this->handler->handleObjectCreated(
			new ObjectCreatedEvent($object),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testHandleObjectCreatedEnrichesPlainObject()

	/**
	 * Test that a created prohibition is dispatched to the retroactive layer
	 *
	 * @return void
	 */
	public function testHandleObjectCreatedDispatchesProhibitionMutation(): void {
		$payload = $this->prohibitionPayload();

		$this->retroactive->expects($this->once())
			->method('applyProhibitionMutation')
			->with($this->identicalTo($payload))
			->willReturn(3);
		$this->logger->expects($this->once())
			->method('info')
			->with(
				$this->stringContains('prohibition mutation force-resolved'),
				$this->identicalTo(['resolved' => 3, 'reason' => 'created'])
			);
		$this->enrichmentRunner->expects($this->once())->method('enrichObject');

		$this->handler->handleObjectCreated(
			new ObjectCreatedEvent($this->makeObject($payload)),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testHandleObjectCreatedDispatchesProhibitionMutation()

	/**
	 * Test that a prohibition resolving zero records logs no info line
	 *
	 * @return void
	 */
	public function testProhibitionResolvingNoRecordsLogsNothing(): void {
		$this->retroactive->expects($this->once())
			->method('applyProhibitionMutation')
			->willReturn(0);
		$this->logger->expects($this->never())->method('info');

		$this->handler->handleObjectCreated(
			new ObjectCreatedEvent($this->makeObject($this->prohibitionPayload())),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testProhibitionResolvingNoRecordsLogsNothing()

	/**
	 * Test that a scope=entity consent is treated as a standing consent
	 *
	 * @return void
	 */
	public function testStandingConsentDispatchesStandingConsentMutation(): void {
		$payload = ['consentStatus' => 'granted', 'scope' => 'entity'];

		$this->retroactive->expects($this->once())->method('applyStandingConsentMutation');
		$this->retroactive->expects($this->never())->method('applyProhibitionMutation');

		$this->handler->handleObjectCreated(
			new ObjectCreatedEvent($this->makeObject($payload)),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testStandingConsentDispatchesStandingConsentMutation()

	/**
	 * Test that a document-scoped consent is a retroactive no-op
	 *
	 * Both an explicit scope=document and a missing scope (which defaults to
	 * 'document') are workflow records, not policy rules.
	 *
	 * @param array<string, mixed> $payload The consent payload.
	 *
	 * @return void
	 *
	 * @dataProvider documentConsentProvider
	 */
	public function testDocumentScopedConsentIsARetroactiveNoOp(array $payload): void {
		$this->retroactive->expects($this->never())->method('applyStandingConsentMutation');
		$this->retroactive->expects($this->never())->method('applyProhibitionMutation');
		$this->retroactive->expects($this->never())->method('applyRuleRemoval');
		$this->enrichmentRunner->expects($this->once())->method('enrichObject');

		$this->handler->handleObjectCreated(
			new ObjectCreatedEvent($this->makeObject($payload)),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testDocumentScopedConsentIsARetroactiveNoOp()

	/**
	 * Data provider for document-scoped consent payloads.
	 *
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function documentConsentProvider(): array {
		return [
			'explicit document scope' => [['consentStatus' => 'granted', 'scope' => 'document']],
			'missing scope defaults to document' => [['consentStatus' => 'granted']],
		];
	}//end documentConsentProvider()

	/**
	 * Test that a payload failing the prohibition signature is not dispatched
	 *
	 * @param array<string, mixed> $payload The non-prohibition payload.
	 *
	 * @return void
	 *
	 * @dataProvider nonProhibitionProvider
	 */
	public function testPayloadsFailingTheProhibitionSignatureAreNotDispatched(array $payload): void {
		$this->retroactive->expects($this->never())->method('applyProhibitionMutation');
		$this->retroactive->expects($this->never())->method('applyStandingConsentMutation');
		$this->enrichmentRunner->expects($this->once())->method('enrichObject');

		$this->handler->handleObjectCreated(
			new ObjectCreatedEvent($this->makeObject($payload)),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testPayloadsFailingTheProhibitionSignatureAreNotDispatched()

	/**
	 * Data provider for payloads that must NOT read as a prohibition.
	 *
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function nonProhibitionProvider(): array {
		return [
			'no matchRules at all' => [['reason' => 'Privacy']],
			'matchRules empty' => [['matchRules' => [], 'reason' => 'Privacy']],
			'matchRules not an array' => [['matchRules' => 'nope', 'reason' => 'Privacy']],
			'matchRules but blank reason and no authority' => [
				['matchRules' => [['field' => 'a']], 'reason' => ''],
			],
		];
	}//end nonProhibitionProvider()

	/**
	 * Test that legalAuthority alone satisfies the prohibition signature
	 *
	 * @return void
	 */
	public function testLegalAuthorityAloneIdentifiesAProhibition(): void {
		$payload = ['matchRules' => [['field' => 'a']], 'legalAuthority' => 'AVG art. 6'];

		$this->retroactive->expects($this->once())
			->method('applyProhibitionMutation')
			->willReturn(0);

		$this->handler->handleObjectCreated(
			new ObjectCreatedEvent($this->makeObject($payload)),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testLegalAuthorityAloneIdentifiesAProhibition()

	/**
	 * Test that a consentStatus field outranks the prohibition signature
	 *
	 * A record carrying BOTH matchRules and consentStatus is a consent, not a
	 * prohibition — consent records always carry consentStatus.
	 *
	 * @return void
	 */
	public function testConsentStatusOutranksTheProhibitionSignature(): void {
		$payload = [
			'matchRules' => [['field' => 'a']],
			'reason' => 'Privacy',
			'consentStatus' => 'granted',
			'scope' => 'entity',
		];

		$this->retroactive->expects($this->never())->method('applyProhibitionMutation');
		$this->retroactive->expects($this->once())->method('applyStandingConsentMutation');

		$this->handler->handleObjectCreated(
			new ObjectCreatedEvent($this->makeObject($payload)),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testConsentStatusOutranksTheProhibitionSignature()

	/**
	 * Test that a failing retroactive dispatch is logged and does not abort enrichment
	 *
	 * @return void
	 */
	public function testRetroactiveFailureIsLoggedAndEnrichmentStillRuns(): void {
		$this->retroactive->expects($this->once())
			->method('applyProhibitionMutation')
			->willThrowException(new \Exception('boom'));
		$this->logger->expects($this->once())
			->method('warning')
			->with(
				$this->stringContains('retroactive policy dispatch failed'),
				$this->identicalTo(['error' => 'boom', 'reason' => 'created'])
			);
		$this->enrichmentRunner->expects($this->once())->method('enrichObject');

		$this->handler->handleObjectCreated(
			new ObjectCreatedEvent($this->makeObject($this->prohibitionPayload())),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testRetroactiveFailureIsLoggedAndEnrichmentStillRuns()

	/**
	 * Test that an update event with no new object warns and skips
	 *
	 * @return void
	 */
	public function testHandleObjectUpdatedWithNullObjectWarnsAndSkips(): void {
		$this->logger->expects($this->once())
			->method('warning')
			->with($this->stringContains('ObjectUpdatedEvent received with null object'));
		$this->enrichmentRunner->expects($this->never())->method('enrichObject');

		$this->handler->handleObjectUpdated(
			new ObjectUpdatedEvent(null, $this->makeObject(['title' => 'old'])),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testHandleObjectUpdatedWithNullObjectWarnsAndSkips()

	/**
	 * Test that an unchanged object is not re-enriched
	 *
	 * @return void
	 */
	public function testHandleObjectUpdatedSkipsEnrichmentWhenContentIsUnchanged(): void {
		$payload = ['title' => 'Same', 'content' => 'Same body', 'status' => 'draft'];
		$old = ['title' => 'Same', 'content' => 'Same body', 'status' => 'published'];

		$this->enrichmentRunner->expects($this->never())->method('enrichObject');
		$this->logger->expects($this->once())
			->method('debug')
			->with(
				$this->stringContains('No content change detected'),
				$this->identicalTo(['objectId' => 'uuid-1'])
			);

		$this->handler->handleObjectUpdated(
			new ObjectUpdatedEvent($this->makeObject($payload), $this->makeObject($old)),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testHandleObjectUpdatedSkipsEnrichmentWhenContentIsUnchanged()

	/**
	 * Test that a change in any single content field triggers re-enrichment
	 *
	 * @param string $field The content field that changed.
	 *
	 * @return void
	 *
	 * @dataProvider contentFieldProvider
	 */
	public function testHandleObjectUpdatedEnrichesWhenAContentFieldChanges(string $field): void {
		$old = ['content' => 'a', 'text' => 'b', 'description' => 'c', 'title' => 'd'];
		$new = $old;
		$new[$field] = 'CHANGED';

		$newObject = $this->makeObject($new);

		$this->enrichmentRunner->expects($this->once())
			->method('enrichObject')
			->with(
				$this->identicalTo($newObject),
				$this->identicalTo($this->metadataService),
				$this->identicalTo($this->settingsService),
				$this->identicalTo($this->logger),
				$this->identicalTo('updated object')
			);

		$this->handler->handleObjectUpdated(
			new ObjectUpdatedEvent($newObject, $this->makeObject($old)),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testHandleObjectUpdatedEnrichesWhenAContentFieldChanges()

	/**
	 * Data provider naming each watched content field.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function contentFieldProvider(): array {
		return [
			'content' => ['content'],
			'text' => ['text'],
			'description' => ['description'],
			'title' => ['title'],
		];
	}//end contentFieldProvider()

	/**
	 * Test that a missing previous state is treated as an empty payload
	 *
	 * With no old object every populated content field reads as changed, so
	 * the object must be enriched rather than skipped.
	 *
	 * @return void
	 */
	public function testHandleObjectUpdatedTreatsAMissingOldObjectAsEmpty(): void {
		$this->enrichmentRunner->expects($this->once())->method('enrichObject');

		$this->handler->handleObjectUpdated(
			new ObjectUpdatedEvent($this->makeObject(['title' => 'New']), null),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testHandleObjectUpdatedTreatsAMissingOldObjectAsEmpty()

	/**
	 * Test that an updated prohibition dispatches with reason 'updated'
	 *
	 * @return void
	 */
	public function testHandleObjectUpdatedDispatchesProhibitionWithUpdatedReason(): void {
		$payload = $this->prohibitionPayload();

		$this->retroactive->expects($this->once())
			->method('applyProhibitionMutation')
			->with($this->identicalTo($payload))
			->willReturn(2);
		$this->logger->expects($this->once())
			->method('info')
			->with(
				$this->anything(),
				$this->identicalTo(['resolved' => 2, 'reason' => 'updated'])
			);

		$this->handler->handleObjectUpdated(
			new ObjectUpdatedEvent($this->makeObject($payload), $this->makeObject($payload)),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testHandleObjectUpdatedDispatchesProhibitionWithUpdatedReason()

	/**
	 * Test that a changed dossier checkedOn triggers the summary regen
	 *
	 * @return void
	 */
	public function testDossierCheckedOnChangeTriggersSummaryRegen(): void {
		$summaryService = $this->createMock(originalClassName: LegalBasesSummaryService::class);
		$summaryService->expects($this->once())
			->method('renderDossierSummary')
			->with($this->identicalTo('dossier-uuid'));
		$this->installContainer(LegalBasesSummaryService::class, $summaryService);

		$this->handler->handleObjectUpdated(
			new ObjectUpdatedEvent(
				$this->makeObject($this->dossierPayload()),
				$this->makeObject($this->dossierPayload('2026-01-01T00:00:00+00:00'))
			),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testDossierCheckedOnChangeTriggersSummaryRegen()

	/**
	 * Test that the regen is skipped when the payload opts out
	 *
	 * @return void
	 */
	public function testRegenSkippedWhenAutoRegenOnReviewIsDisabled(): void {
		$summaryService = $this->createMock(originalClassName: LegalBasesSummaryService::class);
		$summaryService->expects($this->never())->method('renderDossierSummary');
		$this->installContainer(LegalBasesSummaryService::class, $summaryService);

		$payload = $this->dossierPayload(
			extra: ['configuration' => ['grondslagen' => ['autoRegenOnReview' => false]]]
		);

		$this->handler->handleObjectUpdated(
			new ObjectUpdatedEvent($this->makeObject($payload), $this->makeObject($this->dossierPayload('2026-01-01'))),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testRegenSkippedWhenAutoRegenOnReviewIsDisabled()

	/**
	 * Test that a malformed or absent grondslagen configuration keeps the default
	 *
	 * A `configuration` that is not an array, or that lacks a `grondslagen`
	 * block, must fall back to auto-regen ENABLED.
	 *
	 * @param array<string, mixed> $extra Extra top-level keys for the payload.
	 *
	 * @return void
	 *
	 * @dataProvider autoRegenDefaultProvider
	 */
	public function testAutoRegenDefaultsToEnabled(array $extra): void {
		$summaryService = $this->createMock(originalClassName: LegalBasesSummaryService::class);
		$summaryService->expects($this->once())->method('renderDossierSummary');
		$this->installContainer(LegalBasesSummaryService::class, $summaryService);

		$this->handler->handleObjectUpdated(
			new ObjectUpdatedEvent(
				$this->makeObject($this->dossierPayload(extra: $extra)),
				$this->makeObject($this->dossierPayload('2026-01-01'))
			),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testAutoRegenDefaultsToEnabled()

	/**
	 * Data provider for configurations that must not disable auto-regen.
	 *
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function autoRegenDefaultProvider(): array {
		return [
			'no configuration key' => [[]],
			'configuration not an array' => [['configuration' => 'nope']],
			'configuration without grondslagen' => [['configuration' => ['other' => true]]],
			'grondslagen without the toggle' => [['configuration' => ['grondslagen' => ['fileId' => 7]]]],
			'toggle explicitly true' => [['configuration' => ['grondslagen' => ['autoRegenOnReview' => true]]]],
		];
	}//end autoRegenDefaultProvider()

	/**
	 * Test that an unchanged or empty checkedOn does not trigger a regen
	 *
	 * @param string $newCheckedOn The new review timestamp.
	 * @param string $oldCheckedOn The previous review timestamp.
	 *
	 * @return void
	 *
	 * @dataProvider unchangedCheckedOnProvider
	 */
	public function testRegenSkippedWhenCheckedOnDidNotChangeToAValue(
		string $newCheckedOn,
		string $oldCheckedOn,
	): void {
		$summaryService = $this->createMock(originalClassName: LegalBasesSummaryService::class);
		$summaryService->expects($this->never())->method('renderDossierSummary');
		$this->installContainer(LegalBasesSummaryService::class, $summaryService);

		$this->handler->handleObjectUpdated(
			new ObjectUpdatedEvent(
				$this->makeObject($this->dossierPayload($newCheckedOn)),
				$this->makeObject($this->dossierPayload($oldCheckedOn))
			),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testRegenSkippedWhenCheckedOnDidNotChangeToAValue()

	/**
	 * Data provider for checkedOn values that must not trigger a regen.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function unchangedCheckedOnProvider(): array {
		return [
			'identical timestamps' => ['2026-08-22T10:00:00+00:00', '2026-08-22T10:00:00+00:00'],
			'cleared to empty string' => ['', '2026-08-22T10:00:00+00:00'],
		];
	}//end unchangedCheckedOnProvider()

	/**
	 * Test that a payload without the dossier signature never triggers a regen
	 *
	 * @param array<string, mixed> $payload The non-dossier payload.
	 *
	 * @return void
	 *
	 * @dataProvider nonDossierProvider
	 */
	public function testRegenSkippedForNonDossierShapes(array $payload): void {
		$summaryService = $this->createMock(originalClassName: LegalBasesSummaryService::class);
		$summaryService->expects($this->never())->method('renderDossierSummary');
		$this->installContainer(LegalBasesSummaryService::class, $summaryService);

		$this->handler->handleObjectUpdated(
			new ObjectUpdatedEvent($this->makeObject($payload), $this->makeObject([])),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testRegenSkippedForNonDossierShapes()

	/**
	 * Data provider for payloads missing part of the dossier signature.
	 *
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function nonDossierProvider(): array {
		return [
			'missing name' => [['bases' => [], 'checkedOn' => '2026-08-22']],
			'missing bases' => [['name' => 'x', 'checkedOn' => '2026-08-22']],
			'no checkedOn and no configuration' => [['name' => 'x', 'bases' => []]],
		];
	}//end nonDossierProvider()

	/**
	 * Test that the dossier UUID is resolved through its documented fallbacks
	 *
	 * @param array<string, mixed> $extra Identifier keys placed on the payload.
	 * @param string $expected The UUID the summary service must receive.
	 *
	 * @return void
	 *
	 * @dataProvider dossierUuidProvider
	 */
	public function testDossierUuidIsResolvedThroughItsFallbacks(array $extra, string $expected): void {
		$summaryService = $this->createMock(originalClassName: LegalBasesSummaryService::class);
		$summaryService->expects($this->once())
			->method('renderDossierSummary')
			->with($this->identicalTo($expected));
		$this->installContainer(LegalBasesSummaryService::class, $summaryService);

		$payload = [
			'name' => 'Dossier A',
			'bases' => [],
			'checkedOn' => '2026-08-22T10:00:00+00:00',
		];

		$this->handler->handleObjectUpdated(
			new ObjectUpdatedEvent(
				$this->makeObject(array_merge($payload, $extra)),
				$this->makeObject([])
			),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testDossierUuidIsResolvedThroughItsFallbacks()

	/**
	 * Data provider for the dossier UUID resolution order.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: string}>
	 */
	public static function dossierUuidProvider(): array {
		return [
			'@self.id wins' => [['@self' => ['id' => 'self-id'], 'id' => 'top-id'], 'self-id'],
			'@self.uuid when no @self.id' => [['@self' => ['uuid' => 'self-uuid'], 'id' => 'top-id'], 'self-uuid'],
			'top-level id when @self empty' => [['@self' => [], 'id' => 'top-id'], 'top-id'],
			'top-level uuid last' => [['uuid' => 'top-uuid'], 'top-uuid'],
		];
	}//end dossierUuidProvider()

	/**
	 * Test that a dossier with no resolvable UUID warns instead of regenerating
	 *
	 * @return void
	 */
	public function testDossierWithoutAUuidWarnsAndSkipsRegen(): void {
		$summaryService = $this->createMock(originalClassName: LegalBasesSummaryService::class);
		$summaryService->expects($this->never())->method('renderDossierSummary');
		$this->installContainer(LegalBasesSummaryService::class, $summaryService);

		$this->logger->expects($this->once())
			->method('warning')
			->with($this->stringContains('UUID missing'));

		$payload = ['name' => 'Dossier A', 'bases' => [], 'checkedOn' => '2026-08-22T10:00:00+00:00'];

		$this->handler->handleObjectUpdated(
			new ObjectUpdatedEvent($this->makeObject($payload), $this->makeObject([])),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testDossierWithoutAUuidWarnsAndSkipsRegen()

	/**
	 * Test that a failing regen is logged and the update still completes
	 *
	 * @return void
	 */
	public function testRegenFailureIsLoggedAndTheUpdateStillEnriches(): void {
		$summaryService = $this->createMock(originalClassName: LegalBasesSummaryService::class);
		$summaryService->expects($this->once())
			->method('renderDossierSummary')
			->willThrowException(new \RuntimeException('render failed'));
		$this->installContainer(LegalBasesSummaryService::class, $summaryService);

		$this->logger->expects($this->once())
			->method('warning')
			->with(
				$this->stringContains('grondslagen-summary auto-regen failed'),
				$this->identicalTo(['dossierUuid' => 'dossier-uuid', 'error' => 'render failed'])
			);
		$this->enrichmentRunner->expects($this->once())->method('enrichObject');

		$new = $this->dossierPayload();
		$new['title'] = 'Changed';

		$this->handler->handleObjectUpdated(
			new ObjectUpdatedEvent(
				$this->makeObject($new),
				$this->makeObject($this->dossierPayload('2026-01-01'))
			),
			$this->metadataService,
			$this->settingsService,
			$this->logger,
			$this->retroactive
		);

	}//end testRegenFailureIsLoggedAndTheUpdateStillEnriches()

	/**
	 * Test that a deletion event with no object warns and skips
	 *
	 * @return void
	 */
	public function testHandleObjectDeletedWithNullObjectWarnsAndSkips(): void {
		$this->logger->expects($this->once())
			->method('warning')
			->with($this->stringContains('ObjectDeletedEvent received with null object'));
		$this->logger->expects($this->never())->method('info');
		$this->retroactive->expects($this->never())->method('applyRuleRemoval');

		$this->handler->handleObjectDeleted(
			new ObjectDeletedEvent(null),
			$this->logger,
			$this->retroactive
		);

	}//end testHandleObjectDeletedWithNullObjectWarnsAndSkips()

	/**
	 * Test that a deleted object is logged with its register/schema context
	 *
	 * @return void
	 */
	public function testHandleObjectDeletedLogsTheObjectContext(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				$this->stringContains('Object deleted'),
				$this->identicalTo(
					[
						'objectId' => 'uuid-9',
						'schemaId' => 'schema-9',
						'registerId' => 'register-9',
					]
				)
			);
		$this->retroactive->expects($this->never())->method('applyRuleRemoval');

		$this->handler->handleObjectDeleted(
			new ObjectDeletedEvent(
				$this->makeObject(['title' => 'gone'], 'uuid-9', 'register-9', 'schema-9')
			),
			$this->logger,
			$this->retroactive
		);

	}//end testHandleObjectDeletedLogsTheObjectContext()

	/**
	 * Test that deleting a policy rule applies a rule removal
	 *
	 * Both a prohibition and a standing consent are policy rules whose removal
	 * must re-evaluate the affected records.
	 *
	 * @param array<string, mixed> $payload The deleted policy payload.
	 *
	 * @return void
	 *
	 * @dataProvider deletedPolicyProvider
	 */
	public function testDeletingAPolicyRuleAppliesRuleRemoval(array $payload): void {
		$this->retroactive->expects($this->once())->method('applyRuleRemoval');
		$this->retroactive->expects($this->never())->method('applyProhibitionMutation');
		$this->retroactive->expects($this->never())->method('applyStandingConsentMutation');

		$this->handler->handleObjectDeleted(
			new ObjectDeletedEvent($this->makeObject($payload)),
			$this->logger,
			$this->retroactive
		);

	}//end testDeletingAPolicyRuleAppliesRuleRemoval()

	/**
	 * Data provider for deleted policy-rule payloads.
	 *
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function deletedPolicyProvider(): array {
		return [
			'prohibition' => [
				['matchRules' => [['field' => 'a']], 'reason' => 'Privacy'],
			],
			'standing consent' => [
				['consentStatus' => 'granted', 'scope' => 'entity'],
			],
		];
	}//end deletedPolicyProvider()

	/**
	 * Test that deleting a document-scoped consent applies no rule removal
	 *
	 * @return void
	 */
	public function testDeletingADocumentConsentIsARetroactiveNoOp(): void {
		$this->retroactive->expects($this->never())->method('applyRuleRemoval');

		$this->handler->handleObjectDeleted(
			new ObjectDeletedEvent($this->makeObject(['consentStatus' => 'granted', 'scope' => 'document'])),
			$this->logger,
			$this->retroactive
		);

	}//end testDeletingADocumentConsentIsARetroactiveNoOp()

	/**
	 * Test that a failing rule removal on delete is logged, not thrown
	 *
	 * @return void
	 */
	public function testFailingRuleRemovalOnDeleteIsLogged(): void {
		$this->retroactive->expects($this->once())
			->method('applyRuleRemoval')
			->willThrowException(new \Exception('removal failed'));
		$this->logger->expects($this->once())
			->method('warning')
			->with(
				$this->stringContains('retroactive policy dispatch failed'),
				$this->identicalTo(['error' => 'removal failed', 'reason' => 'deleted'])
			);

		$this->handler->handleObjectDeleted(
			new ObjectDeletedEvent($this->makeObject(['matchRules' => [['f' => 'a']], 'reason' => 'Privacy'])),
			$this->logger,
			$this->retroactive
		);

	}//end testFailingRuleRemovalOnDeleteIsLogged()
}//end class
