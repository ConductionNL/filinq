<?php

/**
 * Golden test for PostRegisterReader::openPostFor: a unit's undischarged inbound post, oldest first.
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
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use JsonSerializable;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\PostRegisterReader;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;

/**
 * Every unit and register answer through openPostFor(), compared with JSON
 * recorded from the implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\PostRegisterReader
 */
class PostRegisterOpenPostGoldenTest extends TestCase {

	/**
	 * The unit, the register rows ('fails' or a scalar), and the uuids that have an answer ('raise' raises for that uuid).
	 *
	 * @return array<string, array{0: string, 1: mixed, 2: array<string, string>}>
	 */
	public static function scenarios(): array {
		$rows = [
			['id' => 'a', 'registeredAt' => '2026-10-03', 'onderwerp' => 'A'],
			['uuid' => 'b', 'registeredAt' => '2026-10-01'],
			['object' => ['registeredAt' => '2026-10-02'], 'id' => 'c'],
			['id' => 'd'],
			['id' => '  ', 'registeredAt' => '2026-09-01'],
			['registeredAt' => '2026-09-02'],
			'tekst',
			['json' => ['id' => 'e', 'registeredAt' => '2026-09-30']],
		];
		return [
			'open and answered post' => [' team-a ', $rows, ['a' => 'answered']],
			'everything answered' => ['team-a', $rows, ['a' => 'x', 'b' => 'x', 'c' => 'x', 'd' => 'x', 'e' => 'x']],
			'nothing answered' => ['team-a', $rows, []],
			'an answer read that raises' => ['team-a', $rows, ['c' => 'raise']],
			'no rows' => ['team-a', [], []],
			'a scalar answer' => ['team-a', 'nothing', []],
			'the register read fails' => ['team-a', 'fails', []],
			'a blank unit' => ['  ', $rows, []],
		];
	}//end scenarios()

	/**
	 * The open post, the reads and the log match the recorded ones.
	 *
	 * @param string $unit The unit.
	 * @param mixed $rows The register rows.
	 * @param array<string, string> $answered The uuids that have an answer.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testOpenPost(string $unit, mixed $rows, array $answered): void {
		$logger = new class extends AbstractLogger {
			/**
			 * @var array<int, array<int, mixed>>
			 */
			public array $lines = [];

			/**
			 * Record one line.
			 *
			 * @param mixed $level The level.
			 * @param string|Stringable $message The message.
			 * @param array<mixed> $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|Stringable $message, array $context = []): void {
				$this->lines[] = [$level, (string)$message, $context];
			}
		};

		$calls = [];
		$objects = $this->createMock(ObjectService::class);
		$objects->method('searchObjectsBySlug')->willReturnCallback(function (...$args) use (&$calls, $rows) {
			$calls[] = ['search', $args];
			if ($rows === 'fails') {
				throw new RuntimeException('register offline');
			}

			if (is_array($rows) === false) {
				return $rows;
			}

			return array_map(fn ($row) => $this->row($row), $rows);
		});
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		$reader = $this->getMockBuilder(PostRegisterReader::class)
			->setConstructorArgs([$resolver, $logger])
			->onlyMethods(['answersFor'])
			->getMock();
		$reader->method('answersFor')->willReturnCallback(static function (string $uuid) use (&$calls, $answered): array {
			$calls[] = ['answersFor', $uuid];
			if (($answered[$uuid] ?? '') === 'raise') {
				throw new RuntimeException('answers unreadable');
			}

			return isset($answered[$uuid]) === true ? [['id' => 'out-' . $uuid]] : [];
		});

		try {
			$trace = ['open' => $reader->openPostFor($unit)];
		} catch (RuntimeException $e) {
			$trace = ['error' => $e->getMessage()];
		}

		$trace['calls'] = $calls;
		$trace['logs'] = $logger->lines;

		$path = __DIR__ . '/golden/post-register-open-post.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);
	}//end testOpenPost()

	/**
	 * One register row: ['json' => x] becomes an object serializing to x.
	 *
	 * @param mixed $row The row description.
	 *
	 * @return mixed The row.
	 */
	private function row(mixed $row): mixed {
		if (is_array($row) === false || array_key_exists('json', $row) === false) {
			return $row;
		}

		return new class($row['json']) implements JsonSerializable {
			/**
			 * Constructor.
			 *
			 * @param array<string, mixed> $data The serialized form.
			 */
			public function __construct(
				private array $data,
			) {
			}

			/**
			 * The serialized form.
			 *
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return $this->data;
			}
		};
	}//end row()
}//end class
