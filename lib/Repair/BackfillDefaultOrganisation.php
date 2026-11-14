<?php

/**
 * Backfill the default organisation on legacy Filinq objects
 *
 * Objects written before OpenRegister stamped an organisation, or while
 * Filinq wrote with multitenancy switched off, carry no organisation. Once
 * the organisation boundary is enforced those objects would vanish for the
 * users who own them. This step stamps them with OpenRegister's default
 * organisation (multi-tenant-hardening D6, REQ-DDMTH-006).
 *
 * It touches only the envelope column `_organisation`, never a data property,
 * and only where it is empty, so a second run changes nothing and an object
 * already stamped keeps its organisation. Sealed audit rows
 * (`signingAuditEntry`) are never changed. It logs a count per schema; an
 * instance with several organisations reviews that log and reassigns through
 * OpenRegister's own tooling.
 *
 * @category Repair
 * @package  OCA\Filinq\Repair
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

namespace OCA\Filinq\Repair;

use OCA\Filinq\Kernel\Contracts\IntakeRepositoryInterface;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Throwable;

/**
 * Stamps OpenRegister's default organisation on Filinq objects that carry none.
 *
 * @spec openspec/changes/multi-tenant-hardening/tasks.md#task-2.5
 */
class BackfillDefaultOrganisation implements IRepairStep {

	/**
	 * The schemas whose rows are sealed and never changed.
	 *
	 * @var list<string>
	 */
	public const SKIPPED = ['signingAuditEntry'];

	/**
	 * Constructor.
	 *
	 * @param IDBConnection $db        The database.
	 * @param IAppConfig    $appConfig OpenRegister's configured default organisation.
	 */
	public function __construct(
		private readonly IDBConnection $db,
		private readonly IAppConfig $appConfig,
	) {

	}//end __construct()

	/**
	 * The step's name in the upgrade log.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/multi-tenant-hardening/tasks.md#task-2.5
	 */
	public function getName(): string {
		return 'Give Filinq objects without an organisation the default organisation';

	}//end getName()

	/**
	 * Stamp the default organisation where a Filinq object has none.
	 *
	 * @param IOutput $output The upgrade output.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-tenant-hardening/tasks.md#task-2.5
	 */
	public function run(IOutput $output): void {
		$organisation = $this->defaultOrganisation();
		if ($organisation === null) {
			$output->info('OpenRegister has no default organisation, so no Filinq object was given one.');
			return;
		}

		try {
			$tables = $this->tables();
		} catch (Throwable $e) {
			$output->info('OpenRegister tables are not readable, nothing was changed: ' . $e->getMessage());
			return;
		}

		foreach ($tables as $slug => $table) {
			$changed = $this->db->executeStatement(
				'UPDATE `*PREFIX*' . $table . "` SET _organisation = ? WHERE _organisation IS NULL OR _organisation = ''",
				[$organisation]
			);
			$output->info(sprintf('%s: %d object(s) given the default organisation', $slug, $changed));
		}

	}//end run()

	/**
	 * OpenRegister's default organisation, newer config key first; null when none is set.
	 *
	 * @return string|null The organisation uuid.
	 */
	private function defaultOrganisation(): ?string {
		$organisation = trim($this->appConfig->getValueString('openregister', 'defaultOrganisation', ''));
		if ($organisation !== '') {
			return $organisation;
		}

		$legacy = json_decode($this->appConfig->getValueString('openregister', 'organisation', ''), true);
		$organisation = trim((string)($legacy['default_organisation'] ?? ''));
		if ($organisation === '') {
			return null;
		}

		return $organisation;

	}//end defaultOrganisation()

	/**
	 * The existing table of every schema of Filinq's register except the sealed ones, by slug.
	 *
	 * @return array<string, string> Schema slug => table name without prefix.
	 */
	private function tables(): array {
		if ($this->db->tableExists('openregister_registers') === false || $this->db->tableExists('openregister_schemas') === false) {
			return [];
		}

		$register = $this->db->executeQuery(
			'SELECT id FROM `*PREFIX*openregister_registers` WHERE slug = ?',
			[IntakeRepositoryInterface::REGISTER]
		)->fetch();
		if (is_array($register) === false || isset($register['id']) === false) {
			return [];
		}

		$registerId = (string)$register['id'];
		$tables = [];
		foreach ($this->db->executeQuery('SELECT id, slug FROM `*PREFIX*openregister_schemas`')->fetchAll() as $row) {
			$slug = (string)$row['slug'];
			$table = 'openregister_table_' . $registerId . '_' . $row['id'];
			if (in_array($slug, self::SKIPPED, true) === false && $this->db->tableExists($table) === true) {
				$tables[$slug] = $table;
			}
		}

		ksort($tables);
		return $tables;

	}//end tables()
}//end class
