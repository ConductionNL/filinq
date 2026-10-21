<?php

/**
 * Hand existing evidence rows to the system identity
 *
 * Filinq writes its evidence and audit records with the system as owner, so
 * OpenRegister's owner bypass cannot reopen one. Rows written before that
 * still carry the user who triggered them as owner. This step gives every row
 * of the server-only schemas to the system identity. It is idempotent, touches
 * no other schema, and deletes nothing.
 *
 * @category Repair
 * @package  OCA\Filinq\Repair
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version GIT: <git-id>
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/evidence-rows-owned-by-the-system/specs/consumer-schema-authorization-audit/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Repair;

use OCA\Filinq\Service\IntakeRepository;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Throwable;

/**
 * Sets `_owner` to the system identity on every row of the server-only schemas.
 *
 * @category Repair
 * @package  OCA\Filinq\Repair
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class UnownEvidenceRecords implements IRepairStep {

	/**
	 * The schemas only Filinq's server writes.
	 *
	 * @var list<string>
	 */
	public const SCHEMAS = [
		'documentVersion',
		'publicationLogEntry',
		'prohibitionOverrideAudit',
		'signerRecord',
		'signingSession',
		'signingRequest',
		'anonymizationLink',
		'sanitizationRecord',
		'erasureCertificate',
		'legalHoldCase',
		'subjectErasureRequest',
		'publicationRecord',
		'printJob',
	];

	/**
	 * OpenRegister's system identity when none is configured.
	 *
	 * @var string
	 */
	public const DEFAULT_SYSTEM_OWNER = '__system__';

	/**
	 * Constructor.
	 *
	 * @param IDBConnection $db        The database.
	 * @param IAppConfig    $appConfig OpenRegister's configured system identity.
	 *
	 * @return void
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
	 */
	public function getName(): string {
		return 'Hand existing Filinq evidence records to the system identity';

	}//end getName()

	/**
	 * Rewrite the owner of every row not yet owned by the system.
	 *
	 * @param IOutput $output The upgrade output.
	 *
	 * @return void
	 */
	public function run(IOutput $output): void {
		try {
			$tables = $this->evidenceTables();
		} catch (Throwable $e) {
			$output->info('OpenRegister tables are not readable, nothing was changed: ' . $e->getMessage());
			return;
		}

		$owner = $this->appConfig->getValueString('openregister', 'systemUserId', self::DEFAULT_SYSTEM_OWNER);
		if ($owner === '') {
			$owner = self::DEFAULT_SYSTEM_OWNER;
		}

		foreach ($tables as $slug => $table) {
			$changed = $this->db->executeStatement(
				'UPDATE `*PREFIX*' . $table . '` SET _owner = ? WHERE _owner IS NULL OR _owner <> ?',
				[$owner, $owner]
			);
			$output->info(sprintf('%s: %d row(s) handed to %s', $slug, $changed, $owner));
		}

	}//end run()

	/**
	 * The existing table of each server-only schema in Filinq's register, by slug.
	 *
	 * @return array<string, string> Schema slug => table name without prefix.
	 */
	private function evidenceTables(): array {
		if ($this->db->tableExists('openregister_registers') === false || $this->db->tableExists('openregister_schemas') === false) {
			return [];
		}

		$register = $this->db->executeQuery(
			'SELECT id FROM `*PREFIX*openregister_registers` WHERE slug = ?',
			[IntakeRepository::REGISTER]
		)->fetch();
		if (is_array($register) === false || isset($register['id']) === false) {
			return [];
		}

		$registerId = (string) $register['id'];

		$rows = $this->db->executeQuery(
			'SELECT id, slug FROM `*PREFIX*openregister_schemas` WHERE slug IN (' . implode(',', array_fill(0, count(self::SCHEMAS), '?')) . ')',
			self::SCHEMAS
		)->fetchAll();

		$tables = [];
		foreach ($rows as $row) {
			$table = 'openregister_table_' . $registerId . '_' . $row['id'];
			if ($this->db->tableExists($table) === true) {
				$tables[(string) $row['slug']] = $table;
			}
		}

		return $tables;

	}//end evidenceTables()
}//end class
