<?php

/**
 * Unit tests for the publication pipeline
 *
 * Real store, readiness, map and clearance over an in-memory OpenRegister;
 * every payload written is validated against the real schema it goes into.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Publication
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/woo-publicatie-pipeline/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Publication;

use DateTimeImmutable;
use InvalidArgumentException;
use OCA\Filinq\Service\ConsentCrudService;
use OCA\Filinq\Service\ConsentService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\PolicyMatchService;
use OCA\Filinq\Service\Publication\ConsentClearance;
use OCA\Filinq\Service\Publication\OpenCatalogiPlatform;
use OCA\Filinq\Service\Publication\OpenCatalogiPublicationMap;
use OCA\Filinq\Service\Publication\PublicationNotReadyException;
use OCA\Filinq\Service\Publication\PublicationPipelineService;
use OCA\Filinq\Service\Publication\PublicationReadiness;
use OCA\Filinq\Service\Publication\PublicationStore;
use OCA\Filinq\Service\Redaction\RedactionReviewMarkRepository;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * The pipeline from a document to OpenCatalogi.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Publication
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PublicationPipelineServiceTest extends TestCase {

	/**
	 * Stored objects per schema slug, by uuid.
	 *
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	private array $rows = [];

	/**
	 * Every save, in order: [schema, object].
	 *
	 * @var array<int, array{0: string, 1: array<string, mixed>}>
	 */
	private array $saves = [];

	/**
	 * Files attached to platform publications: [uuid, name, share].
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $attached = [];

	/**
	 * The consent records of the document.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $consents = [];

	/**
	 * Whether a review mark exists.
	 *
	 * @var bool
	 */
	private bool $reviewed = true;

	/**
	 * Whether OpenCatalogi is enabled.
	 *
	 * @var bool
	 */
	private bool $platform = true;

	/**
	 * The pipeline over the in-memory world.
	 *
	 * @return PublicationPipelineService
	 */
	private function pipeline(): PublicationPipelineService {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object = [], string $register = '', string $schema = '', ?string $uuid = null): array {
				$this->saves[] = [$schema, $object];
				$uuid = $uuid ?? ($schema . '-' . (count($this->rows[$schema] ?? []) + 1));
				$this->rows[$schema][$uuid] = array_merge($object, ['uuid' => $uuid]);
				return $this->rows[$schema][$uuid];
			}
		);
		$objects->method('find')->willReturnCallback(
			fn (string $id = '', ?string $register = null, ?string $schema = null): ?array => $this->rows[$schema][$id] ?? null
		);
		$objects->method('searchObjectsBySlug')->willReturnCallback(
			fn (string $registerSlug, string $schemaSlug, array $filters = []): array => array_values(
				array_filter(
					$this->rows[$schemaSlug] ?? [],
					static function (array $row) use ($filters): bool {
						foreach ($filters as $key => $value) {
							if (($row[$key] ?? null) !== $value) {
								return false;
							}
						}

						return true;
					}
				)
			)
		);
		$this->rows['anonymizationLink']['link-1'] = ['sourceFileId' => '42', 'anonymizedFileId' => '43'];

		$fileService = new class ($this) {
			/**
			 * Constructor.
			 *
			 * @param PublicationPipelineServiceTest $test The test that records the attachment
			 */
			public function __construct(private PublicationPipelineServiceTest $test) {
			}

			public function addFile(string $objectEntity, string $fileName, mixed $content, bool $share = false): void {
				$this->test->recordAttachment(uuid: $objectEntity, name: $fileName, share: $share);
			}
		};
		$tooi = new class {
			/**
			 * Two entries in OpenCatalogi's shape.
			 *
			 * @return array<string, array{uri: string, label: string}>
			 */
			public function informatiecategorieList(): array {
				return [
					'infocat001' => ['uri' => 'https://identifier.overheid.nl/tooi/def/thes/kern/c_139c6280', 'label' => 'Wetten en algemeen verbindende voorschriften'],
					'infocat009' => ['uri' => 'https://identifier.overheid.nl/tooi/def/thes/kern/c_8c840238', 'label' => 'Adviezen'],
				];
			}
		};
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static fn (string $id): object => match ($id) {
				'OCA\OpenRegister\Service\FileService' => $fileService,
				'OCA\OpenCatalogi\Service\TooiVocabularyService' => $tooi,
				default => $objects,
			}
		);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);
		$apps->method('isEnabledForAnyone')->willReturnCallback(fn (string $app): bool => $app === 'opencatalogi' && $this->platform);
		$store = new PublicationStore(objects: new DocumentObjectServiceResolver($container, $apps));

		$consentService = $this->createMock(ConsentService::class);
		$consentService->method('getConsentsByDocument')->willReturnCallback(fn (): array => $this->consents);
		$consentService->method('isDocumentConsentClear')->willReturnCallback(
			fn (string $documentId, string $register, string $schema, DateTimeImmutable $now): array => (new ConsentClearance())->evaluate(consents: $this->consents, now: $now)
		);
		$consentConfig = $this->createMock(ConsentCrudService::class);
		$consentConfig->method('getConsentConfig')->willReturn(['register' => 'filinq', 'schema' => 'publicationConsent']);
		$policies = $this->createMock(PolicyMatchService::class);
		$policies->method('matchProhibition')->willReturnCallback(
			static fn (string $entityText): ?array => ($entityText === 'Verboden Persoon') ? ['uuid' => 'prohibition-7', 'kind' => 'prohibition', 'entityType' => 'PERSON', 'primaryName' => 'x'] : null
		);
		$marks = $this->createMock(RedactionReviewMarkRepository::class);
		$marks->method('findFor')->willReturnCallback(fn (): ?array => $this->reviewed ? ['detectionRun' => 'run-1'] : null);

		$copy = $this->createMock(File::class);
		$copy->method('getName')->willReturn('besluit-geanonimiseerd.pdf');
		$copy->method('getContent')->willReturn('%PDF-redacted');
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturnCallback(static fn (int $id): array => ($id === 43) ? [$copy] : []);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($folder);

		$clock = $this->createMock(ITimeFactory::class);
		$clock->method('getTime')->willReturn((new DateTimeImmutable('2026-09-29T12:00:00+00:00'))->getTimestamp());

		return new PublicationPipelineService(
			store: $store,
			readiness: new PublicationReadiness(consents: $consentService, consentConfig: $consentConfig, policies: $policies, marks: $marks, store: $store),
			map: new OpenCatalogiPublicationMap(),
			platform: new OpenCatalogiPlatform(appManager: $apps, container: $container, rootFolder: $root),
			clock: $clock
		);

	}//end pipeline()

	/**
	 * Called by the fake file service.
	 *
	 * @param string $uuid  The publication
	 * @param string $name  The file name
	 * @param bool   $share Whether it is public
	 *
	 * @return void
	 */
	public function recordAttachment(string $uuid, string $name, bool $share): void {
		$this->attached[] = [$uuid, $name, $share];

	}//end recordAttachment()

	/**
	 * A record with complete metadata that passes every check.
	 *
	 * @param PublicationPipelineService $pipeline The pipeline
	 *
	 * @return array<string, mixed> The ready record.
	 */
	private function readyRecord(PublicationPipelineService $pipeline): array {
		$record = $pipeline->create(documentFileRef: '42', subjectType: 'document', dossierRef: 'woo-2025-017', actor: 'anna');

		return $pipeline->updateMetadata(
			record: $record,
			metadata: ['officieleTitel' => 'Besluit 2025-017', 'wooCategory' => 'c_8c840238', 'documentsoort' => 'besluit', 'publicatiedatum' => '2026-09-29'],
			actor: 'anna'
		);

	}//end readyRecord()

	/**
	 * A document that is not ready says why, and stays draft.
	 *
	 * @return void
	 */
	public function testANotReadyDocumentSaysWhy(): void {
		$this->reviewed = false;
		$this->consents = [
			['id' => 'c-1', 'consentStatus' => 'no_response', 'publicationDecision' => 'pending', 'objectionDeadline' => '2026-10-27T00:00:00+00:00', 'entityText' => 'Piet'],
			['id' => 'c-2', 'consentStatus' => 'consent_given', 'publicationDecision' => 'publish_with_consent', 'entityText' => 'Verboden Persoon'],
		];

		$record = $this->pipeline()->create(documentFileRef: '42', subjectType: 'document', dossierRef: '', actor: 'anna');

		$this->assertSame('draft', $record['status']);
		$this->assertFalse($record['entitiesReviewed']);
		$this->assertFalse($record['consentClear']);
		$this->assertFalse($record['prohibitionsClear']);
		$this->assertSame('43', $record['redactedFileRef']);
		$reasons = implode(' | ', $record['readinessReasons']);
		$this->assertStringContainsString('checked the detected entities', $reasons);
		$this->assertStringContainsString('c-1', $reasons);
		$this->assertStringContainsString('prohibition-7', $reasons);
		$this->assertStringNotContainsString('Piet', $reasons);
		$this->assertStringNotContainsString('Verboden', $reasons);
		$this->assertSame(['created', 'readiness_evaluated'], array_column($this->rows['publicationLogEntry'], 'action'));

	}//end testANotReadyDocumentSaysWhy()

	/**
	 * A ready document is handed to OpenCatalogi with its redacted copy, and is published on its date.
	 *
	 * @return void
	 */
	public function testAReadyDocumentIsHandedOffWithTheRedactedCopy(): void {
		$pipeline = $this->pipeline();
		$record = $this->readyRecord(pipeline: $pipeline);
		$this->assertSame('ready', $record['status']);

		$record = $pipeline->handoff(record: $record, actor: 'anna');

		$this->assertSame('published', $record['status']);
		$platform = $this->rows['publication'][$record['endpointPublicationRef']];
		$this->assertSame('Besluit 2025-017', $platform['title']);
		$this->assertSame('2026-09-29T00:00:00+00:00', $platform['publicationDate']);
		$this->assertSame([[$record['endpointPublicationRef'], 'besluit-geanonimiseerd.pdf', true]], $this->attached);
		$this->assertSame(
			['created', 'readiness_evaluated', 'metadata_assembled', 'readiness_evaluated', 'handed_off', 'published'],
			array_column($this->rows['publicationLogEntry'], 'action')
		);

	}//end testAReadyDocumentIsHandedOffWithTheRedactedCopy()

	/**
	 * Readiness is checked again at hand-off: a new objection sends it back to draft.
	 *
	 * @return void
	 */
	public function testAHandoffChecksAgainAndDemotes(): void {
		$pipeline = $this->pipeline();
		$record = $this->readyRecord(pipeline: $pipeline);
		$this->consents = [['id' => 'c-9', 'consentStatus' => 'objection_received', 'publicationDecision' => 'pending']];

		try {
			$pipeline->handoff(record: $record, actor: 'anna');
			$this->fail('A document with an open objection must not be handed off');
		} catch (PublicationNotReadyException $e) {
			$this->assertStringContainsString('c-9', implode(' ', $e->getReasons()));
			$this->assertSame(409, $e->getCode());
		}

		$this->assertSame('draft', end($this->rows['publicationRecord'])['status']);
		$this->assertArrayNotHasKey('publication', $this->rows);
		$this->assertSame([], $this->attached);

	}//end testAHandoffChecksAgainAndDemotes()

	/**
	 * Missing Woo metadata blocks the hand-off, and so does a missing platform.
	 *
	 * @return void
	 */
	public function testMissingMetadataOrPlatformBlocks(): void {
		$pipeline = $this->pipeline();
		$record = $pipeline->create(documentFileRef: '42', subjectType: 'document', dossierRef: '', actor: 'anna');
		try {
			$pipeline->handoff(record: $record, actor: 'anna');
			$this->fail('Metadata is missing');
		} catch (PublicationNotReadyException $e) {
			$this->assertStringContainsString('officieleTitel, wooCategory, publicatiedatum', implode(' ', $e->getReasons()));
		}

		$this->platform = false;
		$this->expectException(RuntimeException::class);
		$this->expectExceptionCode(503);
		$pipeline->handoff(record: $this->readyRecord(pipeline: $pipeline), actor: 'anna');

	}//end testMissingMetadataOrPlatformBlocks()

	/**
	 * The category is one of OpenCatalogi's TOOI categories, never free text.
	 *
	 * @return void
	 */
	public function testTheCategoryComesFromOpenCatalogisList(): void {
		$pipeline = $this->pipeline();
		$record = $pipeline->create(documentFileRef: '42', subjectType: 'document', dossierRef: '', actor: 'anna');

		$this->assertSame('c_8c840238', $pipeline->updateMetadata(record: $record, metadata: ['wooCategory' => 'c_8c840238'], actor: 'anna')['wooCategory']);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionCode(400);
		$pipeline->updateMetadata(record: $record, metadata: ['wooCategory' => 'besluiten'], actor: 'anna');

	}//end testTheCategoryComesFromOpenCatalogisList()

	/**
	 * Withdrawing needs a reason, sets the depublication date and never deletes.
	 *
	 * @return void
	 */
	public function testWithdrawalSetsTheDepublicationDate(): void {
		$pipeline = $this->pipeline();
		$record = $pipeline->handoff(record: $this->readyRecord(pipeline: $pipeline), actor: 'anna');

		try {
			$pipeline->withdraw(record: $record, reason: ' ', actor: 'anna');
			$this->fail('A withdrawal without a reason');
		} catch (InvalidArgumentException $e) {
			$this->assertSame(400, $e->getCode());
		}

		$record = $pipeline->withdraw(record: $record, reason: 'Wrong version published', actor: 'anna');

		$this->assertSame('depublished', $record['status']);
		$this->assertSame('Wrong version published', $record['depublicationReason']);
		$platform = $this->rows['publication'][$record['endpointPublicationRef']];
		$this->assertSame('2026-09-29T12:00:00+00:00', $platform['depublicationDate']);
		$this->assertSame('Besluit 2025-017', $platform['title']);
		$this->assertSame(['depublication_requested', 'depublished'], array_slice(array_column($this->rows['publicationLogEntry'], 'action'), -2));

	}//end testWithdrawalSetsTheDepublicationDate()

	/**
	 * A destruction date reaches the platform with the note OpenCatalogi requires.
	 *
	 * @return void
	 */
	public function testADestructionDateReachesThePlatform(): void {
		$pipeline = $this->pipeline();
		$record = $pipeline->handoff(record: $this->readyRecord(pipeline: $pipeline), actor: 'anna');

		$record = $pipeline->setDestructionDate(record: $record, date: '2034-11-03', source: 'selectielijst 2020, procestype 6', actor: 'anna');

		$platform = $this->rows['publication'][$record['endpointPublicationRef']];
		$this->assertSame('2034-11-03T00:00:00+00:00', $platform['retentionExpiresAt']);
		$this->assertStringContainsString('selectielijst 2020', $platform['retentionNote']);
		$this->assertSame('destruction_date_propagated', end($this->rows['publicationLogEntry'])['action']);

	}//end testADestructionDateReachesThePlatform()

	/**
	 * Every object the pipeline wrote validates against the schema it went
	 * into, and every status change is a declared lifecycle edge.
	 *
	 * @return void
	 */
	public function testEveryPayloadValidatesAndEveryMoveIsDeclared(): void {
		$pipeline = $this->pipeline();
		$record = $pipeline->handoff(record: $this->readyRecord(pipeline: $pipeline), actor: 'anna');
		$record = $pipeline->setDestructionDate(record: $record, date: '2034-11-03', source: 'selectielijst', actor: 'anna');
		$pipeline->withdraw(record: $record, reason: 'Wrong version', actor: 'anna');

		$register = json_decode((string) file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_register.json'), true);
		$platform = json_decode((string) file_get_contents(__DIR__ . '/../../../fixtures/opencatalogi-publication-schema.json'), true);
		$schemas = [
			'publicationRecord' => $register['components']['schemas']['publicationRecord'],
			'publicationLogEntry' => $register['components']['schemas']['publicationLogEntry'],
			'publication' => $platform,
		];
		$edges = [];
		foreach ($register['components']['schemas']['publicationRecord']['x-openregister-lifecycle']['transitions'] as $t) {
			$edges[] = $t['from'] . '>' . $t['to'];
		}

		$this->assertNotContains('draft>handed_off', $edges, 'A draft must not jump to the platform');
		$this->assertNotContains('draft>published', $edges);

		$status = [];
		foreach ($this->saves as [$schema, $object]) {
			$this->assertValid(schema: $schemas[$schema], payload: $object, label: $schema);
			if ($schema === 'publicationRecord') {
				$status[] = $object['status'];
			}
		}

		$previous = 'draft';
		foreach ($status as $next) {
			if ($next !== $previous) {
				$this->assertContains($previous . '>' . $next, $edges);
			}

			$previous = $next;
		}

		$this->assertSame('depublished', $previous);

	}//end testEveryPayloadValidatesAndEveryMoveIsDeclared()

	/**
	 * OpenCatalogi still has every field Filinq writes, with the type Filinq writes.
	 *
	 * @return void
	 */
	public function testTheFieldMapMatchesOpenCatalogi(): void {
		$platform = json_decode((string) file_get_contents(__DIR__ . '/../../../fixtures/opencatalogi-publication-schema.json'), true);
		foreach (OpenCatalogiPublicationMap::FIELDS as $field) {
			$this->assertArrayHasKey($field, $platform['properties'], 'OpenCatalogi no longer has ' . $field);
			$this->assertSame('string', $platform['properties'][$field]['type'], $field);
		}

		$this->assertSame(['title'], $platform['required']);
		$this->assertSame('date-time', $platform['properties']['publicationDate']['format']);
		$this->assertSame('date-time', $platform['properties']['depublicationDate']['format']);

	}//end testTheFieldMapMatchesOpenCatalogi()

	/**
	 * Validate one payload against a register schema.
	 *
	 * @param array<string, mixed> $schema  The schema as the register declares it
	 * @param array<string, mixed> $payload The payload
	 * @param string               $label   For the message
	 *
	 * @return void
	 */
	private function assertValid(array $schema, array $payload, string $label): void {
		$properties = [];
		foreach ($schema['properties'] as $name => $property) {
			unset($property['required'], $property['visible'], $property['order'], $property['facetable'], $property['x-enum-labels']);
			$properties[$name] = $property;
		}

		$json = (string) json_encode(['type' => 'object', 'required' => ($schema['required'] ?? []), 'properties' => (object) $properties, 'additionalProperties' => false]);
		$result = (new Validator())->validate(json_decode((string) json_encode($payload)), $json);
		$message = '';
		if ($result->isValid() === false) {
			$message = $label . ': ' . (string) json_encode((new ErrorFormatter())->format($result->error()));
		}

		$this->assertTrue($result->isValid(), $message);

	}//end assertValid()
}//end class
