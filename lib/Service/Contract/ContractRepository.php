<?php

/**
 * Contract repository
 *
 * Reads and writes contract objects through OpenRegister as the caller, so
 * OpenRegister's authorization decides who reaches a contract: a contract the
 * caller cannot read is not found.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Contract
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Contract;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use RuntimeException;
use Throwable;

/**
 * Contract storage in the filinq register.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Contract
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/specs/contract-lifecycle-management/spec.md#requirement-contract-is-a-first-class-openregister-object-req-ddclm-001
 */
class ContractRepository {

	public const SCHEMA = 'documentContract';

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
	 * A contract the caller can read, or null.
	 *
	 * @param string $uuid The contract uuid.
	 *
	 * @return array<string, mixed>|null The contract fields with `uuid`.
	 *
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-3
	 */
	public function find(string $uuid): ?array {
		if (trim($uuid) === '') {
			return null;
		}

		try {
			$object = $this->objectResolver->resolve()->find(
				id: $uuid,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA
			);
		} catch (Throwable) {
			// Not found and not allowed read the same, so nobody learns a contract exists.
			return null;
		}

		if ($object === null) {
			return null;
		}

		return $this->normalise(row: $object);

	}//end find()

	/**
	 * Save a whole contract (every field carried, never a partial object).
	 *
	 * @param array<string, mixed> $contract The contract fields.
	 * @param string|null          $uuid     The uuid to update, or null to create.
	 *
	 * @return array<string, mixed> The stored contract with `uuid`.
	 *
	 * @throws RuntimeException When OpenRegister refuses the write.
	 *
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-1
	 */
	public function save(array $contract, ?string $uuid=null): array {
		unset($contract['uuid'], $contract['@self'], $contract['id']);
		$arguments = [
			'object'   => $contract,
			'register' => IntakeRepository::REGISTER,
			'schema'   => self::SCHEMA,
		];
		if ($uuid !== null && $uuid !== '') {
			$arguments['uuid'] = $uuid;
		}

		try {
			$stored = $this->objectResolver->resolve()->saveObject(...$arguments);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'The contract could not be saved: ' . $e->getMessage(),
				code: 422,
				previous: $e
			);
		}

		$normalised = $this->normalise(row: $stored);
		if (($normalised['uuid'] ?? '') === '' && $uuid !== null) {
			$normalised['uuid'] = $uuid;
		}

		return $normalised;

	}//end save()

	/**
	 * The object's own fields plus its uuid.
	 *
	 * @param mixed $row An ObjectEntity or an array.
	 *
	 * @return array<string, mixed> The fields.
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

		unset($fields['@self']);
		$fields['uuid'] = (string) ($fields['uuid'] ?? ($data['uuid'] ?? ($data['@self']['id'] ?? ($data['id'] ?? ''))));

		return $fields;

	}//end normalise()
}//end class
