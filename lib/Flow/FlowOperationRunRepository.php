<?php

/**
 * Filinq Flow Operation Run Repository
 *
 * Reads and writes the `flowOperationRun` processing-log objects in
 * OpenRegister. The schema refuses every write through the API (create and
 * update are empty in its authorization block), so only this server-side
 * repository writes a run, and it does so without the caller's RBAC.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-2.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Flow;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\ReadsStoredObjectRows;
use OCA\Filinq\Service\SlugSearch;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use RuntimeException;
use Throwable;

/**
 * The store of Flow operation runs.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-2.3
 */
class FlowOperationRunRepository {
	use ReadsStoredObjectRows;

	/**
	 * The schema slug of a run.
	 */
	public const SCHEMA = 'flowOperationRun';

	/**
	 * The run states in which a run is still in flight.
	 */
	public const IN_FLIGHT = [FlowRunStatus::QUEUED, FlowRunStatus::RUNNING];

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's object service.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
	) {

	}//end __construct()

	/**
	 * Store a run: a new one without `uuid`, the existing one with it.
	 *
	 * @param array<string, mixed> $run The whole run.
	 *
	 * @return array<string, mixed> The stored run, `uuid` included.
	 *
	 * @throws RuntimeException When OpenRegister refuses or is absent.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.3
	 */
	public function save(array $run): array {
		$uuid = (string) ($run['uuid'] ?? '');
		unset($run['uuid']);
		$targetUuid = null;
		if ($uuid !== '') {
			$targetUuid = $uuid;
		}

		try {
			$stored = $this->objectResolver->resolve()->saveObject(
				object: $run,
				options: new ObjectSaveOptions(
					target: new ObjectFindTarget(register: IntakeRepository::REGISTER, schema: self::SCHEMA),
					uuid: $targetUuid
				),
				_rbac: false,
				_multitenancy: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'Could not store the Flow operation run: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		$fields = $this->storedFields(row: $stored);
		if ($fields === []) {
			// A store that answers nothing usable still stored what we sent.
			$fields = $run;
		}

		if (($fields['uuid'] ?? '') === '' && $uuid !== '') {
			$fields['uuid'] = $uuid;
		}

		return $fields;

	}//end save()

	/**
	 * Whether a run of this operation on this file is already queued or running.
	 *
	 * @param string $operation The operation key.
	 * @param int    $fileId    The file id.
	 *
	 * @return bool True when a run is in flight.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.3
	 */
	public function hasRunInFlight(string $operation, int $fileId): bool {
		foreach ($this->search(filters: ['operation' => $operation, 'fileId' => $fileId]) as $run) {
			if (($run['operation'] ?? '') === $operation
				&& (int) ($run['fileId'] ?? 0) === $fileId
				&& in_array($run['status'] ?? '', self::IN_FLIGHT, true) === true
			) {
				return true;
			}
		}

		return false;

	}//end hasRunInFlight()

	/**
	 * Whether a Filinq Flow operation produced this file.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return bool True when a run records it as its produced file.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.4
	 */
	public function isProducedFile(int $fileId): bool {
		if ($fileId <= 0) {
			return false;
		}

		foreach ($this->search(filters: ['producedFileId' => $fileId]) as $run) {
			if ((int) ($run['producedFileId'] ?? 0) === $fileId) {
				return true;
			}
		}

		return false;

	}//end isProducedFile()

	/**
	 * The stored runs matching the filters. The filter is the store's, the
	 * match is the caller's: a search that ignores a filter cannot widen it.
	 *
	 * @param array<string, mixed> $filters The filters.
	 *
	 * @return array<int, array<string, mixed>> The runs.
	 *
	 * @throws RuntimeException When the store cannot be read.
	 */
	private function search(array $filters): array {
		try {
			$results = (new SlugSearch(objects: $this->objectResolver->resolve(), _rbac: false, _multitenancy: false))->search(
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA,
				filters: $filters
			);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'The Flow operation runs could not be read: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		$runs = [];
		foreach ((array) $results as $result) {
			$runs[] = $this->storedFields(row: $result);
		}

		return $runs;

	}//end search()
}//end class
