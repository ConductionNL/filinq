<?php

/**
 * Merge Job Repository
 *
 * Reads and writes the `mergeJob` rows in the `filinq` register. The merge
 * itself, the background job and the endpoint all see one job through this
 * class rather than three readings of the same row.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Stores and finds merge jobs.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */
class MergeJobRepository {

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Create one job.
	 *
	 * @param array<string, mixed> $job The job to store.
	 *
	 * @return array<string, mixed> The stored job, with its uuid.
	 *
	 * @throws RuntimeException When the write fails.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function create(array $job): array {
		return $this->write(job: $job, uuid: null);

	}//end create()

	/**
	 * Update one job.
	 *
	 * @param array<string, mixed> $job The job as it stands.
	 * @param string $uuid The job's uuid.
	 *
	 * @return array<string, mixed> The stored job.
	 *
	 * @throws RuntimeException When the write fails.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function save(array $job, string $uuid): array {
		if ($uuid === '') {
			return $this->create(job: $job);
		}

		return $this->write(job: $job, uuid: $uuid);

	}//end save()

	/**
	 * Find one job by its uuid.
	 *
	 * @param string $uuid The uuid.
	 *
	 * @return array<string, mixed>|null The job, or null when there is none.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function find(string $uuid): ?array {
		if ($uuid === '') {
			return null;
		}

		try {
			$object = $this->objectResolver->resolve()->find(
				id: $uuid,
				register: IntakeRepository::REGISTER,
				schema: DocumentMergeService::SCHEMA
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[MergeJobRepository] could not read one merge job',
				context: ['file' => __FILE__, 'line' => __LINE__, 'uuid' => $uuid, 'error' => $e->getMessage()]
			);

			return null;
		}

		if ($object === null) {
			return null;
		}

		return $this->normalise(row: $object);

	}//end find()

	/**
	 * Every job still queued, oldest first.
	 *
	 * @return array<int, array<string, mixed>> The queued jobs.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function findQueued(): array {
		try {
			$results = $this->objectResolver->resolve()->searchObjects(
				query: [
					'@self' => [
						'register' => IntakeRepository::REGISTER,
						'schema' => DocumentMergeService::SCHEMA,
					],
					'status' => DocumentMergeService::STATUS_QUEUED,
				]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[MergeJobRepository] could not read the merge queue',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);

			return [];
		}

		if (is_array($results) === false) {
			return [];
		}

		$jobs = [];
		foreach ($results as $result) {
			$jobs[] = $this->normalise(row: $result);
		}

		return $jobs;

	}//end findQueued()

	/**
	 * Write one job.
	 *
	 * @param array<string, mixed> $job The job.
	 * @param string|null $uuid The uuid to write under, or null to create one.
	 *
	 * @return array<string, mixed> The stored job.
	 *
	 * @throws RuntimeException When the write fails.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	private function write(array $job, ?string $uuid): array {
		unset($job['uuid']);

		try {
			$arguments = [
				'object' => $job,
				'register' => IntakeRepository::REGISTER,
				'schema' => DocumentMergeService::SCHEMA,
			];
			if ($uuid !== null) {
				$arguments['uuid'] = $uuid;
			}

			$stored = $this->objectResolver->resolve()->saveObject(...$arguments);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'Could not store the merge job: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		$normalised = $this->normalise(row: $stored);
		if (($normalised['uuid'] ?? '') === '' && $uuid !== null) {
			$normalised['uuid'] = $uuid;
		}

		return $normalised;

	}//end write()

	/**
	 * Read one OpenRegister row into the flat shape this app uses.
	 *
	 * @param mixed $row The row.
	 *
	 * @return array<string, mixed> The job.
	 *
	 * @spec exclude Shape adapter over an OpenRegister response.
	 */
	private function normalise(mixed $row): array {
		$data = $row;
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$data = $row->jsonSerialize();
		}

		if (is_array($data) === false) {
			return [];
		}

		$fields = $data;
		if (isset($data['object']) === true && is_array($data['object']) === true) {
			$fields = $data['object'];
		}

		$fields['uuid'] = (string)($fields['uuid'] ?? ($data['uuid'] ?? ($data['@self']['id'] ?? ($data['id'] ?? ''))));

		return $fields;

	}//end normalise()
}//end class
