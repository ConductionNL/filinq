<?php

/**
 * Golden test for how PageLayoutService reads stored layouts into plain field arrays.
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
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use JsonSerializable;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\PageLayoutService;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;

/**
 * Every search answer through versionsOf(), compared with JSON recorded from
 * the implementation before search() was split.
 *
 * @covers \OCA\Filinq\Service\PageLayoutService
 */
class PageLayoutSearchGoldenTest extends TestCase {

	/**
	 * The search answer: a list of rows ('json:' rows become serializable objects), 'scalar', or 'fails'.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public static function scenarios(): array {
		return [
			'plain rows' => [[['uuid' => 'a', 'layoutVersion' => 1], ['uuid' => 'b', 'layoutVersion' => 2]]],
			'serializable rows' => [[['json' => ['uuid' => 'c', 'layoutVersion' => 1]], ['json' => ['object' => ['layoutVersion' => 4], 'uuid' => 'd']]]],
			'a wrapped object' => [[['object' => ['name' => 'brief', 'layoutVersion' => 2], 'id' => 'e', '@self' => ['id' => 'f']]]],
			'an object field that is not an array' => [[['object' => 'brief', 'id' => 'g']]],
			'uuid from @self' => [[['object' => ['layoutVersion' => 1], '@self' => ['id' => 'h'], 'id' => 'i']]],
			'uuid from id' => [[['layoutVersion' => 1, 'id' => 7]]],
			'the field uuid wins' => [[['object' => ['uuid' => 'j'], 'uuid' => 'k']]],
			'the outer uuid' => [[['object' => ['x' => 1], 'uuid' => 'l', 'id' => 'm']]],
			'no id at all' => [[['layoutVersion' => 1]]],
			'rows that are not arrays' => [['brief', 7, null, ['uuid' => 'n']]],
			'a serializable row that serializes to a scalar' => [[['json' => 'brief'], ['uuid' => 'o']]],
			'an object without jsonSerialize' => [['object', ['uuid' => 'p']]],
			'no rows' => [[]],
			'a scalar answer' => ['scalar'],
			'the search fails' => ['fails'],
		];
	}//end scenarios()

	/**
	 * The versions, the search call and the log match the recorded ones.
	 *
	 * @param mixed $answer The search answer.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testVersions(mixed $answer): void {
		$logger = new class extends AbstractLogger {
			/**
			 * @var array<int, array<int, mixed>>
			 */
			public array $lines = [];

			/**
			 * Record one line, without the source line number.
			 *
			 * @param mixed $level The level.
			 * @param string|Stringable $message The message.
			 * @param array<mixed> $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|Stringable $message, array $context = []): void {
				unset($context['line']);
				$context['file'] = basename((string)($context['file'] ?? ''));
				$this->lines[] = [$level, (string)$message, $context];
			}
		};

		$calls = [];
		$objects = $this->createMock(ObjectService::class);
		$objects->method('searchObjectsBySlug')->willReturnCallback(function (...$args) use (&$calls, $answer) {
			$calls[] = $args;
			if ($answer === 'fails') {
				throw new RuntimeException('index gone');
			}

			if ($answer === 'scalar') {
				return 'nothing';
			}

			return array_map(fn ($row) => $this->row($row), $answer);
		});
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		$trace = ['versions' => (new PageLayoutService($resolver, $logger))->versionsOf('brief'), 'calls' => $calls, 'logs' => $logger->lines];

		$path = __DIR__ . '/golden/page-layout-search.json';
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
	}//end testVersions()

	/**
	 * One search row: ['json' => x] becomes an object serializing to x, 'object' a plain object.
	 *
	 * @param mixed $row The row description.
	 *
	 * @return mixed The row.
	 */
	private function row(mixed $row): mixed {
		if ($row === 'object') {
			return new \stdClass();
		}

		if (is_array($row) === true && array_key_exists('json', $row) === true) {
			return new class($row['json']) implements JsonSerializable {
				/**
				 * Constructor.
				 *
				 * @param mixed $data What the row serializes to.
				 */
				public function __construct(
					private mixed $data,
				) {
				}

				/**
				 * The serialized row.
				 *
				 * @return mixed
				 */
				public function jsonSerialize(): mixed {
					return $this->data;
				}
			};
		}

		return $row;
	}//end row()
}//end class
