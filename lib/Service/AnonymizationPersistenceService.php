<?php

/**
 * Anonymization Persistence Service
 *
 * Persists the by-products of a successful anonymisation run: the idempotent
 * source↔anonymised file link in OpenRegister, and the publicationConsent
 * records for entities the operator chose to publish unredacted. Both are
 * best-effort — the anonymised file already exists when they run, so a
 * persistence failure never alters the anonymise response.
 *
 * Extracted verbatim from AnonymizationService.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-4
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Records the anonymisation link and the unredacted-publication consents.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/anonymization/spec.md
 */
class AnonymizationPersistenceService {
	/**
	 * Constructor for AnonymizationPersistenceService
	 *
	 * @param LoggerInterface $logger Logger for best-effort persistence failures.
	 * @param OpenRegisterServiceLocator $locator Resolver for OpenRegister services and mappers.
	 * @param ConsentCrudService $consentCrud Consent CRUD service for register/schema config.
	 * @param ConsentService $consentService Consent service for creating publication consents.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly OpenRegisterServiceLocator $locator,
		private readonly ConsentCrudService $consentCrud,
		private readonly ConsentService $consentService,
	) {

	}//end __construct()

	/**
	 * Persist or update the mapping between a source file and its anonymised counterpart.
	 *
	 * Idempotent UPSERT keyed on `sourceFileId`: the first successful
	 * anonymisation of a file creates an `anonymizationLink` object in the
	 * `document` register; every subsequent re-anonymisation of the same
	 * source file updates that same record (preserving its `@self`, which
	 * triggers OpenRegister's update path) and increments `runCount`. Both
	 * `sourceFileId` and `anonymizedFileId` are facetable on the schema so
	 * OR's search API resolves the link in both directions.
	 *
	 * Best-effort: the anonymised file already exists and the run has
	 * succeeded, so a persistence failure here MUST NOT abort or alter the
	 * response. Failures are caught, logged at warning level, and the
	 * unmodified `$resultInfo` is returned (without an `anonymizationLinkId`
	 * key).
	 *
	 * @param int $fileId The source (unanonymised) Nextcloud file ID.
	 * @param mixed $sourceNode The source file node (used for name/path/owner metadata).
	 * @param array<string, mixed> $resultInfo Current result; carries anonymizedFileId/Name/Path + replacementCount.
	 *
	 * @return array<string, mixed> The `$resultInfo`, enriched with `anonymizationLinkId` on success.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function recordAnonymizationLink(int $fileId, mixed $sourceNode, array $resultInfo): array {
		try {
			$objectService = $this->locator->get(
				className: 'OCA\OpenRegister\Service\ObjectService'
			);

			$object = $this->buildLinkObject(
				objectService: $objectService,
				fileId: $fileId,
				resultInfo: $resultInfo
			);
			$object = $this->applySourceNodeMetadata(object: $object, sourceNode: $sourceNode);

			$saved = $objectService->saveObject(
				object: $object,
				register: 'document',
				schema: 'anonymizationLink'
			);
			$linkId = $this->extractSavedObjectId(saved: $saved);
			if ($linkId !== null) {
				$resultInfo['anonymizationLinkId'] = $linkId;
			}

			$this->logger->info(
				'Anonymisation link recorded',
				[
					'sourceFileId' => $fileId,
					'anonymizedFileId' => $object['anonymizedFileId'],
					'runCount' => $object['runCount'],
				]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				'recordAnonymizationLink failed; anonymisation result is unaffected: ' . $e->getMessage(),
				['fileId' => $fileId, 'exception' => $e]
			);
		}//end try

		return $resultInfo;
	}//end recordAnonymizationLink()

	/**
	 * Create publicationConsent records for each unredacted entity after a successful anonymise run.
	 *
	 * Calls ConsentService::createConsentRequest() once per entry. Any consent-creation
	 * failure is logged but does NOT abort the response — the consent failure is surfaced as
	 * a structured error entry in createdConsents[].
	 *
	 * @param array<string, mixed> $resultInfo Current anonymization result.
	 * @param array<int, array<string, mixed>> $unredactedEntities Validated unredacted-entity entries.
	 *
	 * @return array<string, mixed> Result enriched with createdConsents[] field.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-4
	 */
	public function createConsentsForUnredactedEntities(array $resultInfo, array $unredactedEntities): array {
		$config = $this->consentCrud->getConsentConfig();
		if ($config === null) {
			$this->logger->warning(
				'Publication consent register/schema not configured; skipping consent creation for unredacted entities.'
			);
			$resultInfo['createdConsents'] = [];
			return $resultInfo;
		}

		$documentId = (string)($resultInfo['anonymizedFileId'] ?? '');
		$createdConsents = [];

		foreach ($unredactedEntities as $entry) {
			$createdConsents[] = $this->createOneConsent(
				entry: $entry,
				documentId: $documentId,
				config: $config
			);
		}

		$resultInfo['createdConsents'] = $createdConsents;
		return $resultInfo;
	}//end createConsentsForUnredactedEntities()

	/**
	 * Create the publicationConsent record for one unredacted entity.
	 *
	 * @param array<string, mixed> $entry The unredacted-entity entry.
	 * @param string $documentId The anonymised document id the consent belongs to.
	 * @param array<string, string> $config Resolved consent register/schema configuration.
	 *
	 * @return array<string, mixed> The created-consent descriptor, or its structured failure entry.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
	 */
	private function createOneConsent(array $entry, string $documentId, array $config): array {
		$entityText = (string)($entry['entityText'] ?? '');
		$extra = [
			'publicationBases' => ($entry['publicationBases'] ?? []),
		];

		if (empty($entry['contactEmail']) === false) {
			$extra['contactEmail'] = (string)$entry['contactEmail'];
		}

		if (empty($entry['contactAddress']) === false) {
			$extra['contactAddress'] = (string)$entry['contactAddress'];
		}

		try {
			$consent = $this->consentService->createConsentRequest(
				documentId: $documentId,
				entityType: (string)($entry['entityType'] ?? ''),
				entityText: $entityText,
				register: $config['register'],
				schema: $config['schema'],
				extra: $extra
			);

			return [
				'entityId' => ($entry['entityId'] ?? null),
				'entityText' => $entityText,
				'consentId' => ($consent['id'] ?? $consent['uuid'] ?? null),
				'consentStatus' => ($consent['consentStatus'] ?? 'pending'),
				'action' => 'created',
			];
		} catch (Exception $e) {
			$this->logger->error(
				'Failed to create consent for unredacted entity: ' . $e->getMessage(),
				['entityText' => $entityText, 'exception' => $e]
			);

			return [
				'entityId' => ($entry['entityId'] ?? null),
				'entityText' => $entityText,
				'action' => 'failed',
				'error' => 'Consent creation failed.',
			];
		}//end try

	}//end createOneConsent()

	/**
	 * Build the anonymizationLink object, reusing the existing record when present.
	 *
	 * @param mixed $objectService OpenRegister ObjectService.
	 * @param int $fileId The source Nextcloud file id.
	 * @param array<string, mixed> $resultInfo The anonymise result.
	 *
	 * @return array<string, mixed> The object ready to be saved.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	private function buildLinkObject(mixed $objectService, int $fileId, array $resultInfo): array {
		$results = $objectService->searchObjects(
			query: [
				'@self' => [
					'register' => 'document',
					'schema' => 'anonymizationLink',
				],
				'sourceFileId' => $fileId,
			]
		);

		$existing = [];
		if (is_array($results) === true && empty($results) === false) {
			$existing = $this->extractLinkObjectData(candidate: $results[0]);
		}

		$object = [
			'@self' => [
				'register' => 'document',
				'schema' => 'anonymizationLink',
			],
			'sourceFileId' => $fileId,
			'runCount' => 1,
		];

		if (empty($existing) === false) {
			$object = $existing;
			$object['runCount'] = ((int)($existing['runCount'] ?? 0) + 1);
		}

		// Anonymised-side metadata + run stats. Only successful runs
		// reach this method, so status is always 'anonymized'.
		$anonymizedName = (string)($resultInfo['anonymizedFileName'] ?? '');
		$object['anonymizedFileId'] = (int)$resultInfo['anonymizedFileId'];
		$object['anonymizedFileName'] = $anonymizedName;
		$object['anonymizedFilePath'] = (string)($resultInfo['anonymizedFilePath'] ?? '');
		$object['status'] = 'anonymized';
		$object['replacementCount'] = (int)($resultInfo['replacementCount'] ?? 0);
		$object['anonymizedAt'] = date(format: 'c');

		$extension = strtolower(pathinfo($anonymizedName, PATHINFO_EXTENSION));
		if (in_array($extension, ['pdf', 'docx', 'odt', 'txt', 'html'], true) === true) {
			$object['outputFormat'] = $extension;
		}

		return $object;
	}//end buildLinkObject()

	/**
	 * Apply best-effort source-node metadata (name, path, owner) to a link object.
	 *
	 * Each accessor is guarded with method_exists so the method tolerates any
	 * file-node-like object (and mocks in unit tests) without fataling.
	 *
	 * @param array<string, mixed> $object The link object being built.
	 * @param mixed $sourceNode The source file node.
	 *
	 * @return array<string, mixed> The object with any resolvable source metadata applied.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	private function applySourceNodeMetadata(array $object, mixed $sourceNode): array {
		if (is_object($sourceNode) === false) {
			return $object;
		}

		if (method_exists(object_or_class: $sourceNode, method: 'getName') === true) {
			$object['sourceFileName'] = (string)$sourceNode->getName();
		}

		if (method_exists(object_or_class: $sourceNode, method: 'getPath') === true) {
			$object['sourceFilePath'] = (string)$sourceNode->getPath();
		}

		$owner = null;
		if (method_exists(object_or_class: $sourceNode, method: 'getOwner') === true) {
			$owner = $sourceNode->getOwner();
		}

		if ($owner !== null && method_exists(object_or_class: $owner, method: 'getUID') === true) {
			$object['anonymizedBy'] = (string)$owner->getUID();
		}

		return $object;
	}//end applySourceNodeMetadata()

	/**
	 * Normalise a searchObjects() candidate to a plain array including its `@self`.
	 *
	 * @param mixed $candidate A search result entry (array, or an OR entity object).
	 *
	 * @return array<string, mixed> The object data, or an empty array if it could not be read.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	private function extractLinkObjectData(mixed $candidate): array {
		if (is_array($candidate) === true) {
			return $candidate;
		}

		if (is_object($candidate) === true) {
			return $this->extractObjectPayload(candidate: $candidate);
		}

		return [];
	}//end extractLinkObjectData()

	/**
	 * Read an OR entity object's payload, restoring its `@self` id when absent.
	 *
	 * @param object $candidate The OR entity object.
	 *
	 * @return array<string, mixed> The payload, or an empty array when it cannot be read.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	private function extractObjectPayload(object $candidate): array {
		if (method_exists(object_or_class: $candidate, method: 'getObject') === true) {
			$payload = $candidate->getObject();
			if (is_array($payload) === true) {
				if (isset($payload['@self']) === false
					&& method_exists(object_or_class: $candidate, method: 'getUuid') === true
				) {
					$uuid = $candidate->getUuid();
					if ($uuid !== null) {
						$payload['@self'] = ['id' => $uuid];
					}
				}

				return $payload;
			}
		}

		if (method_exists(object_or_class: $candidate, method: 'jsonSerialize') === true) {
			$payload = $candidate->jsonSerialize();
			if (is_array($payload) === true) {
				return $payload;
			}
		}

		return [];
	}//end extractObjectPayload()

	/**
	 * Extract the persisted object's identifier from a saveObject() return value.
	 *
	 * @param mixed $saved The value returned by ObjectService::saveObject.
	 *
	 * @return string|null The object id/uuid, or null when it cannot be determined.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	private function extractSavedObjectId(mixed $saved): ?string {
		if (is_object($saved) === true) {
			if (method_exists(object_or_class: $saved, method: 'getUuid') === true) {
				$uuid = $saved->getUuid();
				if (empty($uuid) === false) {
					return (string)$uuid;
				}
			}

			if (method_exists(object_or_class: $saved, method: 'getId') === true) {
				$id = $saved->getId();
				if (empty($id) === false) {
					return (string)$id;
				}
			}

			if (method_exists(object_or_class: $saved, method: 'jsonSerialize') === true) {
				$saved = $saved->jsonSerialize();
			}
		}

		if (is_array($saved) === true) {
			$self = ($saved['@self'] ?? []);
			$id = ($self['id'] ?? ($self['uuid'] ?? ($saved['id'] ?? null)));
			if (empty($id) === false) {
				return (string)$id;
			}
		}

		return null;
	}//end extractSavedObjectId()
}//end class
