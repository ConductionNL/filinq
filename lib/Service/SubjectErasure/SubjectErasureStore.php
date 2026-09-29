<?php

/**
 * Storage of erasure requests and their certificates.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SubjectErasure
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-1.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SubjectErasure;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use OCA\OpenRegister\Db\ObjectEntity;
use RuntimeException;
use Throwable;

/**
 * The request and certificate schemas grant the erasure groups only, so every
 * read and write here passes `_rbac: false`; SubjectErasureService calls this
 * only after SubjectErasureAuthority.
 */
class SubjectErasureStore {

	/**
	 * The request schema.
	 *
	 * @var string
	 */
	public const REQUEST = 'subjectErasureRequest';

	/**
	 * The certificate schema.
	 *
	 * @var string
	 */
	public const CERTIFICATE = 'erasureCertificate';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
	) {

	}//end __construct()

	/**
	 * Every request, or those matching every filter.
	 *
	 * @param string               $schema  One of REQUEST, CERTIFICATE.
	 * @param array<string, string> $filters Field => exact value.
	 *
	 * @return array<int, array<string, mixed>> The rows, each with `uuid`.
	 *
	 * @throws RuntimeException When the register cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-1.1
	 */
	public function search(string $schema, array $filters = []): array {
		try {
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: $schema,
				filters: $filters,
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The erasure register could not be read: ' . $e->getMessage(), code: 0, previous: $e);
		}

		$rows = [];
		foreach ((array) $results as $result) {
			$row = $this->normalise(row: $result);
			foreach ($filters as $field => $value) {
				if ((string) ($row[$field] ?? '') !== (string) $value) {
					continue 2;
				}
			}

			if ($row['uuid'] !== '') {
				$rows[] = $row;
			}
		}

		return $rows;

	}//end search()

	/**
	 * One row by uuid, or null.
	 *
	 * @param string $schema One of REQUEST, CERTIFICATE.
	 * @param string $uuid   The uuid.
	 *
	 * @return array<string, mixed>|null The row, with `uuid`.
	 *
	 * @throws RuntimeException When the register cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-1.1
	 */
	public function find(string $schema, string $uuid): ?array {
		if ($uuid === '') {
			return null;
		}

		foreach ($this->search(schema: $schema) as $row) {
			if ($row['uuid'] === $uuid) {
				return $row;
			}
		}

		return null;

	}//end find()

	/**
	 * Write a row, whole.
	 *
	 * @param string               $schema One of REQUEST, CERTIFICATE.
	 * @param array<string, mixed> $row    The full row, `uuid` set for an update.
	 *
	 * @return array<string, mixed> The row as stored, with `uuid`.
	 *
	 * @throws RuntimeException When OpenRegister refuses it.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-1.1
	 */
	public function save(string $schema, array $row): array {
		$uuid = (string) ($row['uuid'] ?? '');
		$existing = null;
		if ($uuid !== '') {
			$existing = $uuid;
		}

		unset($row['uuid'], $row['@self'], $row['id']);
		try {
			$stored = $this->objectResolver->resolve()->saveObject(
				object: $row,
				register: IntakeRepository::REGISTER,
				schema: $schema,
				uuid: $existing,
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The ' . $schema . ' could not be saved: ' . $e->getMessage(), code: 0, previous: $e);
		}

		$saved = $this->normalise(row: $stored);
		if ($saved['uuid'] === '') {
			throw new RuntimeException(message: 'OpenRegister stored the ' . $schema . ' without a uuid.');
		}

		return array_merge($row, $saved);

	}//end save()

	/**
	 * The stored request object, for the ids an audit entry sits on.
	 *
	 * @param string $uuid The request uuid.
	 *
	 * @return ObjectEntity|null The object, or null when it cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-4.2
	 */
	public function storedRequest(string $uuid): ?ObjectEntity {
		if ($uuid === '') {
			return null;
		}

		try {
			$entity = $this->objectResolver->resolve()->find(
				id: $uuid,
				register: IntakeRepository::REGISTER,
				schema: self::REQUEST,
				_rbac: false,
				_multitenancy: false
			);
		} catch (Throwable) {
			return null;
		}

		if ($entity instanceof ObjectEntity === false) {
			return null;
		}

		return $entity;

	}//end storedRequest()

	/**
	 * Flatten an OpenRegister row to its fields plus `uuid`.
	 *
	 * @param mixed $row A search or save result.
	 *
	 * @return array<string, mixed> The fields.
	 */
	private function normalise(mixed $row): array {
		if (is_object($row) === true && method_exists($row, 'getObject') === true && method_exists($row, 'getUuid') === true) {
			return array_merge((array) $row->getObject(), ['uuid' => (string) $row->getUuid()]);
		}

		$data = $row;
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$data = $row->jsonSerialize();
		}

		if (is_array($data) === false) {
			return ['uuid' => ''];
		}

		$fields = $data;
		if (isset($data['object']) === true && is_array($data['object']) === true) {
			$fields = $data['object'];
		}

		unset($fields['@self']);
		$fields['uuid'] = (string) ($fields['uuid'] ?? ($data['uuid'] ?? ($data['@self']['id'] ?? ($data['id'] ?? ''))));

		return $fields;

	}//end normalise()
}//end class
