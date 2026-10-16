<?php

/**
 * Which redacted copy belongs to which original.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Redaction;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use OCA\OpenRegister\Db\ObjectEntity;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Reads `anonymizationLink` rows, which pair a source file with its copy.
 */
class AnonymizationLinkReader {

	/**
	 * The schema these rows live in.
	 *
	 * @var string
	 */
	public const SCHEMA = 'anonymizationLink';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param LoggerInterface               $logger         Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The link for one source file, or null when it has no redacted copy.
	 *
	 * 🔴 A READ THAT FAILS RETURNS NULL, WHICH READS AS "NOT REDACTED YET" AND
	 * STOPS THE LIST. The other direction would compose a list over records
	 * whose redaction state nobody could confirm.
	 *
	 * @param int $sourceFileId The original's file id.
	 *
	 * @return array<string, mixed>|null The link.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function forSource(int $sourceFileId): ?array {
		if ($sourceFileId <= 0) {
			return null;
		}

		try {
			// 🔴 SLUGS GO THROUGH `searchObjectsBySlug`, NEVER `searchObjects`.
			// `searchObjects` answers a slug with zero rows and no error, so
			// every source file read as having no redacted copy and
			// PublicationListComposer refused the whole list with
			// "N of N records have no redacted copy yet" even when every one
			// had been redacted.
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: ['sourceFileId' => $sourceFileId]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[AnonymizationLinkReader] could not read the link, so the record counts as not ready',
				context: [
					'file' => __FILE__,
					'line' => __LINE__,
					'sourceFileId' => $sourceFileId,
					'error' => $e->getMessage(),
				]
			);

			return null;
		}

		if (is_array($results) === false || $results === []) {
			return null;
		}

		return $this->normalise(row: $results[0]);

	}//end forSource()

	/**
	 * The link whose redacted copy is this file, or null.
	 *
	 * @param int $anonymizedFileId The redacted copy's file id.
	 *
	 * @return array<string, mixed>|null The link, with its `uuid`.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-4.2
	 */
	public function forAnonymized(int $anonymizedFileId): ?array {
		if ($anonymizedFileId <= 0) {
			return null;
		}

		try {
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: ['anonymizedFileId' => $anonymizedFileId]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[AnonymizationLinkReader] could not read the link of a redacted copy',
				context: ['anonymizedFileId' => $anonymizedFileId, 'error' => $e->getMessage()]
			);

			return null;
		}

		foreach ((array) $results as $row) {
			$link = $this->withUuid(row: $row);
			if ((int) ($link['anonymizedFileId'] ?? 0) === $anonymizedFileId) {
				return $link;
			}
		}

		return null;

	}//end forAnonymized()

	/**
	 * The stored link object, for its OpenRegister ids only, or null.
	 *
	 * Read past RBAC on purpose: the answer never reaches a caller, it only
	 * tells the audit trail which register and schema the link lives in, and a
	 * denied caller's attempt must land on the same object as a granted one.
	 *
	 * @param string $linkId The link uuid.
	 *
	 * @return ObjectEntity|null The stored object, or null when there is none.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-3.2
	 */
	public function storedObject(string $linkId): ?ObjectEntity {
		if ($linkId === '') {
			return null;
		}

		try {
			$entity = $this->objectResolver->resolve()->find(
				id: $linkId,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA,
				_rbac: false,
				_multitenancy: false
			);
		} catch (Throwable $e) {
			$this->logger->info(
				message: '[AnonymizationLinkReader] no stored link under this id',
				context: ['linkId' => $linkId, 'error' => $e->getMessage()]
			);

			return null;
		}

		if ($entity instanceof ObjectEntity === false) {
			return null;
		}

		return $entity;

	}//end storedObject()

	/**
	 * One link by its uuid, read as the caller, or null.
	 *
	 * @param string $linkId The link uuid.
	 *
	 * @return array<string, mixed>|null The link, with its `uuid`.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-3.1
	 */
	public function byId(string $linkId): ?array {
		if ($linkId === '') {
			return null;
		}

		try {
			$entity = $this->objectResolver->resolve()->find(
				id: $linkId,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA
			);
		} catch (Throwable $e) {
			$this->logger->info(
				message: '[AnonymizationLinkReader] no readable link under this id',
				context: ['linkId' => $linkId, 'error' => $e->getMessage()]
			);

			return null;
		}

		if ($entity === null) {
			return null;
		}

		$link = $this->withUuid(row: $entity);
		if (($link['uuid'] ?? '') === '') {
			$link['uuid'] = $linkId;
		}

		return $link;

	}//end byId()

	/**
	 * A normalised link that also carries its uuid.
	 *
	 * @param mixed $row The OpenRegister row.
	 *
	 * @return array<string, mixed> The link.
	 *
	 * @spec exclude Shape adapter over an OpenRegister response.
	 */
	private function withUuid(mixed $row): array {
		$link = $this->normalise(row: $row);
		$uuid = '';
		if (is_object($row) === true && method_exists($row, 'getUuid') === true) {
			$uuid = (string) $row->getUuid();
		}

		if ($uuid === '' && is_array($row) === true) {
			$uuid = (string) ($row['@self']['id'] ?? ($row['uuid'] ?? ($row['id'] ?? '')));
		}

		$link['uuid'] = (string) ($link['uuid'] ?? $uuid);
		if ($link['uuid'] === '') {
			$link['uuid'] = (string) ($link['@self']['id'] ?? $uuid);
		}

		return $link;

	}//end withUuid()

	/**
	 * Read one OpenRegister row into the flat shape this app uses.
	 *
	 * @param mixed $row The row.
	 *
	 * @return array<string, mixed> The link.
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

		if (isset($data['object']) === true && is_array($data['object']) === true) {
			return $data['object'];
		}

		return $data;

	}//end normalise()
}//end class
