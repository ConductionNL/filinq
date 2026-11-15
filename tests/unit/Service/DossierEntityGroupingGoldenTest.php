<?php

/**
 * Golden results of grouping one file's anonymised entity rows.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\BaseLabelResolver;
use OCA\Filinq\Service\DossierEntityCollector;
use OCA\Filinq\Service\DossierObjectRepository;
use OCA\Filinq\Service\DossierPlaceholderRanker;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Each file's grouped entities and log lines, compared with JSON recorded
 * before the grouping was split into steps.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class DossierEntityGroupingGoldenTest extends TestCase {

	/**
	 * The scenarios: raw rows and the caller's placeholder map.
	 *
	 * @return array<string, array{0: mixed, 1: array<string, string>}> Name => [rows, map].
	 */
	public static function files(): array {
		$jan = ['entity_id' => 2, 'entity_type' => 'PERSON', 'entity_value' => 'Jan', 'anonymized_value' => '[PERSOON: 1]', 'bases' => ['5.1.2e']];
		return [
			'no rows' => [[], []],
			'the mapper answers no list' => ['broken', []],
			'one row, stored placeholder' => [[$jan], []],
			'the caller map wins' => [[$jan], ['2' => '[PERSOON 7]']],
			'repeated rows count and merge bases' => [[$jan, ['bases' => ['5.1.1c', '5.1.2e']] + $jan, ['bases' => 'x'] + $jan], []],
			'a stale row is omitted and logged' => [[$jan, ['entity_id' => 3, 'entity_type' => 'PERSON', 'entity_value' => 'Piet', 'anonymized_value' => 'Piet'], ['entity_id' => 4, 'entity_type' => 'ORG']], []],
			'same id, other type is another group' => [[$jan, ['entity_type' => 'ORG', 'anonymized_value' => '[ORG: 1]'] + $jan], []],
			'numeric base references' => [[['bases' => [7, '7', 8]] + $jan], []],
			'ordering by placeholder' => [[['entity_id' => 9, 'anonymized_value' => '[PERSOON: 10]'] + $jan, $jan, ['entity_id' => 5, 'entity_type' => 'DATE', 'anonymized_value' => '[DATUM: 2]'] + $jan], []],
		];
	}//end files()

	/**
	 * The result matches the recorded one.
	 *
	 * @param mixed                 $rows The rows the mapper answers.
	 * @param array<string, string> $map  The caller's placeholder map.
	 *
	 * @return void
	 *
	 * @dataProvider files
	 */
	public function testGroupingMatchesTheRecordedOne(mixed $rows, array $map): void {
		$logger = new class extends AbstractLogger {
			/**
			 * The lines logged.
			 *
			 * @var array<int, array<int, mixed>>
			 */
			public array $lines = [];

			/**
			 * Record one line.
			 *
			 * @param mixed             $level   The level.
			 * @param string|Stringable $message The message.
			 * @param array             $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|Stringable $message, array $context = []): void {
				$this->lines[] = [$level, (string)$message, $context];
			}
		};
		$mapper = new class ($rows) {
			/**
			 * Constructor.
			 *
			 * @param mixed $rows The rows to answer.
			 */
			public function __construct(private mixed $rows) {
			}

			/**
			 * The rows.
			 *
			 * @param int $fileId The file.
			 *
			 * @return mixed The rows.
			 */
			public function findAnonymisedEntitiesWithBasesForFile(int $fileId): mixed {
				return $this->rows;
			}
		};
		$repository = $this->createMock(DossierObjectRepository::class);
		$repository->method('entityRelationMapper')->willReturn($mapper);
		$labels = $this->createMock(BaseLabelResolver::class);
		$labels->method('resolve')->willReturnCallback(
			static fn (array $refs): array => array_combine(
				array_map('strval', $refs),
				array_map(static fn ($ref): array => ['name' => 'Label ' . $ref], $refs)
			) ?: []
		);
		$collector = new DossierEntityCollector($repository, $labels, new DossierPlaceholderRanker($repository, $logger), $logger);

		$result = ['entities' => $collector->loadAnonymisedEntitiesForFile(fileId: 42, placeholderMap: $map), 'logs' => $logger->lines];

		$path = __DIR__ . '/golden/dossier-entity-grouping.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $result;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, json_decode((string)json_encode($result), true));
	}//end testGroupingMatchesTheRecordedOne()
}//end class
