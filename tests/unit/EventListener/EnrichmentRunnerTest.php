<?php

/**
 * Tests for the classification hook in EnrichmentRunner
 *
 * The runner over the real InboundClassificationService: enrichment of an
 * inbound document leaves a classificationResult suggestion and writes
 * nothing of the classification onto the object (REQ-DDIAC-006).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

require_once __DIR__ . '/../Service/Classification/InboundClassificationFixture.php';

use OCA\Filinq\EventListener\EnrichmentRunner;
use OCA\Filinq\EventListener\ObjectEventServices;
use OCA\Filinq\Kernel\Contracts\PolicyRetroactiveServiceInterface;
use OCA\Filinq\Service\MetadataService;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Tests\Unit\Service\Classification\ClassificationObjectStore;
use OCA\Filinq\Tests\Unit\Service\Classification\InboundClassificationFixture;
use OCA\OpenRegister\Db\ObjectEntity;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * The enrichment path triggers classification; the toggle governs it.
 */
class EnrichmentRunnerTest extends TestCase {
	use InboundClassificationFixture;

	/**
	 * Metadata the enrichment path persisted onto objects, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $enriched = [];

	/**
	 * Fresh stores.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->store = new ClassificationObjectStore();
		$this->enriched = [];

	}//end setUp()

	/**
	 * Enrichment leaves a suggestion and writes no classification field onto the object.
	 *
	 * @return void
	 */
	public function testEnrichmentTriggersClassificationWithoutTouchingFields(): void {
		$runner = new EnrichmentRunner(classification: $this->service());

		$runner->enrichObject(object: $this->object(), services: $this->services(metadata: $this->metadata(metadata: ['language' => 'nl']), settings: $this->settings(enrichment: true), logger: new NullLogger()), logContext: 'new object');

		$this->assertSame([['language' => 'nl']], $this->enriched);
		$this->assertCount(1, $this->store->saves);
		$record = $this->store->saves[0];
		$this->assertSame('factuur', $record['suggestedDocumentType']);
		$this->assertSame('intake-1', $record['objectId']);
		$this->assertSame('7', $record['objectRegister']);
		$this->assertSame('42', $record['objectSchema']);

	}//end testEnrichmentTriggersClassificationWithoutTouchingFields()

	/**
	 * Classification has its own toggle: with every enrichment toggle off it still runs.
	 *
	 * @return void
	 */
	public function testClassificationRunsWhenEnrichmentIsOff(): void {
		$runner = new EnrichmentRunner(classification: $this->service());

		$runner->enrichObject(object: $this->object(), services: $this->services(metadata: $this->metadata(metadata: ['language' => 'nl']), settings: $this->settings(enrichment: false), logger: new NullLogger()), logContext: 'new object');

		$this->assertSame([], $this->enriched);
		$this->assertCount(1, $this->store->saves);

	}//end testClassificationRunsWhenEnrichmentIsOff()

	/**
	 * With classification disabled enrichment is exactly as before and no suggestion exists.
	 *
	 * @return void
	 */
	public function testClassificationDisabledLeavesEnrichmentUntouched(): void {
		$this->toggle = '0';
		$runner = new EnrichmentRunner(classification: $this->service());

		$runner->enrichObject(object: $this->object(), services: $this->services(metadata: $this->metadata(metadata: ['language' => 'nl']), settings: $this->settings(enrichment: true), logger: new NullLogger()), logContext: 'new object');

		$this->assertSame([['language' => 'nl']], $this->enriched);
		$this->assertSame([], $this->store->saves);

	}//end testClassificationDisabledLeavesEnrichmentUntouched()

	/**
	 * An object that is not readable yet is a debug-level skip, not an error.
	 *
	 * @return void
	 */
	public function testAnUnreadableObjectIsSkippedAtDebugLevel(): void {
		$metadata = $this->createMock(MetadataService::class);
		$metadata->method('enhanceMetadata')->willReturn(['language' => 'nl']);
		$metadata->method('saveEnrichedMetadataAsSystem')
			->willThrowException(new DoesNotExistException('not found in any magic table'));
		$logger = new class extends AbstractLogger {
			/**
			 * Levels logged, in order.
			 *
			 * @var array<int, string>
			 */
			public array $levels = [];

			public function log($level, string|\Stringable $message, array $context = []): void {
				unset($message, $context);
				$this->levels[] = (string)$level;
			}
		};
		$runner = new EnrichmentRunner(classification: null);

		$runner->enrichObject(object: $this->object(), services: $this->services(metadata: $metadata, settings: $this->settings(enrichment: true), logger: $logger), logContext: 'new object');

		$this->assertContains(LogLevel::DEBUG, $logger->levels);
		$this->assertNotContains(LogLevel::ERROR, $logger->levels);

	}//end testAnUnreadableObjectIsSkippedAtDebugLevel()

	/**
	 * A failing classification does not cost the object its enrichment.
	 *
	 * @return void
	 */
	public function testAFailingClassificationDoesNotStopEnrichment(): void {
		$this->store = new class extends ClassificationObjectStore {
			/**
			 * Every read fails.
			 *
			 * @param string $registerSlug  The register slug.
			 * @param string $schemaSlug    The schema slug.
			 * @param array  $filters       Field filters.
			 * @param bool   $_rbac         Ignored.
			 * @param bool   $_multitenancy Ignored.
			 *
			 * @return array Never.
			 */
			public function searchObjectsBySlug(string $registerSlug, string $schemaSlug, array $filters = [], bool $_rbac = true, bool $_multitenancy = true) {
				throw new RuntimeException('register unavailable');
			}
		};
		$runner = new EnrichmentRunner(classification: $this->service());

		$runner->enrichObject(object: $this->object(), services: $this->services(metadata: $this->metadata(metadata: ['language' => 'nl']), settings: $this->settings(enrichment: true), logger: new NullLogger()), logContext: 'new object');

		$this->assertSame([['language' => 'nl']], $this->enriched);

	}//end testAFailingClassificationDoesNotStopEnrichment()

	/**
	 * Re-running on an object whose suggestion was decided changes nothing.
	 *
	 * @return void
	 */
	public function testNoSuggestionEverAutoApplies(): void {
		$runner = new EnrichmentRunner(classification: $this->service());
		$runner->classify(object: $this->object(), logger: new NullLogger());
		$uuid = array_key_first($this->store->rows['classificationResult']);
		$this->store->rows['classificationResult'][$uuid]['status'] = 'rejected';

		$runner->classify(object: $this->object(), logger: new NullLogger());
		$runner->enrichObject(object: $this->object(), services: $this->services(metadata: $this->metadata(metadata: []), settings: $this->settings(enrichment: true), logger: new NullLogger()), logContext: 'updated object');

		$this->assertCount(1, $this->store->saves);
		$this->assertSame([], $this->enriched);

	}//end testNoSuggestionEverAutoApplies()

	/**
	 * Without a classification service (the handler's default) the runner enriches as before.
	 *
	 * @return void
	 */
	public function testTheRunnerWorksWithoutAClassificationService(): void {
		(new EnrichmentRunner())->enrichObject(object: $this->object(), services: $this->services(metadata: $this->metadata(metadata: ['language' => 'nl']), settings: $this->settings(enrichment: true), logger: new NullLogger()), logContext: 'new object');

		$this->assertSame([['language' => 'nl']], $this->enriched);
		$this->assertSame([], $this->store->saves);

	}//end testTheRunnerWorksWithoutAClassificationService()

	/**
	 * The handler's default runner resolves the service from the container,
	 * and an instance where it cannot be resolved still enriches.
	 *
	 * @return void
	 */
	public function testTheRunnerResolvesClassificationFromTheContainer(): void {
		$service = $this->service();
		$found = $this->createMock(ContainerInterface::class);
		$found->method('get')->willReturn($service);
		$missing = $this->createMock(ContainerInterface::class);
		$missing->method('get')->willThrowException(new RuntimeException('not registered'));

		(new EnrichmentRunner())->withClassificationFrom(container: $missing)->enrichObject(object: $this->object(), services: $this->services(metadata: $this->metadata(metadata: ['language' => 'nl']), settings: $this->settings(enrichment: true), logger: new NullLogger()), logContext: 'new object');
		$this->assertSame([], $this->store->saves);
		$this->assertSame([['language' => 'nl']], $this->enriched);

		(new EnrichmentRunner())->withClassificationFrom(container: $found)->enrichObject(object: $this->object(), services: $this->services(metadata: $this->metadata(metadata: []), settings: $this->settings(enrichment: true), logger: new NullLogger()), logContext: 'new object');
		$this->assertCount(1, $this->store->saves);

	}//end testTheRunnerResolvesClassificationFromTheContainer()

	/**
	 * The intake document as OpenRegister hands it to the listener.
	 *
	 * @return ObjectEntity The object.
	 */
	private function object(): ObjectEntity {
		$object = new ObjectEntity();
		$object->setUuid('intake-1');
		$object->setRegister('7');
		$object->setSchema('42');
		$object->setObject($this->intake());

		return $object;

	}//end object()

	/**
	 * A MetadataService that enriches with fixed metadata and records what it persists.
	 *
	 * @param array<string, mixed> $metadata What enhanceMetadata() answers.
	 *
	 * @return MetadataService The service.
	 */
	private function metadata(array $metadata): MetadataService {
		$service = $this->createMock(MetadataService::class);
		$service->method('enhanceMetadata')->willReturn($metadata);
		$service->method('saveEnrichedMetadataAsSystem')->willReturnCallback(function (string $objectId, string $register, string $schema, array $fields): array {
			$this->enriched[] = $fields;

			return $fields;
		});

		return $service;

	}//end metadata()

	/**
	 * The feature toggles with every enrichment toggle on or off.
	 *
	 * @param bool $enrichment The enrichment toggles.
	 *
	 * @return SettingsService The settings.
	 */
	private function settings(bool $enrichment): SettingsService {
		$settings = $this->createMock(SettingsService::class);
		$settings->method('getFeatureToggles')->willReturn([
			'enable_language_detection' => $enrichment,
			'enable_keyword_extraction' => $enrichment,
			'enable_topic_classification' => $enrichment,
		]);

		return $settings;

	}//end settings()
	/**
	 * The services an object event hands the runner.
	 *
	 * @param MetadataService $metadata The metadata service.
	 * @param SettingsService $settings The settings.
	 * @param LoggerInterface $logger   The logger.
	 *
	 * @return ObjectEventServices The services.
	 */
	private function services(MetadataService $metadata, SettingsService $settings, LoggerInterface $logger): ObjectEventServices {
		return new ObjectEventServices(
			metadataService: $metadata,
			settingsService: $settings,
			logger: $logger,
			retroactive: $this->createMock(PolicyRetroactiveServiceInterface::class)
		);

	}//end services()
}//end class
