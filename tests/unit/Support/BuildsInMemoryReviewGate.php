<?php

/**
 * A real review gate over in-memory doubles, for controller tests that prove the wiring.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Support
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
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Support;

use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\Review\CertaintyThresholds;
use OCA\Filinq\Service\Review\DocumentReviewRepository;
use OCA\Filinq\Service\Review\DocumentReviewService;
use OCA\Filinq\Service\Review\ReviewRenditionRepository;
use OCP\IAppConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

/**
 * Builds the real DocumentReviewService and CertaintyThresholds over arrays the test fills:
 * relations per file, marks per file, the current rendition per file, the gate mode,
 * certain-from and OpenRegister's floor. Only the stores and OpenRegister are doubles.
 */
trait BuildsInMemoryReviewGate {

	/**
	 * Relations per file id, as OpenRegister serialises them.
	 *
	 * @var array<int, list<array<string, mixed>>>
	 */
	protected array $gateRelations = [];

	/**
	 * Stored marks per file id.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected array $gateMarks = [];

	/**
	 * The current rendition per source file id.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected array $gateRenditions = [];

	/**
	 * The gate mode.
	 *
	 * @var string
	 */
	protected string $gateMode = 'enforced';

	/**
	 * The stored certain-from; 0 makes no finding uncertain.
	 *
	 * @var string
	 */
	protected string $gateCertainFrom = '0';

	/**
	 * OpenRegister's detection floor.
	 *
	 * @var float
	 */
	protected float $gateFloor = 0.5;

	/**
	 * The real review service over the arrays above.
	 *
	 * @return DocumentReviewService The service.
	 */
	protected function inMemoryReviewGate(): DocumentReviewService {
		$relations = fn (int $fileId): array => ($this->gateRelations[$fileId] ?? []);
		$floor = fn (): float => $this->gateFloor;
		$mapper = new class ($relations) {
			/**
			 * Constructor.
			 *
			 * @param \Closure $relations Relations per file.
			 */
			public function __construct(private \Closure $relations) {
			}

			/**
			 * The relations of a file.
			 *
			 * @param int $fileId The file.
			 *
			 * @return array<int, mixed> The relations.
			 */
			public function findByFileId(int $fileId): array {
				return ($this->relations)($fileId);
			}
		};
		$handler = new class ($floor) {
			/**
			 * Constructor.
			 *
			 * @param \Closure $floor The floor.
			 */
			public function __construct(private \Closure $floor) {
			}

			/**
			 * The floor.
			 *
			 * @return float The floor.
			 */
			public function confidenceThreshold(): float {
				return ($this->floor)();
			}
		};

		$locator = $this->createMock(OpenRegisterServiceLocator::class);
		$locator->method('get')->willReturnCallback(
			static fn (string $className): object => match ($className) {
				'OCA\OpenRegister\Db\EntityRelationMapper' => $mapper,
				default => $handler,
			}
		);

		$repository = $this->createMock(DocumentReviewRepository::class);
		$repository->method('findFor')->willReturnCallback(fn (int $fileId): ?array => ($this->gateMarks[$fileId] ?? null));
		$repository->method('save')->willReturnCallback(
			function (array $mark): array {
				$this->gateMarks[(int) $mark['fileId']] = $mark + ['uuid' => 'mark-' . $mark['fileId']];
				return $this->gateMarks[(int) $mark['fileId']];
			}
		);
		$repository->method('delete')->willReturnCallback(
			function (string $uuid): void {
				foreach ($this->gateMarks as $fileId => $mark) {
					if ($mark['uuid'] === $uuid) {
						unset($this->gateMarks[$fileId]);
					}
				}
			}
		);

		$renditions = $this->createMock(ReviewRenditionRepository::class);
		$renditions->method('findFor')->willReturnCallback(fn (int $sourceFileId): ?array => ($this->gateRenditions[$sourceFileId] ?? null));

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default = ''): string => match ($key) {
				DocumentReviewService::GATE_KEY => $this->gateMode,
				CertaintyThresholds::CERTAIN_FROM_KEY => $this->gateCertainFrom,
				default => $default,
			}
		);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		$logger = $this->createMock(LoggerInterface::class);

		return new DocumentReviewService(
			repository: $repository,
			locator: $locator,
			appConfig: $config,
			logger: $logger,
			l10n: $l10n,
			renditions: $renditions,
			thresholds: new CertaintyThresholds(appConfig: $config, locator: $locator, logger: $logger)
		);

	}//end inMemoryReviewGate()
}//end trait
