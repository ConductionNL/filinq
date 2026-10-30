<?php
/**
 * Golden runs of the custom dictionary detection pass over chunked text.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use DateTime;
use OCA\Filinq\Service\CustomDictionaryDetectionRunner;
use OCA\Filinq\Service\CustomDictionaryMatchService;
use OCA\Filinq\Service\CustomDictionaryService;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * The relation rows and catalogue calls of one run, compared with JSON
 * recorded from the implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\CustomDictionaryDetectionRunner
 * @uses   \OCA\Filinq\Service\CustomDictionaryMatchService
 * @uses   \OCA\Filinq\Service\DictionaryMatchScan
 */
class CustomDictionaryDetectionGoldenTest extends TestCase {

	/**
	 * What the OpenRegister doubles were asked, in order.
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $calls = [];

	/**
	 * The scenarios: the chunks, as [id, start offset, text].
	 *
	 * @return array<string, array{0: array<int, array{0: int, 1: int, 2: string}>}>
	 */
	public static function scenarios(): array {
		return [
			'two chunks with an overlap region' => [[
				[1, 0, 'Operatie Zilverreiger begint in Ridderkerk.'],
				[2, 30, 'n Ridderkerk. Daarna Barendrecht en operatie zilverreiger.'],
			]],
			'an empty chunk and a chunk without hits' => [[
				[3, 0, ''],
				[4, 0, 'Niets te vinden hier.'],
			]],
			'a known and a new entity, each twice' => [[
				[5, 100, 'Karekiet, Ridderkerk, Karekiet en Ridderkerk.'],
			]],
			'matches that touch without overlapping' => [[
				[7, 0, 'KarekietOperatie ZilverreigerKarekiet'],
			]],
			'a longer term across two dictionaries' => [[
				[6, 0, 'Dossier Karekiet ligt in Ridderkerk-Zuid.'],
			]],
		];
	}//end scenarios()

	/**
	 * The run matches the recorded one.
	 *
	 * @param array<int, array{0: int, 1: int, 2: string}> $chunks The chunks.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testRunMatchesTheRecordedOne(array $chunks): void {
		$warning = $this->runner($chunks)->run(fileId: 77, entityTypeWhitelist: null);
		$trace = ['warning' => $warning, 'calls' => $this->calls];

		$path = __DIR__ . '/golden/custom-dictionary-detection.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);
	}//end testRunMatchesTheRecordedOne()

	/**
	 * The runner over two dictionaries and recording OpenRegister doubles.
	 *
	 * @param array<int, array{0: int, 1: int, 2: string}> $chunks The chunks.
	 *
	 * @return CustomDictionaryDetectionRunner
	 */
	private function runner(array $chunks): CustomDictionaryDetectionRunner {
		$dictionaries = $this->createMock(CustomDictionaryService::class);
		$dictionaries->method('listActiveDictionariesForDetection')->willReturn(
			[
				[
					'matchMode' => 'caseInsensitive',
					'terms' => [['value' => 'Operatie Zilverreiger', 'label' => 'Codenaam'], ['value' => 'Karekiet']],
				],
				[
					'matchMode' => 'wordBoundary',
					'terms' => [['value' => 'Ridderkerk', 'label' => 'Stad'], ['value' => 'Barendrecht'], ['value' => 'Dossier Karekiet', 'label' => 'Dossier']],
				],
			]
		);

		$services = [
			'OCA\OpenRegister\Db\ChunkMapper' => $this->chunkMapper($chunks),
			'OCA\OpenRegister\Db\EntityRelationMapper' => $this->relationMapper(),
			'OCA\OpenRegister\Db\GdprEntityMapper' => $this->entityMapper(),
		];
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(static fn (string $name): object => $services[$name]);

		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		return new CustomDictionaryDetectionRunner(new NullLogger(), $container, $apps, $dictionaries, new CustomDictionaryMatchService());
	}//end runner()

	/**
	 * A chunk mapper double.
	 *
	 * @param array<int, array{0: int, 1: int, 2: string}> $chunks The chunks.
	 *
	 * @return object
	 */
	private function chunkMapper(array $chunks): object {
		$objects = array_map(
			static fn (array $chunk): object => new class($chunk) {
				/**
				 * @param array{0: int, 1: int, 2: string} $chunk The chunk.
				 */
				public function __construct(
					private array $chunk,
				) {
				}

				public function getId(): int {
					return $this->chunk[0];
				}

				public function getStartOffset(): int {
					return $this->chunk[1];
				}

				public function getTextContent(): string {
					return $this->chunk[2];
				}
			},
			$chunks
		);
		return new class($objects) {
			/**
			 * @param array<int, object> $chunks The chunks.
			 */
			public function __construct(
				private array $chunks,
			) {
			}

			/**
			 * @return array<int, object>
			 */
			public function findBySource(string $type, int $id): array {
				return $this->chunks;
			}
		};
	}//end chunkMapper()

	/**
	 * An entity relation mapper double that records deletes and inserts.
	 *
	 * @return object
	 */
	private function relationMapper(): object {
		$record = function (array $call): void {
			$this->calls[] = $call;
		};
		return new class($record) {
			/**
			 * @param \Closure $record Records a call.
			 */
			public function __construct(
				private \Closure $record,
			) {
			}

			/**
			 * @return array<int, object>
			 */
			public function findByFileId(int $fileId): array {
				return [];
			}

			/**
			 * @param array<int, array<string, mixed>> $rows The rows.
			 */
			public function insertBatch(array $rows): void {
				foreach ($rows as $row) {
					$row['createdAt'] = $row['createdAt'] instanceof DateTime;
					($this->record)(['insert', $row]);
				}
			}
		};
	}//end relationMapper()

	/**
	 * A GDPR entity mapper double: Ridderkerk exists as entity 500, new ones get 900 and up.
	 *
	 * @return object
	 */
	private function entityMapper(): object {
		$record = function (array $call): void {
			$this->calls[] = $call;
		};
		return new class($record) {
			private int $next = 900;

			/**
			 * @param \Closure $record Records a call.
			 */
			public function __construct(
				private \Closure $record,
			) {
			}

			public function findOneByValueAndType(string $value, string $type): ?object {
				($this->record)(['find', $value, $type]);
				if ($value !== 'Ridderkerk') {
					return null;
				}

				$entity = new \OCA\OpenRegister\Db\GdprEntity();
				$entity->setId(500);
				return $entity;
			}

			public function insert(object $entity): object {
				($this->record)(['create', $entity->getValue(), $entity->getType(), $entity->getCategory()]);
				$entity->setId($this->next++);
				return $entity;
			}
		};
	}//end entityMapper()
}//end class
