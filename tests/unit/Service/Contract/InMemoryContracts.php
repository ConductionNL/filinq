<?php

/**
 * An in-memory contract store for tests
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Contract
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#4-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Contract;

use OCA\Filinq\Service\Contract\ContractRepository;

/**
 * Keeps whole objects, like OpenRegister: a save replaces the object, so a
 * field the caller did not carry forward is gone. Only seeded rows are
 * readable: anything else is "not yours".
 */
class InMemoryContracts extends ContractRepository {

	/**
	 * Stored objects by uuid.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public array $rows = [];

	/**
	 * Every payload written, in order, without uuid.
	 *
	 * @var list<array<string, mixed>>
	 */
	public array $writes = [];

	/**
	 * No resolver: this store never reaches OpenRegister.
	 */
	public function __construct() {
	}//end __construct()

	/**
	 * Store a row directly.
	 *
	 * @param array<string, mixed> $row The contract.
	 *
	 * @return string The uuid.
	 */
	public function seed(array $row): string {
		$uuid = 'c-' . (count($this->rows) + 1);
		$this->rows[$uuid] = $row;

		return $uuid;

	}//end seed()

	/**
	 * {@inheritDoc}
	 */
	public function find(string $uuid): ?array {
		if (isset($this->rows[$uuid]) === false) {
			return null;
		}

		return $this->rows[$uuid] + ['uuid' => $uuid];

	}//end find()

	/**
	 * {@inheritDoc}
	 */
	public function save(array $contract, ?string $uuid=null): array {
		unset($contract['uuid']);
		$this->writes[] = $contract;
		if ($uuid === null || $uuid === '') {
			$uuid = 'c-' . (count($this->rows) + 1);
		}

		$this->rows[$uuid] = $contract;

		return $contract + ['uuid' => $uuid];

	}//end save()
}//end class
