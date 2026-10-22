<?php

/**
 * Pseudonym Map Repository
 *
 * Reads and writes the `pseudonymMap` rows in the `filinq` register. Every call
 * bypasses OpenRegister's cascade: the schema grants admins only, so that the
 * REST API offers the rows to nobody else, and the app reaches them on behalf of
 * an operator or a restorer after its own checks.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Pseudonymisation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Pseudonymisation;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use RuntimeException;
use Throwable;

/**
 * The `pseudonymMap` rows.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Pseudonymisation
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PseudonymMapRepository {

	public const SCHEMA = 'pseudonymMap';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService.
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
	) {

	}//end __construct()

	/**
	 * The map that belongs to one anonymisation link, as a rendered read.
	 *
	 * A rendered read strips the writeOnly `mappings`, so what comes back here
	 * is the metadata only: that is the point, and why the ciphertext has its
	 * own method.
	 *
	 * @param string $linkId The anonymisation link uuid.
	 *
	 * @return array<string, mixed>|null The row without `mappings`, with its uuid.
	 *
	 * @throws RuntimeException When OpenRegister cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.1
	 */
	public function findForLink(string $linkId): ?array {
		if ($linkId === '') {
			return null;
		}

		try {
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: ['anonymizationLink' => $linkId],
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The pseudonym map could not be read: ' . $e->getMessage(), code: 0, previous: $e);
		}

		foreach ((array) $results as $result) {
			$row = $this->normalise(row: $result);
			// The filter is the search's; the match is ours. A search that
			// ignored the filter must not hand one link another link's key.
			if ((string) ($row['anonymizationLink'] ?? '') === $linkId) {
				unset($row['mappings']);
				return $row;
			}
		}

		return null;

	}//end findForLink()

	/**
	 * Every map kept for anonymised copies of one source document, without `mappings`.
	 *
	 * @param int $sourceFileId The original document's file id.
	 *
	 * @return array<int, array<string, mixed>> The rows, each with its uuid.
	 *
	 * @throws RuntimeException When OpenRegister cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-3.3
	 */
	public function findForSource(int $sourceFileId): array {
		try {
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: ['sourceFileId' => $sourceFileId],
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The pseudonym maps could not be read: ' . $e->getMessage(), code: 0, previous: $e);
		}

		$rows = [];
		foreach ((array) $results as $result) {
			$row = $this->normalise(row: $result);
			// The filter is the search's; the match is ours.
			if ((int) ($row['sourceFileId'] ?? 0) === $sourceFileId && (string) ($row['uuid'] ?? '') !== '') {
				unset($row['mappings']);
				$rows[] = $row;
			}
		}

		return $rows;

	}//end findForSource()

	/**
	 * The stored ciphertext of one map.
	 *
	 * The only unrendered read in this class, and the only way to the payload.
	 * Called from the restore path alone.
	 *
	 * @param string $uuid The map uuid.
	 *
	 * @return string The ciphertext, '' when the row has none.
	 *
	 * @throws RuntimeException When OpenRegister cannot be read or the row is gone.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-3.1
	 */
	public function readCiphertext(string $uuid): string {
		try {
			$entity = $this->objectResolver->resolve()->find(
				id: $uuid,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA,
				_rbac: false,
				_render: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The pseudonym map could not be read: ' . $e->getMessage(), code: 0, previous: $e);
		}

		if ($entity === null) {
			throw new RuntimeException(message: 'The pseudonym map ' . $uuid . ' does not exist.');
		}

		return (string) ($this->normalise(row: $entity)['mappings'] ?? '');

	}//end readCiphertext()

	/**
	 * Write a map, replacing the one the link already has.
	 *
	 * @param array<string, mixed> $row The row, `mappings` already encrypted.
	 * @param string|null $uuid The existing map's uuid, null for a new one.
	 *
	 * @return string The uuid of the stored map.
	 *
	 * @throws RuntimeException When OpenRegister refuses the write.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.1
	 */
	public function save(array $row, ?string $uuid): string {
		unset($row['uuid']);
		try {
			$stored = $this->objectResolver->resolve()->saveObject(
				object: $row,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA,
				uuid: $uuid,
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'Could not store the pseudonym map: ' . $e->getMessage(), code: 0, previous: $e);
		}

		$storedUuid = (string) ($this->normalise(row: $stored)['uuid'] ?? '');
		if ($storedUuid === '') {
			throw new RuntimeException(message: 'OpenRegister stored the pseudonym map without a uuid.');
		}

		return $storedUuid;

	}//end save()

	/**
	 * Delete one map.
	 *
	 * @param string $uuid The map uuid.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When OpenRegister refuses the delete.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.2
	 */
	public function delete(string $uuid): void {
		try {
			$deleted = $this->objectResolver->resolve()->deleteObject(
				uuid: $uuid,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA,
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'Could not delete the pseudonym map: ' . $e->getMessage(), code: 0, previous: $e);
		}

		if ($deleted === false) {
			throw new RuntimeException(message: 'OpenRegister did not delete the pseudonym map ' . $uuid . '.');
		}

	}//end delete()

	/**
	 * Flatten an OpenRegister row to its fields plus uuid.
	 *
	 * @param mixed $row An ObjectEntity or array.
	 *
	 * @return array<string, mixed> The fields.
	 *
	 * @spec exclude Shape adapter over an OpenRegister response.
	 */
	private function normalise(mixed $row): array {
		$data = $row;
		if (is_object($row) === true && method_exists($row, 'getObject') === true && method_exists($row, 'getUuid') === true) {
			// An unrendered ObjectEntity: its jsonSerialize is the RENDER
			// shape, so the stored payload is read from getObject().
			return array_merge((array) $row->getObject(), ['uuid' => (string) $row->getUuid()]);
		}

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
