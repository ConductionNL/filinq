<?php

/**
 * InboundClassificationService: suggestions, never field writes
 *
 * The real classifier, ranker, matcher, repository and sources; only the
 * OpenRegister edges are doubles (an in-memory object store that validates
 * every classificationResult against the real register fragment, and an
 * EntityRelationMapper double answering detected entities).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/inbound-auto-classification/tasks.md#4-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

require_once __DIR__ . '/Wizard/WizardDoubles.php';
require_once __DIR__ . '/Classification/ClassificationDoubles.php';

use OCA\Filinq\Service\Classification\ClassificationResultRepository;
use OCA\Filinq\Service\Classification\ClassificationSources;
use OCA\Filinq\Service\Classification\CorrespondentRanker;
use OCA\Filinq\Service\Classification\DossierMatcher;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentTypeClassifier;
use OCA\Filinq\Service\FileEntityStatsService;
use OCA\Filinq\Service\InboundClassificationService;
use OCA\Filinq\Tests\Unit\Service\Classification\ClassificationObjectStore;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * Inbound classification over the real classes.
 */
class InboundClassificationServiceTest extends TestCase {

	/**
	 * An invoice's OCR text.
	 *
	 * @var string
	 */
	private const INVOICE = "Heijmans B.V.\nFactuur\nFactuurnummer: 2026-0412\nTe betalen binnen 30 dagen op IBAN NL91ABNA0417164300.\nBedrag excl. btw: 1.250,00";

	/**
	 * The in-memory OpenRegister.
	 *
	 * @var ClassificationObjectStore
	 */
	private ClassificationObjectStore $store;

	/**
	 * Detected entity rows per file id; a missing file id has had no detection.
	 *
	 * @var array<int, array<int, array<string, mixed>>>
	 */
	private array $entities = [];

	/**
	 * The toggle value.
	 *
	 * @var string
	 */
	private string $toggle = '1';

	/**
	 * Set up the store.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->store = new ClassificationObjectStore();

	}//end setUp()

	/**
	 * An invoice gets a factuur suggestion with a confidence, and nothing is written onto the document.
	 *
	 * @return void
	 */
	public function testAnInboundInvoiceIsTypedWithConfidence(): void {
		$intake = $this->intake();
		$outcome = $this->service()->classify(objectData: $intake, objectRef: $this->ref());

		$this->assertSame('suggested', $outcome['outcome']);
		$this->assertSame('factuur', $outcome['record']['suggestedDocumentType']);
		$this->assertGreaterThan(DocumentTypeClassifier::MIN_CONFIDENCE, $outcome['record']['documentTypeConfidence']);
		$this->assertSame('suggested', $outcome['record']['status']);
		// Only classificationResult rows were written: the store refuses any other target.
		$this->assertCount(1, $this->store->saves);

	}//end testAnInboundInvoiceIsTypedWithConfidence()

	/**
	 * With the toggle off nothing is created.
	 *
	 * @return void
	 */
	public function testTheToggleDisablesTheSurface(): void {
		$this->toggle = '0';
		$outcome = $this->service()->classify(objectData: $this->intake(), objectRef: $this->ref());

		$this->assertSame(['outcome' => 'skipped', 'reason' => 'disabled'], $outcome);
		$this->assertSame([], $this->store->saves);

	}//end testTheToggleDisablesTheSurface()

	/**
	 * A document without text is skipped with the reason, not silently.
	 *
	 * @return void
	 */
	public function testADocumentWithoutTextIsSkippedWithItsReason(): void {
		$outcome = $this->service()->classify(objectData: ['file' => 812020, 'channel' => 'scan', 'status' => 'received'], objectRef: $this->ref());

		$this->assertSame(['outcome' => 'skipped', 'reason' => 'no_text'], $outcome);

	}//end testADocumentWithoutTextIsSkippedWithItsReason()

	/**
	 * The letterhead organisation becomes the correspondent, over three body mentions of a person.
	 *
	 * @return void
	 */
	public function testTheLetterheadOrganisationBecomesTheCorrespondent(): void {
		$this->entities[812010] = [
			['entity_type' => 'ORGANIZATION', 'entity_value' => 'Heijmans B.V.', 'position_start' => 0],
			['entity_type' => 'PERSON', 'entity_value' => 'J. de Vries', 'position_start' => 1400],
			['entity_type' => 'PERSON', 'entity_value' => 'J. de Vries', 'position_start' => 1800],
			['entity_type' => 'PERSON', 'entity_value' => 'J. de Vries', 'position_start' => 2300],
		];

		$record = $this->service()->classify(objectData: $this->intake(), objectRef: $this->ref())['record'];

		$this->assertSame(['name' => 'Heijmans B.V.', 'entityType' => 'ORGANIZATION', 'source' => 'ner'], $record['suggestedCorrespondent']);
		$this->assertFalse($record['correspondentPending']);
		$this->assertSame('mixed', $record['method']);

	}//end testTheLetterheadOrganisationBecomesTheCorrespondent()

	/**
	 * Without detection the type leg stands and the correspondent is pending, not invented.
	 *
	 * @return void
	 */
	public function testNoDetectionYetIsFlaggedNotFabricated(): void {
		$record = $this->service()->classify(objectData: $this->intake(), objectRef: $this->ref())['record'];

		$this->assertSame('factuur', $record['suggestedDocumentType']);
		$this->assertTrue($record['correspondentPending']);
		$this->assertNull($record['suggestedCorrespondent']);
		$this->assertSame('rules', $record['method']);

	}//end testNoDetectionYetIsFlaggedNotFabricated()

	/**
	 * Classified twice with detection in between: one active record, the first superseded.
	 *
	 * @return void
	 */
	public function testOneActiveRecordPerFile(): void {
		$service = $this->service();
		$service->classify(objectData: $this->intake(), objectRef: $this->ref());
		$this->entities[812010] = [['entity_type' => 'ORGANIZATION', 'entity_value' => 'Heijmans B.V.', 'position_start' => 0]];
		$service->classify(objectData: $this->intake(), objectRef: $this->ref());

		$statuses = array_column($this->store->rows['classificationResult'], 'status');
		sort($statuses);
		$this->assertSame(['suggested', 'superseded'], $statuses);

	}//end testOneActiveRecordPerFile()

	/**
	 * A suggestion, however confident, is never applied by a re-run.
	 *
	 * @return void
	 */
	public function testNoSuggestionEverAutoApplies(): void {
		$this->entities[812010] = [['entity_type' => 'ORGANIZATION', 'entity_value' => 'Heijmans B.V.', 'position_start' => 0]];
		$service = $this->service();
		$service->classify(objectData: $this->intake(), objectRef: $this->ref());
		$again = $service->classify(objectData: $this->intake(), objectRef: $this->ref());

		$this->assertSame(['outcome' => 'skipped', 'reason' => 'already_classified'], $again);
		$this->assertCount(1, $this->store->saves);
		$this->assertSame('suggested', array_values($this->store->rows['classificationResult'])[0]['status']);

	}//end testNoSuggestionEverAutoApplies()

	/**
	 * The record carries the correspondent name at most: no text, no other entity values.
	 *
	 * @return void
	 */
	public function testTheRecordCarriesNoDocumentContent(): void {
		$this->entities[812010] = [
			['entity_type' => 'ORGANIZATION', 'entity_value' => 'Heijmans B.V.', 'position_start' => 0],
			['entity_type' => 'PERSON', 'entity_value' => 'Piet Puk', 'position_start' => 900],
			['entity_type' => 'IBAN', 'entity_value' => 'NL91ABNA0417164300', 'position_start' => 120],
		];

		$this->service()->classify(objectData: $this->intake(), objectRef: $this->ref());
		$stored = json_encode($this->store->saves[0]);

		$this->assertStringNotContainsString('Factuurnummer', $stored);
		$this->assertStringNotContainsString('Piet Puk', $stored);
		$this->assertStringNotContainsString('NL91ABNA0417164300', $stored);
		$this->assertStringContainsString('Heijmans B.V.', $stored);

	}//end testTheRecordCarriesNoDocumentContent()

	/**
	 * A dossier whose name starts with the correspondent is suggested; nothing moves.
	 *
	 * @return void
	 */
	public function testAMatchingDossierIsSuggestedNotApplied(): void {
		$this->store->rows['dossier']['00000000-0000-0000-0000-00000000d001'] = ['name' => 'Heijmans B.V. — nieuwbouw'];
		$this->store->rows['dossier']['00000000-0000-0000-0000-00000000d002'] = ['name' => 'Bouwbedrijf Jansen'];
		$this->entities[812010] = [['entity_type' => 'ORGANIZATION', 'entity_value' => 'Heijmans B.V.', 'position_start' => 0]];

		$record = $this->service()->classify(objectData: $this->intake(), objectRef: $this->ref())['record'];

		$this->assertSame('00000000-0000-0000-0000-00000000d001', $record['suggestedDossier']);
		$this->assertSame('suggested', $record['status']);

	}//end testAMatchingDossierIsSuggestedNotApplied()

	/**
	 * The service over the real classes.
	 *
	 * @return InboundClassificationService The service.
	 */
	private function service(): InboundClassificationService {
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->store);

		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->method('findEntitiesForFile')->willReturnCallback(fn (int $fileId): array => ($this->entities[$fileId] ?? []));
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($mapper);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(fn (string $app, string $key, string $default): string => $key === InboundClassificationService::TOGGLE ? $this->toggle : $default);

		return new InboundClassificationService(
			classifier: new DocumentTypeClassifier(),
			ranker: new CorrespondentRanker(),
			matcher: new DossierMatcher(),
			results: new ClassificationResultRepository(objectResolver: $resolver),
			sources: new ClassificationSources(entityStats: new FileEntityStatsService(new NullLogger(), $container, $apps), objectResolver: $resolver, logger: new NullLogger()),
			appConfig: $config,
			logger: new NullLogger(),
		);

	}//end service()

	/**
	 * An intake document with OCR text.
	 *
	 * @return array<string, mixed> The fields.
	 */
	private function intake(): array {
		return ['channel' => 'scan', 'status' => 'received', 'file' => 812010, 'fileName' => 'scan-factuur-heijmans.pdf', 'contentText' => self::INVOICE];

	}//end intake()

	/**
	 * The intake document's reference.
	 *
	 * @return array<string, string> The id, register and schema.
	 */
	private function ref(): array {
		return ['id' => 'intake-1', 'register' => 'filinq', 'schema' => 'intakeDocument'];

	}//end ref()
}//end class
