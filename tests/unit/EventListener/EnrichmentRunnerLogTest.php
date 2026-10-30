<?php

/**
 * What EnrichmentRunner::enrichObject logs and persists, per outcome.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\EnrichmentRunner;
use OCA\Filinq\Service\MetadataService;
use OCA\Filinq\Service\SettingsService;
use OCA\OpenRegister\Db\ObjectEntity;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Throwable;

/**
 * The enrichment run's log lines and its system write.
 *
 * @covers \OCA\Filinq\EventListener\EnrichmentRunner
 */
class EnrichmentRunnerLogTest extends TestCase {

	/**
	 * The saveEnrichedMetadataAsSystem calls: [objectId, register, schema, fields].
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $saves = [];

	/**
	 * What enhanceMetadata received.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $enhanced = [];

	/**
	 * An enabled run with metadata logs the start, writes as system and logs the fields.
	 *
	 * @return void
	 */
	public function testAnEnrichedObjectIsWrittenAsSystemAndLogged(): void {
		$logger = $this->logger();

		$this->enrich(metadata: $this->metadata(answer: ['language' => 'nl', 'keywords' => ['a']]), enabled: true, logger: $logger);

		$this->assertSame([['body' => 'text']], $this->enhanced);
		$this->assertSame([['intake-1', '7', '42', ['language' => 'nl', 'keywords' => ['a']]]], $this->saves);
		$this->assertSame(
			[
				['info', 'Filinq: Processing new object', ['objectId' => 'intake-1', 'schemaId' => '42', 'registerId' => '7']],
				['info', 'Filinq: Metadata enrichment completed for new object', ['objectId' => 'intake-1', 'enrichedFields' => ['language', 'keywords']]],
			],
			$logger->lines
		);

	}//end testAnEnrichedObjectIsWrittenAsSystemAndLogged()

	/**
	 * With every toggle off nothing is enhanced or written.
	 *
	 * @return void
	 */
	public function testDisabledEnrichmentOnlyLogsTheStart(): void {
		$logger = $this->logger();

		$this->enrich(metadata: $this->metadata(answer: ['language' => 'nl']), enabled: false, logger: $logger);

		$this->assertSame([], $this->enhanced);
		$this->assertSame([], $this->saves);
		$this->assertCount(1, $logger->lines);

	}//end testDisabledEnrichmentOnlyLogsTheStart()

	/**
	 * Empty metadata is not written and not reported as completed.
	 *
	 * @return void
	 */
	public function testEmptyMetadataIsNotWritten(): void {
		$logger = $this->logger();

		$this->enrich(metadata: $this->metadata(answer: []), enabled: true, logger: $logger);

		$this->assertCount(1, $this->enhanced);
		$this->assertSame([], $this->saves);
		$this->assertCount(1, $logger->lines);

	}//end testEmptyMetadataIsNotWritten()

	/**
	 * A failed write is an error naming the context and the cause.
	 *
	 * @return void
	 */
	public function testAFailedWriteIsLoggedAsAnError(): void {
		$logger = $this->logger();

		$this->enrich(metadata: $this->metadata(answer: ['language' => 'nl'], failWith: new RuntimeException('disk full')), enabled: true, logger: $logger);

		$this->assertSame(
			['error', 'Filinq: Failed to enrich metadata for new object', ['objectId' => 'intake-1', 'exception' => 'disk full']],
			$logger->lines[1]
		);
		$this->assertCount(2, $logger->lines);

	}//end testAFailedWriteIsLoggedAsAnError()

	/**
	 * An unreadable object is a debug line naming the context and the cause.
	 *
	 * @return void
	 */
	public function testAnUnreadableObjectIsADebugLine(): void {
		$logger = $this->logger();

		$this->enrich(metadata: $this->metadata(answer: ['language' => 'nl'], failWith: new DoesNotExistException('gone')), enabled: true, logger: $logger);

		$this->assertSame(
			['debug', 'Filinq: Skipped metadata enrichment for new object, the object is not readable', ['objectId' => 'intake-1', 'exception' => 'gone']],
			$logger->lines[1]
		);
		$this->assertCount(2, $logger->lines);

	}//end testAnUnreadableObjectIsADebugLine()

	/**
	 * Run enrichObject on the fixed object with no classification service.
	 *
	 * @param MetadataService $metadata The metadata service.
	 * @param bool            $enabled  The enrichment toggles.
	 * @param AbstractLogger  $logger   The logger.
	 *
	 * @return void
	 */
	private function enrich(MetadataService $metadata, bool $enabled, AbstractLogger $logger): void {
		$object = new ObjectEntity();
		$object->setUuid('intake-1');
		$object->setRegister('7');
		$object->setSchema('42');
		$object->setObject(['body' => 'text']);

		$settings = $this->createMock(SettingsService::class);
		$settings->method('getFeatureToggles')->willReturn([
			'enable_language_detection' => $enabled,
			'enable_keyword_extraction' => $enabled,
			'enable_topic_classification' => $enabled,
		]);

		(new EnrichmentRunner(classification: null))->enrichObject($object, $metadata, $settings, $logger, 'new object');

	}//end enrich()

	/**
	 * A MetadataService that answers fixed metadata and records its calls.
	 *
	 * @param array<string, mixed> $answer   What enhanceMetadata() answers.
	 * @param Throwable|null       $failWith What the system write throws, if anything.
	 *
	 * @return MetadataService The service.
	 */
	private function metadata(array $answer, ?Throwable $failWith = null): MetadataService {
		$service = $this->createMock(MetadataService::class);
		$service->method('enhanceMetadata')->willReturnCallback(function (array $data) use ($answer): array {
			$this->enhanced[] = $data;
			return $answer;
		});
		$service->method('saveEnrichedMetadataAsSystem')->willReturnCallback(function (...$arguments) use ($failWith): array {
			if ($failWith !== null) {
				throw $failWith;
			}

			$this->saves[] = $arguments;
			return $arguments[3];
		});

		return $service;

	}//end metadata()

	/**
	 * A logger that keeps every line.
	 *
	 * @return AbstractLogger The logger, with its lines in `lines`.
	 */
	private function logger(): AbstractLogger {
		return new class extends AbstractLogger {
			/**
			 * Lines logged: [level, message, context].
			 *
			 * @var array<int, array{0: string, 1: string, 2: array<string, mixed>}>
			 */
			public array $lines = [];

			public function log($level, string|\Stringable $message, array $context = []): void {
				$this->lines[] = [(string)$level, (string)$message, $context];
			}
		};

	}//end logger()
}//end class
