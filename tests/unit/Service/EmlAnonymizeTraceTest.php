<?php
/**
 * Trace tests for anonymising an EML input into a PDF: calls, written file and answer.
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

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\EmlAnonymizationService;
use OCA\Filinq\Service\EmlPdfAssemblyService;
use OCA\Filinq\Service\EntityDetectionService;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Stringable;
use Throwable;

/**
 * Every path through anonymize(), compared with JSON recorded from the
 * implementation before its parameters were bundled.
 *
 * @covers \OCA\Filinq\Service\EmlAnonymizationService
 * @uses   \OCA\Filinq\Exception\ConversionFailedException
 */
class EmlAnonymizeTraceTest extends TestCase {

	/**
	 * What the doubles were asked, in order.
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $calls = [];

	/**
	 * The scenarios: the source name, whether the output exists, and what the FileService does.
	 *
	 * @return array<string, array{0: string, 1: array<string, mixed>}>
	 */
	public static function scenarios(): array {
		return [
			'a named message' => ['bericht.eml', []],
			'a message without a name' => ['', []],
			'a node that cannot name itself' => ['-', []],
			'an earlier output is replaced' => ['bericht.eml', ['exists' => true]],
			'a dossier scope' => ['bericht.eml', ['scope' => 'dossier', 'dossierKey' => 'd-9']],
			'no placeholder map' => ['bericht.eml', ['noMap' => true]],
			'no anonymise-EML API' => ['bericht.eml', ['noApi' => true]],
			'the API fails' => ['bericht.eml', ['answer' => 'throw']],
			'the API returns no structure' => ['bericht.eml', ['answer' => 'flat']],
		];
	}//end scenarios()

	/**
	 * The trace matches the recorded one.
	 *
	 * @param string               $name  The source file name; '-' for a node without getName().
	 * @param array<string, mixed> $world How the doubles answer.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testTrace(string $name, array $world): void {
		$logger = new class extends AbstractLogger {
			/**
			 * @var array<int, array<int, mixed>>
			 */
			public array $lines = [];

			/**
			 * Record one line.
			 *
			 * @param mixed             $level   The level.
			 * @param string|Stringable $message The message.
			 * @param array<mixed>      $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|Stringable $message, array $context = []): void {
				$this->lines[] = [$level, (string)$message, $context];
			}
		};

		$assembly = $this->createMock(EmlPdfAssemblyService::class);
		$assembly->method('assemble')->willReturnCallback(
			function (object $result, string $sourceFilename): string {
				$this->calls[] = ['assemble', $result->subject, $sourceFilename];
				return '%PDF-redacted';
			}
		);
		$detection = $this->createMock(EntityDetectionService::class);
		$detection->method('parseAnonymizationResult')->willReturnCallback(
			function (mixed $node): array {
				$this->calls[] = ['parse', $node->name];
				return ['fileId' => 501, 'fileName' => $node->name];
			}
		);

		$service = new EmlAnonymizationService(logger: $logger, entityDetection: $detection, emlAssembly: $assembly);
		$trace = [];
		try {
			$answer = $service->anonymize(
				fileId: 42,
				node: $this->source($name, $world),
				fileService: $this->fileService($world),
				mappedEntities: [['entityText' => 'Jan', 'replacement' => '[PERSOON]'], ['entityText' => 'Delft']],
				scope: $world['scope'] ?? 'document',
				dossierKey: $world['dossierKey'] ?? null
			);
			$trace['answer'] = ['resultInfo' => $answer['resultInfo'], 'node' => $answer['node']->name, 'placeholderMap' => $answer['placeholderMap']];
		} catch (ConversionFailedException $e) {
			$trace['thrown'] = [$e->getMessage(), $e->getCode(), $e->getAttempts()];
		}

		$trace['calls'] = $this->calls;
		$trace['logs'] = $logger->lines;

		$path = __DIR__ . '/golden/eml-anonymize-trace.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$key = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$key] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) . "\n");
		}

		$this->assertSame($recorded[$key] ?? null, $trace);
	}//end testTrace()

	/**
	 * The source node and its parent folder, both recording.
	 *
	 * @param string               $name  The file name; '-' for a node without getName().
	 * @param array<string, mixed> $world How the parent answers.
	 *
	 * @return object
	 */
	private function source(string $name, array $world): object {
		$record = function (array $call): void {
			$this->calls[] = $call;
		};
		$parent = new class($record, ($world['exists'] ?? false) === true) {
			/**
			 * @param \Closure $record Records a call.
			 * @param bool     $exists Whether the output name exists.
			 */
			public function __construct(
				private \Closure $record,
				private bool $exists,
			) {
			}

			public function nodeExists(string $name): bool {
				($this->record)(['nodeExists', $name]);
				return $this->exists;
			}

			public function get(string $name): object {
				$record = $this->record;
				return new class($record, $name) {
					public function __construct(
						private \Closure $record,
						private string $name,
					) {
					}

					public function delete(): void {
						($this->record)(['delete', $this->name]);
					}
				};
			}

			public function newFile(string $name, string $content): object {
				($this->record)(['newFile', $name, $content]);
				return (object)['name' => $name];
			}
		};

		if ($name === '-') {
			return new class($parent) {
				public function __construct(
					private object $parent,
				) {
				}

				public function getParent(): object {
					return $this->parent;
				}
			};
		}

		return new class($parent, $name) {
			public function __construct(
				private object $parent,
				private string $name,
			) {
			}

			public function getName(): string {
				return $this->name;
			}

			public function getParent(): object {
				return $this->parent;
			}
		};
	}//end source()

	/**
	 * An OpenRegister FileService double.
	 *
	 * @param array<string, mixed> $world How it answers.
	 *
	 * @return object
	 */
	private function fileService(array $world): object {
		if (($world['noApi'] ?? false) === true) {
			return new \stdClass();
		}

		$record = function (array $call): void {
			$this->calls[] = $call;
		};
		$answer = $world['answer'] ?? 'ok';
		if (($world['noMap'] ?? false) === true) {
			return new class($record) {
				public function __construct(
					private \Closure $record,
				) {
				}

				public function anonymizeEmlStructured(mixed $node, array $entities, string $scope, ?string $dossierKey): object {
					($this->record)(['api', count($entities), $scope, $dossierKey]);
					return (object)['subject' => 'Onderwerp'];
				}
			};
		}

		return new class($record, $answer) {
			public function __construct(
				private \Closure $record,
				private string $answer,
			) {
			}

			public function anonymizeEmlStructured(mixed $node, array $entities, string $scope, ?string $dossierKey): mixed {
				($this->record)(['api', count($entities), $scope, $dossierKey]);
				if ($this->answer === 'throw') {
					throw new \RuntimeException('OR exploded');
				}

				if ($this->answer === 'flat') {
					return ['subject' => 'Onderwerp'];
				}

				return (object)['subject' => 'Onderwerp'];
			}

			/**
			 * @return array<string, string>
			 */
			public function getLastPlaceholderMap(): array {
				($this->record)(['placeholderMap']);
				return ['Jan' => '[PERSOON 1]'];
			}
		};
	}//end fileService()
}//end class
