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
use OCP\DB\IResult;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;

/**
 * REQ-DDMTH-006: null-organisation objects become default-organisation objects, once.
 *
 * @covers \OCA\Filinq\Repair\BackfillDefaultOrganisation
 */
class OrganisationBackfillTest extends TestCase {

	/**
	 * The `_organisation` of each row, per table: a tiny database.
	 *
	 * @var array<string, list<string|null>>
	 */
	private array $rows = [];

	/**
	 * Every statement run, with its parameters.
	 *
	 * @var list<array{0: string, 1: array<int, mixed>}>
	 */
	private array $statements = [];

	/**
	 * The step over Filinq's register (id 4) and the given config.
	 *
	 * @param array<string, string> $config OpenRegister's app config.
	 *
	 * @return BackfillDefaultOrganisation
	 */
	private function step(array $config): BackfillDefaultOrganisation {
		$db = $this->createMock(IDBConnection::class);
		$db->method('tableExists')->willReturnCallback(
			fn (string $table): bool => in_array($table, ['openregister_registers', 'openregister_schemas'], true) || isset($this->rows[$table])
		);
		$db->method('executeQuery')->willReturnCallback(
			function (string $sql): IResult {
				$result = $this->createMock(IResult::class);
				$result->method('fetch')->willReturn(['id' => 4]);
				$result->method('fetchAll')->willReturn(
					[['id' => 10, 'slug' => 'generatedDocument'], ['id' => 11, 'slug' => 'signingAuditEntry'], ['id' => 12, 'slug' => 'dossier'], ['id' => 13, 'slug' => 'otherApps']]
				);
				return $result;
			}
		);
		$db->method('executeStatement')->willReturnCallback(
			function (string $sql, array $params): int {
				$this->statements[] = [$sql, $params];
				preg_match('/`\*PREFIX\*(openregister_table_\d+_\d+)`/', $sql, $match);
				$changed = 0;
				foreach ($this->rows[$match[1]] as $index => $organisation) {
					if ($organisation === null || $organisation === '') {
						$this->rows[$match[1]][$index] = $params[0];
						$changed++;
					}
				}

				return $changed;
			}
		);
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => ($app === 'openregister' ? ($config[$key] ?? $default) : $default)
		);

		return new BackfillDefaultOrganisation(db: $db, appConfig: $appConfig);
	}//end step()

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
	 * The first run stamps every empty row with the default organisation and counts per schema;
	 * the second changes nothing; sealed audit rows and stamped rows are never touched.
	 *
	 * @return void
	 */
	public function testNullOrganisationObjectsBecomeDefaultOrganisationObjectsOnce(): void {
		$this->rows = [
			'openregister_table_4_10' => [null, '', 'org-other'],
			'openregister_table_4_11' => [null, null],
			'openregister_table_4_12' => ['org-default'],
		];
		$step = $this->step(config: ['defaultOrganisation' => 'org-default']);

		$this->assertSame(
			['dossier: 0 object(s) given the default organisation', 'generatedDocument: 2 object(s) given the default organisation'],
			$this->runStep(step: $step)
		);
		$this->assertSame(['org-default', 'org-default', 'org-other'], $this->rows['openregister_table_4_10']);
		$this->assertSame([null, null], $this->rows['openregister_table_4_11']);
		$this->assertStringContainsString("SET _organisation = ? WHERE _organisation IS NULL OR _organisation = ''", $this->statements[0][0]);

		$this->assertSame(
			['dossier: 0 object(s) given the default organisation', 'generatedDocument: 0 object(s) given the default organisation'],
			$this->runStep(step: $step)
		);
	}//end testNullOrganisationObjectsBecomeDefaultOrganisationObjectsOnce()

	/**
	 * The legacy config form names the default too; without any default nothing is changed.
	 *
	 * @return void
	 */
	public function testTheLegacyConfigWorksAndNoDefaultChangesNothing(): void {
		$this->rows = ['openregister_table_4_12' => [null]];
		$this->runStep(step: $this->step(config: ['organisation' => json_encode(['default_organisation' => 'org-legacy'])]));
		$this->assertSame(['org-legacy'], $this->rows['openregister_table_4_12']);

		$this->rows = ['openregister_table_4_12' => [null]];
		$this->statements = [];
		$this->assertSame(
			['OpenRegister has no default organisation, so no Filinq object was given one.'],
			$this->runStep(step: $this->step(config: []))
		);
		$this->assertSame([], $this->statements);
		$this->assertSame([null], $this->rows['openregister_table_4_12']);
	}//end testTheLegacyConfigWorksAndNoDefaultChangesNothing()
}//end class
