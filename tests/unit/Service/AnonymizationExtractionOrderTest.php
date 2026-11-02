<?php

/**
 * Unit tests for the order and result of an extract-and-detect run.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymizationService;
use OCA\Filinq\Service\CustomDictionaryDetectionRunner;
use OCA\Filinq\Service\FileEntityStatsService;
use OCA\Filinq\Service\LegalBasisProposalService;
use OCA\Filinq\Service\Ocr\OcrExtractionFallback;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Pins the collaborator order, the merged result and the error wrapping.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AnonymizationExtractionOrderTest extends TestCase {
	use BuildsAnonymizationService;

	/**
	 * The calls in the order the collaborators saw them.
	 *
	 * @var array<int, string>
	 */
	private array $log = [];

	/**
	 * A service whose collaborators record every call.
	 *
	 * @param \Throwable|null $extractFails What extractFile throws, if anything.
	 * @param LoggerInterface|null $logger Optional logger.
	 *
	 * @return AnonymizationService
	 */
	private function service(?\Throwable $extractFails = null, ?LoggerInterface $logger = null): AnonymizationService {
		$log = &$this->log;
		$extractor = new class($log, $extractFails) {
			public function __construct(
				private array &$log,
				private ?\Throwable $fails,
			) {
			}

			public function extractFile(int $fileId, bool $force = false, ?array $entityTypes = null): void {
				$this->log[] = 'extractFile ' . $fileId . ' ' . var_export($force, true) . ' ' . json_encode($entityTypes);
				if ($this->fails !== null) {
					throw $this->fails;
				}
			}
		};

		$rows = [['id' => 1, 'type' => 'PERSON', 'value' => 'Jan', 'relation_id' => 11]];
		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->method('findEntitiesForFile')->willReturnCallback(function (int $fileId) use (&$log, $rows) {
			$log[] = 'findEntitiesForFile ' . $fileId;
			return $rows;
		});

		$basis = $this->createMock(LegalBasisProposalService::class);
		$basis->method('getEntityTypeWhitelist')->willReturnCallback(function () use (&$log) {
			$log[] = 'getEntityTypeWhitelist';
			return ['PERSON'];
		});
		$basis->method('applyProposals')->willReturnCallback(function (int $fileId) use (&$log) {
			$log[] = 'applyProposals ' . $fileId;
			return 0;
		});
		$basis->method('enrichEntitiesWithBases')->willReturnCallback(function (array $entities, int $fileId) use (&$log) {
			$log[] = 'enrichEntitiesWithBases ' . count($entities) . ' ' . $fileId;
			return $entities;
		});

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static fn (string $class) => match ($class) {
				'OCA\OpenRegister\Service\TextExtractionService' => $extractor,
				'OCA\OpenRegister\Db\EntityRelationMapper' => $mapper,
				'OCA\Filinq\Service\LegalBasisProposalService' => $basis,
				default => throw new RuntimeException('Unknown service: ' . $class),
			}
		);
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn(['openregister']);

		$ocr = $this->getMockBuilder(OcrExtractionFallback::class)->disableOriginalConstructor()->onlyMethods(['afterExtraction'])->getMock();
		$ocr->method('afterExtraction')->willReturnCallback(function (int $fileId, object $textExtractor, bool $force) use (&$log, $extractor) {
			$log[] = 'afterExtraction ' . $fileId . ' ' . var_export($force, true) . ' ' . var_export($textExtractor === $extractor, true);
			return ['ocrApplied' => true, 'riskLevel' => 'from-ocr'];
		});

		$dictionary = $this->createMock(CustomDictionaryDetectionRunner::class);
		$dictionary->method('run')->willReturnCallback(function (int $fileId, ?array $entityTypeWhitelist) use (&$log) {
			$log[] = 'dictionary ' . $fileId . ' ' . json_encode($entityTypeWhitelist);
			return 'dictionary warning';
		});

		$stats = $this->createMock(FileEntityStatsService::class);
		$stats->method('tryGetRiskLevelService')->willReturn(null);
		$stats->method('getFileRiskLevel')->willReturn('low');

		$deps = ['container' => $container, 'appManager' => $appManager, 'ocrFallback' => $ocr, 'dictionaryRunner' => $dictionary, 'fileEntityStats' => $stats];
		if ($logger !== null) {
			$deps['logger'] = $logger;
		}
		return $this->makeAnonymizationServiceFrom($deps);
	}

	public function testRunsTheStepsInOrderAndMergesTheOcrFieldsLast(): void {
		$result = $this->service()->reExtractAndDetectEntities(fileId: 7);

		$this->assertSame(
			[
				'getEntityTypeWhitelist',
				'extractFile 7 true ["PERSON"]',
				'afterExtraction 7 true true',
				'dictionary 7 ["PERSON"]',
				'findEntitiesForFile 7',
				'applyProposals 7',
				'enrichEntitiesWithBases 1 7',
			],
			$this->log
		);
		$this->assertSame(['entities', 'entityCount', 'riskLevel', 'customDictionaryWarning', 'ocrApplied'], array_keys($result));
		$this->assertSame(1, $result['entityCount']);
		$this->assertSame('from-ocr', $result['riskLevel']);
		$this->assertSame('dictionary warning', $result['customDictionaryWarning']);
		$this->assertCount(1, $result['entities']);
	}

	public function testTheDefaultRunResumesFromCache(): void {
		$this->service()->extractAndDetectEntities(fileId: 8);

		$this->assertContains('extractFile 8 false ["PERSON"]', $this->log);
		$this->assertContains('afterExtraction 8 false true', $this->log);
	}

	public function testAFailureIsLoggedAndWrapped(): void {
		$boom = new RuntimeException('extractor down');
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('error')->with(
			'Failed to extract and detect entities: extractor down',
			['fileId' => 9, 'exception' => $boom]
		);

		try {
			$this->service(extractFails: $boom, logger: $logger)->extractAndDetectEntities(fileId: 9);
			$this->fail('Expected an exception');
		} catch (\Exception $e) {
			$this->assertSame(\Exception::class, get_class($e));
			$this->assertSame('Failed to extract and detect entities: extractor down', $e->getMessage());
			$this->assertSame(0, $e->getCode());
			$this->assertSame($boom, $e->getPrevious());
		}
		$this->assertSame(['getEntityTypeWhitelist', 'extractFile 9 false ["PERSON"]'], $this->log);
	}
}
