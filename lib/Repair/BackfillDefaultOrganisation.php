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
 * Filinq does not write OpenRegister's object tables itself: the write is
 * OpenRegister's own organisation adoption (the tooling behind
 * `occ openregister:adopt-organisationless-rows`), scoped here to Filinq's
 * register and given the default organisation. An object cannot be given an
 * organisation through ObjectService here: a repair step has no session user,
 * and OpenRegister only accepts `@self.organisation` from an administrator or
 * a member of that organisation.
 *
 * It touches only the envelope organisation, never a data property, and only
 * where it is empty, so a second run changes nothing and an object already
 * stamped keeps its organisation. Sealed audit rows (`signingAuditEntry`) are
 * never changed. It logs a count per schema; an instance with several
 * organisations reviews that log and reassigns through OpenRegister's own
 * tooling. Without that tooling (an older OpenRegister) it changes nothing.
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
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Container\ContainerInterface;
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
	 * OpenRegister's selection of object tables by register and schema.
	 *
	 * @var string
	 */
	public const SELECTION = 'OCA\\OpenRegister\\Command\\MagicTableSelection';

	/**
	 * OpenRegister's adoption of objects that carry no organisation.
	 *
	 * @var string
	 */
	public const ADOPTION = 'OCA\\OpenRegister\\Service\\Organisation\\OrganisationlessRowAdoption';

	/**
	 * Constructor.
	 *
	 * @param IAppConfig         $appConfig OpenRegister's configured default organisation.
	 * @param ContainerInterface $container Resolves OpenRegister's adoption tooling when it is installed.
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly ContainerInterface $container,
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

		$tooling = $this->tooling();
		if ($tooling === null) {
			$output->info(
				'OpenRegister has no organisation adoption, nothing was changed; run occ openregister:adopt-organisationless-rows --apply after upgrading it.'
			);
			return;
		}

		try {
			$tables = $this->tables(selection: $tooling['selection']);
		} catch (Throwable $e) {
			$output->info('OpenRegister tables are not readable, nothing was changed: ' . $e->getMessage());
			return;
		}

		foreach ($tables as $slug => $table) {
			$changed = (int)$tooling['adoption']->adopt(tableName: $table, organisation: $organisation);
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
	 * OpenRegister's table selection and organisation adoption, or null when it has none.
	 *
	 * @return array{selection: mixed, adoption: mixed}|null
	 */
	private function tooling(): ?array {
		try {
			return [
				'selection' => $this->container->get(self::SELECTION),
				'adoption'  => $this->container->get(self::ADOPTION),
			];
		} catch (Throwable) {
			return null;
		}

	}//end tooling()

	/**
	 * The existing table of every schema of Filinq's register except the sealed ones, by slug.
	 *
	 * @param mixed $selection OpenRegister's MagicTableSelection.
	 *
	 * @return array<string, string> Schema slug => table name.
	 */
	private function tables(mixed $selection): array {
		$targets = $selection->tables(
			registers: $selection->registers(registerRef: IntakeRepositoryInterface::REGISTER),
			schemas: $selection->schemas(schemaRef: null)
		);

		$tables = [];
		foreach ($targets as $target) {
			$slug = (string)$target->schema->getSlug();
			if (in_array($slug, self::SKIPPED, true) === false) {
				$tables[$slug] = (string)$target->tableName;
			}
		}

		ksort($tables);
		return $tables;

	}//end tables()
}//end class
