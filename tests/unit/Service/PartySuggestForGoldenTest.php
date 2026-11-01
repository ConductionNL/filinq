<?php

/**
 * Golden test for PartySuggestionService::suggestFor over every entity mix.
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
 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FileEntityStatsService;
use OCA\Filinq\Service\PartySuggestionService;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;

/**
 * Every entity mix through suggestFor(), compared with JSON recorded from the
 * implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\PartySuggestionService
 */
class PartySuggestForGoldenTest extends TestCase {

	/**
	 * The file id and the detected entities: a list of [type, value], 'none' for no mapper, 'fails' for a failing read.
	 *
	 * @return array<string, array{0: int, 1: mixed}>
	 */
	public static function scenarios(): array {
		return [
			'a full block' => [12, [['PERSON', 'Anna de Vries'], ['LOCATION', 'Markt 1'], ['EMAIL', 'anna@example.nl'], ['PHONE_NUMBER', '0612345678']]],
			'a name only' => [12, [['person', ' Anna ']]],
			'the first of each field' => [12, [['PERSON', 'Anna'], ['PERSON', 'Bert'], ['EMAIL', 'b@example.nl']]],
			'a blank first value' => [12, [['PERSON', '  '], ['PERSON', 'Bert']]],
			'unknown types' => [12, [['IBAN', 'NL00BANK0123456789'], ['DATE', '2026-10-31']]],
			'no entities' => [12, []],
			'objects without getters' => [12, ['plain']],
			'no mapper' => [12, 'none'],
			'the read fails' => [12, 'fails'],
			'file id zero' => [0, [['PERSON', 'Anna']]],
			'a negative file id' => [-3, [['PERSON', 'Anna']]],
			'three of four' => [5, [['EMAIL', 'a@example.nl'], ['PHONE_NUMBER', '010'], ['LOCATION', 'Dorp']]],
		];
	}//end scenarios()

	/**
	 * The suggestion, the mapper calls and the log match the recorded ones.
	 *
	 * @param int $fileId The file id.
	 * @param mixed $entities The detected entities.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testSuggestion(int $fileId, mixed $entities): void {
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

		$asked = [];
		$stats = $this->createMock(FileEntityStatsService::class);
		$mapper = null;
		if ($entities !== 'none') {
			$mapper = $this->createMock(EntityRelationMapper::class);
			$mapper->method('findEntitiesForFile')->willReturnCallback(
				function (int $id) use (&$asked, $entities): array {
					$asked[] = $id;
					if ($entities === 'fails') {
						throw new RuntimeException('database gone');
					}

					return array_map(fn ($entity) => $this->relation($entity), $entities);
				}
			);
		}

		$stats->method('tryGetEntityRelationMapper')->willReturn($mapper);
		$service = new PartySuggestionService(
			$stats,
			$this->createMock(DocumentObjectServiceResolver::class),
			$this->createMock(IUserSession::class),
			$logger
		);

		$trace = ['result' => $service->suggestFor($fileId), 'asked' => $asked, 'logs' => $logger->lines];

		$path = __DIR__ . '/golden/party-suggest-for.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, json_decode((string)json_encode($trace, JSON_PRESERVE_ZERO_FRACTION), true));
	}//end testSuggestion()

	/**
	 * One relation double: [type, value] becomes an object with getters, anything else an object without.
	 *
	 * @param mixed $entity The entity description.
	 *
	 * @return object The relation.
	 */
	private function relation(mixed $entity): object {
		if (is_array($entity) === false) {
			return new \stdClass();
		}

		return new class($entity[0], $entity[1]) {
			/**
			 * Constructor.
			 *
			 * @param string $type The type.
			 * @param string $value The value.
			 */
			public function __construct(
				private string $type,
				private string $value,
			) {
			}

			/**
			 * The type.
			 *
			 * @return string
			 */
			public function getType(): string {
				return $this->type;
			}

			/**
			 * The value.
			 *
			 * @return string
			 */
			public function getValue(): string {
				return $this->value;
			}
		};
	}//end relation()
}//end class
