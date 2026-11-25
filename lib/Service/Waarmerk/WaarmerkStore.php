<?php

/**
 * Waarmerk Store
 *
 * The write-once waarmerk records in OpenRegister.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Waarmerk
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Waarmerk;
use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\ReadsStoredObjectRows;
use OCA\Filinq\Service\SystemContextSeam;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCA\OpenRegister\Kernel\Contracts\Value\SaveActor;
use Throwable;

/**
 * Creates, reads and revokes `waarmerk` objects.
 *
 * Evidence fields are write-once (GH #289): create() refuses an existing
 * uuid, and revoke() changes nothing but the revocation fields. There is no
 * update or delete method.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Waarmerk
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
 */
class WaarmerkStore {
	use ReadsStoredObjectRows;

	public const REGISTER = 'filinq';

	public const SCHEMA = 'waarmerk';

	/**
	 * The only fields revocation may set.
	 *
	 * @var array<int, string>
	 */
	public const REVOCATION_FIELDS = ['status', 'revokedAt', 'revokedBy', 'revocationReason'];

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService.
	 * @param SystemContextSeam|null        $seam           The one place Filinq reads across organisations; used for
	 *                                                      the public lookup by verification code.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-waarmerk-certification/tasks.md
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly ?SystemContextSeam $seam=null,
	) {

	}//end __construct()

	/**
	 * Store a new seal.
	 *
	 * @param array<string, mixed> $row The seal fields.
	 *
	 * @return array<string, mixed> The stored seal with its uuid.
	 *
	 * @throws WaarmerkRefusedException When the row names an existing seal or the register fails.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	public function create(array $row): array {
		if (isset($row['uuid']) === true || isset($row['id']) === true || isset($row['@self']) === true) {
			throw new WaarmerkRefusedException(message: 'A waarmerk is created once and never overwritten', status: 409);
		}

		return $this->write(row: $row, uuid: null);

	}//end create()

	/**
	 * Set the revocation fields of a seal, and nothing else.
	 *
	 * @param array<string, mixed> $seal The stored seal, as find() returned it.
	 * @param array<string, string> $revocation status, revokedAt, revokedBy, revocationReason.
	 *
	 * @return array<string, mixed> The revoked seal.
	 *
	 * @throws WaarmerkRefusedException When other fields are given or the seal is already revoked.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-waarmerk-can-be-revoked-and-revocation-dominates-verification-req-ddwmk-005
	 */
	public function revoke(array $seal, array $revocation): array {
		if (array_diff(array_keys($revocation), self::REVOCATION_FIELDS) !== []) {
			throw new WaarmerkRefusedException(message: 'Only the revocation fields of a waarmerk can change', status: 409);
		}

		if (($seal['status'] ?? '') === 'revoked') {
			throw new WaarmerkRefusedException(message: 'This waarmerk is already revoked; revocation cannot be undone', status: 409);
		}

		$uuid = (string) ($seal['uuid'] ?? '');
		unset($seal['uuid'], $seal['@self'], $seal['id']);

		return $this->write(row: array_merge($seal, $revocation), uuid: $uuid);

	}//end revoke()

	/**
	 * One seal by uuid.
	 *
	 * @param string $uuid The seal's uuid.
	 *
	 * @return array<string, mixed>|null
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	public function find(string $uuid): ?array {
		if ($uuid === '') {
			return null;
		}

		return self::firstWith(rows: $this->search(filters: []), match: ['uuid' => $uuid]);

	}//end find()

	/**
	 * One seal by its verification code, with a system read (no user folder involved).
	 *
	 * @param string $code The verification code.
	 *
	 * @return array<string, mixed>|null
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-waarmerk-is-publicly-verifiable-fail-closed-req-ddwmk-004
	 */
	public function findByCode(string $code): ?array {
		if ($code === '') {
			return null;
		}

		$find = fn (): array => $this->search(filters: ['verificationCode' => $code]);
		if ($this->seam === null) {
			return self::firstWith(rows: $find(), match: ['verificationCode' => $code]);
		}

		// Anyone may verify without an account, so nobody's organisation scopes this read. The unguessable,
		// checksummed code is the capability; the answer carries only what the verification page shows.
		$rows = (array) $this->seam->run(
			objectService: $this->objectResolver->resolve(),
			reason: 'Public waarmerk verification: the lookup by verification code spans organisations',
			operation: $find
		);

		return self::firstWith(rows: $rows, match: ['verificationCode' => $code]);

	}//end findByCode()

	/**
	 * The first row whose fields equal every value given, compared in constant time.
	 *
	 * @param array<int, array<string, mixed>> $rows  The rows.
	 * @param array<string, string>            $match Field => value.
	 *
	 * @return array<string, mixed>|null
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-waarmerk-is-publicly-verifiable-fail-closed-req-ddwmk-004
	 */
	private static function firstWith(array $rows, array $match): ?array {
		foreach ($rows as $row) {
			foreach ($match as $field => $value) {
				if (hash_equals((string) ($row[$field] ?? ''), $value) === false) {
					continue 2;
				}
			}

			return $row;
		}

		return null;

	}//end firstWith()

	/**
	 * Seals matching exact field filters (re-checked locally).
	 *
	 * @param array<string, scalar> $filters Field => value.
	 *
	 * @return array<int, array<string, mixed>>
	 *
	 * @throws WaarmerkRefusedException 503 when the register cannot be read.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	public function search(array $filters): array {
		try {
			$results = $this->objectResolver->slugSearch(_rbac: false)->search(
				register: self::REGISTER,
				schema: self::SCHEMA,
				filters: $filters
			);
		} catch (Throwable $e) {
			throw new WaarmerkRefusedException(message: 'The waarmerk register could not be read', status: 503, previous: $e);
		}

		$rows = [];
		foreach ((array) $results as $result) {
			$row = ($this->storedEntityFields(row: $result) + ['uuid' => '']);
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
	 * Save a row.
	 *
	 * @param array<string, mixed> $row The fields.
	 * @param string|null $uuid The existing uuid, or null to create.
	 *
	 * @return array<string, mixed>
	 *
	 * @throws WaarmerkRefusedException 503 when the register refuses.
	 */
	private function write(array $row, ?string $uuid): array {
		try {
			$stored = $this->objectResolver->resolve()->saveObject(
				object: $row,
				options: new ObjectSaveOptions(
					target: new ObjectFindTarget(register: self::REGISTER, schema: self::SCHEMA),
					uuid: $uuid,
					actor: new SaveActor(unowned: true)
				),
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new WaarmerkRefusedException(message: 'The waarmerk could not be stored', status: 503, previous: $e);
		}

		$saved = ($this->storedEntityFields(row: $stored) + ['uuid' => '']);
		if ($saved['uuid'] === '') {
			throw new WaarmerkRefusedException(message: 'OpenRegister stored the waarmerk without a uuid', status: 503);
		}

		return array_merge($row, $saved);

	}//end write()
}//end class
