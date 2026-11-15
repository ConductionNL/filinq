<?php

/**
 * Golden test for DomainDirectory::all: the domains, or why there are none.
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
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DomainDirectory;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;

/**
 * Every configuration and search answer through all(), compared with JSON
 * recorded from the implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\DomainDirectory
 */
class DomainDirectoryAllGoldenTest extends TestCase {

	/**
	 * The register, schema and owner settings, and the search answer (rows, 'fails' or 'scalar').
	 *
	 * @return array<string, array{0: array<string, string>, 1: mixed}>
	 */
	public static function scenarios(): array {
		$set = ['domainFolder_register' => ' domeinen ', 'domainFolder_schema' => 'domein', 'domainFolder_owner' => 'admin'];
		return [
			'two domains' => [$set, [['id' => 'a', 'naam' => 'Wonen'], ['object' => ['naam' => 'Werk'], 'uuid' => 'b']]],
			'rows without an id' => [$set, [['naam' => 'Geen id'], 'tekst', ['id' => 'c']]],
			'no domains' => [$set, []],
			'a scalar answer' => [$set, 'scalar'],
			'the read fails' => [$set, 'fails'],
			'no register' => [['domainFolder_register' => '  '] + $set, []],
			'no schema' => [['domainFolder_schema' => ''] + $set, []],
			'no owner' => [['domainFolder_owner' => ' '] + $set, []],
			'nothing set' => [[], []],
			'an untrimmed schema' => [['domainFolder_schema' => ' domein '] + $set, []],
		];
	}//end scenarios()

	/**
	 * The answer, the search and the log match the recorded ones.
	 *
	 * @param array<string, string> $settings The app settings.
	 * @param mixed $answer The search answer.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testAll(array $settings, mixed $answer): void {
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

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => ($app === 'filinq' ? ($settings[$key] ?? $default) : 'wrong app')
		);

		$calls = [];
		$objects = $this->createMock(ObjectService::class);
		$objects->method('searchObjects')->willReturnCallback(function (...$args) use (&$calls, $answer) {
			$calls[] = $args;
			if ($answer === 'fails') {
				throw new RuntimeException('register gone');
			}

			return $answer === 'scalar' ? 'nothing' : $answer;
		});
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		$trace = ['answer' => (new DomainDirectory($resolver, $config, $logger))->all(), 'calls' => $calls, 'logs' => $logger->lines];

		$path = __DIR__ . '/golden/domain-directory-all.json';
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
	}//end testAll()
}//end class
