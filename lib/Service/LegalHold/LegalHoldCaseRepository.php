<?php

/**
 * Reads and writes legalHoldCase objects.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\LegalHold
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\LegalHold;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use RuntimeException;
use Throwable;

/**
 * The hold register's storage.
 *
 * The schema grants admins only, so every read and write here passes `_rbac:
 * false`; LegalHoldCaseService calls this only after LegalHoldAuthority.
 */
class LegalHoldCaseRepository {

	/**
	 * The schema the cases live in.
	 *
	 * @var string
	 */
	public const SCHEMA = 'legalHoldCase';

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
	 * One case, or null when there is none under this id.
	 *
	 * @param string $uuid The case uuid.
	 *
	 * @return array<string, mixed>|null The case, with `uuid`.
	 *
	 * @throws RuntimeException When the register cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.1
	 */
	public function find(string $uuid): ?array {
		if ($uuid === '') {
			return null;
		}

		foreach ($this->search(filters: []) as $case) {
			if ($case['uuid'] === $uuid) {
				return $case;
			}
		}

		return null;

	}//end find()

	/**
	 * The cases matching every filter.
	 *
	 * The register's filter narrows; the match here decides, so a search that
	 * ignored a filter cannot hand back a case that does not match.
	 *
	 * @param array<string, string> $filters Field => exact value (status, holdType, custodian).
	 *
	 * @return array<int, array<string, mixed>> The cases, each with `uuid`.
	 *
	 * @throws RuntimeException When the register cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.2
	 */
	public function search(array $filters): array {
		try {
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: $filters,
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The hold register could not be read: ' . $e->getMessage(), code: 0, previous: $e);
		}

		$cases = [];
		foreach ((array) $results as $result) {
			$case = $this->normalise(row: $result);
			foreach ($filters as $field => $value) {
				if ((string) ($case[$field] ?? '') !== (string) $value) {
					continue 2;
				}
			}

			if ($case['uuid'] !== '') {
				$cases[] = $case;
			}
		}

		return $cases;

	}//end search()

	/**
	 * Write a case, whole: every field is carried, so a save never drops one.
	 *
	 * @param array<string, mixed> $case The full case, `uuid` set for an update.
	 *
	 * @return array<string, mixed> The case as stored, with `uuid`.
	 *
	 * @throws RuntimeException When OpenRegister refuses it.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.2
	 */
	public function save(array $case): array {
		$uuid = (string) ($case['uuid'] ?? '');
		$existing = null;
		if ($uuid !== '') {
			$existing = $uuid;
		}

		unset($case['uuid'], $case['@self'], $case['id']);
		try {
			$stored = $this->objectResolver->resolve()->saveObject(
				object: $case,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA,
				uuid: $existing,
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The hold case could not be saved: ' . $e->getMessage(), code: 0, previous: $e);
		}

		$saved = $this->normalise(row: $stored);
		if ($saved['uuid'] === '') {
			throw new RuntimeException(message: 'OpenRegister stored the hold case without a uuid.');
		}

		return array_merge($case, $saved);

	}//end save()

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
