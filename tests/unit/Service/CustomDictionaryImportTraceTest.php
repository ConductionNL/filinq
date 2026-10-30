<?php
/**
 * Trace tests for importing dictionary terms: the gate, the reads, the saves and the tally.
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

use OCA\Filinq\Service\CustomDictionaryAccessGate;
use OCA\Filinq\Service\CustomDictionaryPayloadNormaliser;
use OCA\Filinq\Service\CustomDictionaryRepository;
use OCA\Filinq\Service\CustomDictionaryService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Throwable;

/**
 * Every path through importTerms(), compared with JSON recorded from the
 * implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\CustomDictionaryService
 * @uses   \OCA\Filinq\Service\CustomDictionaryPayloadNormaliser
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */
class CustomDictionaryImportTraceTest extends TestCase {

	/**
	 * What the collaborators were asked, in order.
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $calls = [];

	/**
	 * The scenarios: content, CSV or not, and how the world answers.
	 *
	 * @return array<string, array{0: string, 1: bool, 2: array<string, mixed>}>
	 */
	public static function scenarios(): array {
		$rows = static fn (int $n): string => implode("\n", array_map(static fn (int $i): string => 'term ' . $i, range(1, $n)));
		return [
			'not a maintainer' => ["Rotterdam\n", false, ['maintainer' => false]],
			'no such dictionary' => ["Rotterdam\n", false, ['dictionary' => null]],
			'no access to the dictionary' => ["Rotterdam\n", false, ['access' => false]],
			'too many bytes' => [str_repeat('a', 2097153), false, []],
			'exactly the byte limit' => [str_repeat('a', 2097152), false, ['save' => 'quiet']],
			'too many rows' => [$rows(2001), false, []],
			'exactly the row limit' => [$rows(2000), false, ['save' => 'quiet']],
			'a list with blanks and duplicates' => ["Ridderkerk\nBarendrecht\n  \nbarendrecht\n RIDDERKERK \nDelft\nridderkerk", false, []],
			'a csv with labels' => ["Operatie Zilverreiger,Codenaam X\nDossier Karekiet\n,leeg\nzilverreiger\n", true, []],
			'a csv read as a list' => ["Operatie Zilverreiger,Codenaam X\n", false, []],
			'a dictionary without an id' => ["Gouda\nLeiden\n", false, ['dictionary' => ['label' => 'Steden', 'slug' => 'steden']]],
			'nothing to import' => ["\n \n", false, []],
			'a save fails' => ["Gouda\nDelft\n", false, ['save' => 'throw']],
		];
	}//end scenarios()

	/**
	 * The trace matches the recorded one.
	 *
	 * @param string               $content The upload.
	 * @param bool                 $isCsv   Whether it is CSV.
	 * @param array<string, mixed> $world   How the collaborators answer.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testTrace(string $content, bool $isCsv, array $world): void {
		$trace = [];
		try {
			$trace['result'] = $this->service($world)->importTerms(dictionaryUuid: 'dict-a', content: $content, isCsv: $isCsv);
		} catch (Throwable $e) {
			$trace['thrown'] = [$e::class, $e->getMessage()];
		}

		$trace['calls'] = $this->calls;
		if (($world['save'] ?? '') === 'quiet') {
			$trace['calls'] = count($this->calls);
		}

		$path = __DIR__ . '/golden/dictionary-import-trace.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);
	}//end testTrace()

	/**
	 * The service over a recording repository and gate.
	 *
	 * @param array<string, mixed> $world How they answer.
	 *
	 * @return CustomDictionaryService
	 */
	private function service(array $world): CustomDictionaryService {
		$dictionary = ['id' => 'dict-a-id', 'label' => 'Steden', 'slug' => 'steden'];
		if (array_key_exists('dictionary', $world) === true) {
			$dictionary = $world['dictionary'];
		}

		$gate = $this->createMock(CustomDictionaryAccessGate::class);
		$gate->method('assertMayMaintain')->willReturnCallback(
			function () use ($world): void {
				$this->calls[] = ['assertMayMaintain'];
				if (($world['maintainer'] ?? true) === false) {
					throw new RuntimeException('Only dictionary maintainers may change dictionaries.');
				}
			}
		);
		$gate->method('callerHasAccess')->willReturn($world['access'] ?? true);
		$gate->method('selfMeta')->willReturnCallback(static fn (array $record): array => ['slug' => $record['slug'] ?? '']);

		$repository = $this->createMock(CustomDictionaryRepository::class);
		$repository->method('findOne')->willReturnCallback(
			function (string $schema, string $uuid) use ($dictionary): ?array {
				$this->calls[] = ['findOne', $schema, $uuid];
				return $dictionary;
			}
		);
		$repository->method('listBySchema')->willReturnCallback(
			function (string $schema): array {
				$this->calls[] = ['listBySchema', $schema];
				return [
					['id' => 't1', 'value' => ' Ridderkerk ', 'dictionary' => 'dict-a-id'],
					['id' => 't2', 'value' => 'Gouda', 'dictionary' => 'steden'],
					['id' => 't3', 'value' => 'Delft', 'dictionary' => 'other'],
					['id' => 't4', 'dictionary' => 'dict-a-id'],
				];
			}
		);
		$repository->method('save')->willReturnCallback(
			function (string $schema, array $data) use ($world): array {
				$this->calls[] = ['save', $schema, $data];
				if (($world['save'] ?? '') === 'throw' && $data['value'] === 'Delft') {
					throw new RuntimeException('register down');
				}

				return $data;
			}
		);

		return new CustomDictionaryService(
			repository: $repository,
			accessGate: $gate,
			normaliser: new CustomDictionaryPayloadNormaliser(),
			logger: new NullLogger()
		);
	}//end service()
}//end class
