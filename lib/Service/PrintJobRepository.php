<?php

/**
 * Print Job Repository
 *
 * Reads and writes the `printJob` rows in the `filinq` register. The service,
 * the background job, the endpoint and the repair step all see one job through
 * this class.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/print-preview/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Kernel\Contracts\PrintJobRepositoryInterface;
use RuntimeException;
use Throwable;

/**
 * Stores and finds print jobs.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/print-preview/spec.md
 */
class PrintJobRepository implements PrintJobRepositoryInterface {
	use ReadsStoredObjectRows;

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
	) {

	}//end __construct()

	/**
	 * Write one job, creating it when it has no uuid yet.
	 *
	 * @param array<string, mixed> $job  The job
	 * @param string|null          $uuid The uuid to write under, or null to let OpenRegister pick one
	 *
	 * @return array<string, mixed> The stored job, with its uuid.
	 *
	 * @throws RuntimeException When the write fails.
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function save(array $job, ?string $uuid = null): array {
		unset($job['uuid']);

		if ($uuid === '') {
			$uuid = null;
		}

		try {
			$stored = $this->objectResolver->resolve()->saveObject(
				object: $job,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA,
				uuid: $uuid,
				_rbac: false,
				_unowned: true
			);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'Could not store the print job: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		$normalised = $this->storedFields(row: $stored);
		if (($normalised['uuid'] ?? '') === '' && $uuid !== null) {
			$normalised['uuid'] = $uuid;
		}

		return $normalised;

	}//end save()

	/**
	 * Find one job by its uuid.
	 *
	 * @param string $uuid The uuid
	 *
	 * @return array<string, mixed>|null The job, or null when there is none.
	 *
	 * @throws RuntimeException When the read failed, as opposed to finding nothing.
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function find(string $uuid): ?array {
		if ($uuid === '') {
			return null;
		}

		try {
			$object = $this->objectResolver->resolve()->find(
				id: $uuid,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA,
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'The print job could not be read: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		if ($object === null) {
			return null;
		}

		return $this->storedFields(row: $object);

	}//end find()

	/**
	 * Every job one user sent, newest first.
	 *
	 * @param string $userId The user
	 *
	 * @return array<int, array<string, mixed>> The user's jobs.
	 *
	 * @throws RuntimeException When the read failed, as opposed to finding nothing.
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.3
	 */
	public function findForUser(string $userId): array {
		if ($userId === '') {
			return [];
		}

		try {
			// Slugs go through searchObjectsBySlug: searchObjects answers a
			// slug with zero rows and no error.
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: ['requestedBy' => $userId],
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'The print jobs could not be read: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		$jobs = [];
		foreach ((array) $results as $result) {
			$job = $this->storedFields(row: $result);
			// The filter is the search's; the owner check is ours, so a search
			// that ignored the filter still lists nobody else's job.
			if (($job['requestedBy'] ?? '') === $userId) {
				$jobs[] = $job;
			}
		}

		usort(
			$jobs,
			static fn (array $a, array $b): int => strcmp((string) ($b['requestedAt'] ?? ''), (string) ($a['requestedAt'] ?? ''))
		);

		return $jobs;

	}//end findForUser()
}//end class
