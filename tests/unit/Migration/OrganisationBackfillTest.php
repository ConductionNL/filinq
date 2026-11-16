<?php

/**
 * Unit tests for the BackfillDefaultOrganisation repair step.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Migration
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/multi-tenant-hardening/tasks.md#task-2.5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Migration;

use OCA\Filinq\Repair\BackfillDefaultOrganisation;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * REQ-DDMTH-006: null-organisation objects become default-organisation objects, once,
 * through OpenRegister's own organisation adoption.
 *
 * @covers \OCA\Filinq\Repair\BackfillDefaultOrganisation
 */
class OrganisationBackfillTest extends TestCase {

	/**
	 * The `_organisation` of each row, per table: a tiny database the fake adoption writes.
	 *
	 * @var array<string, list<string|null>>
	 */
	public array $rows = [];

	/**
	 * Every adopt() call: table and organisation.
	 *
	 * @var list<array{0: string, 1: string}>
	 */
	public array $adopted = [];

	/**
	 * The register reference the selection was asked for.
	 *
	 * @var list<string|null>
	 */
	public array $registerRefs = [];

	/**
	 * Filinq's register's tables by schema slug, in the selection's own order.
	 *
	 * @var array<string, string>
	 */
	private const TABLES = [
		'otherApps' => 'openregister_table_4_13',
		'generatedDocument' => 'openregister_table_4_10',
		'signingAuditEntry' => 'openregister_table_4_11',
		'dossier' => 'openregister_table_4_12',
	];

	/**
	 * The step over the given config and a container holding the tooling, or failing to.
	 *
	 * @param array<string, string> $config  OpenRegister's app config.
	 * @param bool                  $tooling Whether OpenRegister has its adoption tooling.
	 * @param bool                  $readable Whether the selection can read the tables.
	 *
	 * @return BackfillDefaultOrganisation
	 */
	private function step(array $config, bool $tooling = true, bool $readable = true): BackfillDefaultOrganisation {
		$test = $this;
		$selection = new class($test, $readable) {

			/**
			 * @param OrganisationBackfillTest $test     The test, which records the calls.
			 * @param bool                     $readable Whether the tables can be read.
			 */
			public function __construct(private OrganisationBackfillTest $test, private bool $readable) {
			}

			/**
			 * @param string|null $registerRef The register asked for.
			 *
			 * @return list<string>
			 */
			public function registers(?string $registerRef): array {
				$this->test->registerRefs[] = $registerRef;
				if ($this->readable === false) {
					throw new RuntimeException('register not found');
				}

				return ['register:' . $registerRef];
			}

			/**
			 * @param string|null $schemaRef The schema asked for.
			 *
			 * @return list<string>
			 */
			public function schemas(?string $schemaRef): array {
				return ['all schemas'];
			}

			/**
			 * @param array<int, string> $registers The registers.
			 * @param array<int, string> $schemas   The schemas.
			 *
			 * @return list<object>
			 */
			public function tables(array $registers, array $schemas): array {
				$targets = [];
				foreach (OrganisationBackfillTest::tables() as $slug => $table) {
					$schema = new class($slug) {
						/**
						 * @param string $slug The schema slug.
						 */
						public function __construct(private string $slug) {
						}

						/**
						 * @return string
						 */
						public function getSlug(): string {
							return $this->slug;
						}
					};
					$targets[] = (object)['schema' => $schema, 'tableName' => $table];
				}

				return $targets;
			}
		};
		$adoption = new class($test) {

			/**
			 * @param OrganisationBackfillTest $test The test, whose rows this writes.
			 */
			public function __construct(private OrganisationBackfillTest $test) {
			}

			/**
			 * @param string $tableName    The table.
			 * @param string $organisation The organisation.
			 *
			 * @return int
			 */
			public function adopt(string $tableName, string $organisation): int {
				$this->test->adopted[] = [$tableName, $organisation];
				$changed = 0;
				foreach ($this->test->rows[$tableName] ?? [] as $index => $current) {
					if ($current === null || $current === '') {
						$this->test->rows[$tableName][$index] = $organisation;
						$changed++;
					}
				}

				return $changed;
			}
		};

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static function (string $id) use ($tooling, $selection, $adoption): object {
				if ($tooling === false) {
					throw new RuntimeException('Could not resolve ' . $id);
				}

				return match ($id) {
					BackfillDefaultOrganisation::SELECTION => $selection,
					BackfillDefaultOrganisation::ADOPTION => $adoption,
				};
			}
		);
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => ($app === 'openregister' ? ($config[$key] ?? $default) : $default)
		);

		return new BackfillDefaultOrganisation(appConfig: $appConfig, container: $container);
	}//end step()

	/**
	 * Filinq's register's tables by schema slug.
	 *
	 * @return array<string, string>
	 */
	public static function tables(): array {
		return self::TABLES;
	}//end tables()

	/**
	 * The info lines a run writes.
	 *
	 * @param BackfillDefaultOrganisation $step The step.
	 *
	 * @return list<string>
	 */
	private function runStep(BackfillDefaultOrganisation $step): array {
		$lines = [];
		$output = $this->createMock(IOutput::class);
		$output->method('info')->willReturnCallback(static function (string $line) use (&$lines): void {
			$lines[] = $line;
		});
		$step->run($output);

		return $lines;
	}//end runStep()

	/**
	 * The first run stamps every empty row of Filinq's register with the default organisation and
	 * counts per schema, in slug order; the second changes nothing; sealed audit rows and stamped
	 * rows are never touched.
	 *
	 * @return void
	 */
	public function testNullOrganisationObjectsBecomeDefaultOrganisationObjectsOnce(): void {
		$this->rows = [
			'openregister_table_4_10' => [null, 'org-b', ''],
			'openregister_table_4_11' => [null],
			'openregister_table_4_12' => [null],
			'openregister_table_4_13' => ['org-b'],
		];
		$step = $this->step(config: ['defaultOrganisation' => 'org-default']);

		$this->assertSame(
			[
				'dossier: 1 object(s) given the default organisation',
				'generatedDocument: 2 object(s) given the default organisation',
				'otherApps: 0 object(s) given the default organisation',
			],
			$this->runStep($step)
		);
		$this->assertSame(['filinq'], $this->registerRefs);
		$this->assertSame(
			[
				['openregister_table_4_12', 'org-default'],
				['openregister_table_4_10', 'org-default'],
				['openregister_table_4_13', 'org-default'],
			],
			$this->adopted
		);
		$this->assertSame(['org-default', 'org-b', 'org-default'], $this->rows['openregister_table_4_10']);
		$this->assertSame([null], $this->rows['openregister_table_4_11']);
		$this->assertSame(['org-b'], $this->rows['openregister_table_4_13']);

		$this->assertSame(
			[
				'dossier: 0 object(s) given the default organisation',
				'generatedDocument: 0 object(s) given the default organisation',
				'otherApps: 0 object(s) given the default organisation',
			],
			$this->runStep($step)
		);
	}//end testNullOrganisationObjectsBecomeDefaultOrganisationObjectsOnce()

	/**
	 * The legacy config spelling is read; without any default organisation nothing is adopted.
	 *
	 * @return void
	 */
	public function testTheLegacyConfigWorksAndNoDefaultChangesNothing(): void {
		$this->rows = ['openregister_table_4_12' => [null]];
		$this->runStep($this->step(config: ['organisation' => json_encode(['default_organisation' => ' org-legacy '])]));
		$this->assertSame(['org-legacy'], $this->rows['openregister_table_4_12']);

		$this->rows = ['openregister_table_4_12' => [null]];
		$this->adopted = [];
		$this->registerRefs = [];
		$lines = $this->runStep($this->step(config: []));
		$this->assertSame(['OpenRegister has no default organisation, so no Filinq object was given one.'], $lines);
		$this->assertSame([], $this->adopted);
		$this->assertSame([], $this->registerRefs);
		$this->assertSame([null], $this->rows['openregister_table_4_12']);
	}//end testTheLegacyConfigWorksAndNoDefaultChangesNothing()

	/**
	 * An OpenRegister without the adoption tooling changes nothing and names the command to run.
	 *
	 * @return void
	 */
	public function testWithoutOpenRegistersAdoptionNothingIsChanged(): void {
		$this->rows = ['openregister_table_4_12' => [null]];
		$lines = $this->runStep($this->step(config: ['defaultOrganisation' => 'org-default'], tooling: false));

		$this->assertCount(1, $lines);
		$this->assertStringContainsString('occ openregister:adopt-organisationless-rows --apply', $lines[0]);
		$this->assertSame([], $this->adopted);
		$this->assertSame([null], $this->rows['openregister_table_4_12']);
	}//end testWithoutOpenRegistersAdoptionNothingIsChanged()

	/**
	 * A register the selection cannot read changes nothing and says why.
	 *
	 * @return void
	 */
	public function testUnreadableTablesChangeNothing(): void {
		$this->rows = ['openregister_table_4_12' => [null]];
		$lines = $this->runStep($this->step(config: ['defaultOrganisation' => 'org-default'], readable: false));

		$this->assertSame(['OpenRegister tables are not readable, nothing was changed: register not found'], $lines);
		$this->assertSame([], $this->adopted);
	}//end testUnreadableTablesChangeNothing()
}//end class
