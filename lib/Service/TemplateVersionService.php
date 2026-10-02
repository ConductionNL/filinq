<?php

/**
 * Template Version Service
 *
 * Service for managing template version history stored as OpenRegister objects.
 * Each version captures the state of a template before an update, enabling
 * rollback, comparison, and audit trail of template changes.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Service for CRUD operations on template versions via OpenRegister
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class TemplateVersionService {
	/**
	 * Constructor for TemplateVersionService
	 *
	 * @param ContainerInterface $container Container for dependency injection
	 * @param IAppManager $appManager App manager interface
	 * @param OpenRegisterResolver $registerResolver Resolver for register/schema config
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly IAppManager $appManager,
		private readonly OpenRegisterResolver $registerResolver,
	) {

	}//end __construct()

	/**
	 * Get the ObjectService from OpenRegister
	 *
	 * @return \OCA\OpenRegister\Service\ObjectService The ObjectService instance
	 *
	 * @throws \RuntimeException If OpenRegister is not available
	 */
	private function getObjectService(): \OCA\OpenRegister\Service\ObjectService {
		if (in_array(
			needle: 'openregister',
			haystack: $this->appManager->getInstalledApps(),
			strict: true
		) === true
		) {
			return $this->container->get('OCA\OpenRegister\Service\ObjectService');
		}

		throw new RuntimeException(message: 'OpenRegister service is not available.');
	}//end getObjectService()

	/**
	 * Create a version snapshot of a template's current state
	 *
	 * @param string $templateId The UUID of the parent template
	 * @param array $templateState The current template data to capture
	 * @param string $editor The Nextcloud user ID who made the edit
	 * @param string|null $changelog Optional note describing the change
	 *
	 * @return array The created version object
	 *
	 * @throws Exception If version creation fails
	 *
	 * @spec openspec/specs/template-management/spec.md
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-6
	 */
	public function createVersion(
		string $templateId,
		array $templateState,
		string $editor,
		?string $changelog = null,
	): array {
		$objectService = $this->getObjectService();
		$config = $this->registerResolver->getVersionRegisterAndSchema();
		$versionNumber = $this->getNextVersionNumber(templateId: $templateId);

		$versionData = [
			'templateId' => $templateId,
			'version' => $versionNumber,
			'content' => $templateState['content'] ?? '',
			'name' => $templateState['name'] ?? '',
			'description' => $templateState['description'] ?? '',
			'format' => $templateState['format'] ?? 'A4',
			'orientation' => $templateState['orientation'] ?? 'P',
			'editor' => $editor,
			'changelog' => $changelog ?? '',
		];

		// An office template's version points at its immutable source file.
		if (($templateState['templateType'] ?? 'twig') === 'office') {
			$versionData['templateType'] = 'office';
			$versionData['sourceFileId'] = $templateState['sourceFileId'] ?? null;
			$versionData['contentHash'] = $templateState['contentHash'] ?? null;
		}

		$result = $objectService->saveObject(
			object: $versionData,
			register: $config['register'],
			schema: $config['schema']
		);

		if (is_object($result) === true
			&& method_exists(object_or_class: $result, method: 'jsonSerialize') === true
		) {
			return $result->jsonSerialize();
		}

		return $result;
	}//end createVersion()

	/**
	 * List versions for a template, ordered by version number descending
	 *
	 * @param string $templateId The UUID of the parent template
	 * @param int $limit Maximum number of results (default: 20)
	 * @param int $offset Result offset for pagination (default: 0)
	 *
	 * @return array{results: array, total: int} Paginated version results
	 *
	 * @throws Exception If listing fails
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function getVersions(string $templateId, int $limit = 20, int $offset = 0): array {
		$objectService = $this->getObjectService();
		$config = $this->registerResolver->getVersionRegisterAndSchema();

		$requestParams = [
			'templateId' => $templateId,
			'_limit' => $limit,
			'_offset' => $offset,
			'_order' => ['version' => 'desc'],
		];

		$query = $objectService->buildSearchQuery(
			requestParams: $requestParams,
			register: $config['register'],
			schema: $config['schema']
		);

		return $objectService->searchObjectsPaginated(query: $query);
	}//end getVersions()

	/**
	 * Get a single version by UUID
	 *
	 * @param string $versionId The version UUID
	 *
	 * @return array The version object
	 *
	 * @throws Exception If the version is not found
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function getVersion(string $versionId): array {
		$objectService = $this->getObjectService();
		$config = $this->registerResolver->getVersionRegisterAndSchema();

		$result = $objectService->find(
			id: $versionId,
			register: $config['register'],
			schema: $config['schema']
		);

		if (empty($result) === true) {
			throw new Exception(message: 'Version not found', code: 404);
		}

		if (is_object($result) === true
			&& method_exists(object_or_class: $result, method: 'jsonSerialize') === true
		) {
			return $result->jsonSerialize();
		}

		return $result;
	}//end getVersion()

	/**
	 * Get the next version number for a template
	 *
	 * @param string $templateId The UUID of the parent template
	 *
	 * @return int The next version number (existing count + 1)
	 *
	 * @throws Exception If counting fails
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function getNextVersionNumber(string $templateId): int {
		$result = $this->getVersions(
			templateId: $templateId,
			limit: 1,
			offset: 0
		);

		return $result['total'] + 1;
	}//end getNextVersionNumber()

	/**
	 * Find the stored snapshot of one version of a template, by its number.
	 *
	 * Snapshot N holds the content version N had while it was the head. The
	 * head itself has no snapshot: it is the template object.
	 *
	 * @param string $templateId The UUID of the parent template
	 * @param int    $number     The version number
	 *
	 * @return array|null The snapshot, or null when the chain has no such version
	 *
	 * @throws Exception If the chain cannot be read
	 *
	 * @spec openspec/changes/generated-document-names-its-template-version/specs/template-version-provenance/spec.md#requirement-a-caller-can-pin-the-template-version-to-render-req-ddtvp-003
	 */
	public function findVersionByNumber(string $templateId, int $number): ?array {
		// The chain is read whole and matched here: a `version` search parameter
		// could be read by OpenRegister as its own `@self.version` metadata.
		foreach (($this->getVersions(templateId: $templateId, limit: 1000)['results'] ?? []) as $row) {
			$row = $this->toArray(row: $row);
			if ((int) ($row['version'] ?? 0) === $number && ($row['templateId'] ?? null) === $templateId) {
				return $row;
			}
		}

		return null;
	}//end findVersionByNumber()

	/**
	 * Which version of a template was the head at a moment.
	 *
	 * 🔑 A snapshot is written when its version is REPLACED, so snapshot N's
	 * creation time is the moment version N stopped being the head, and version
	 * N+1 started. Version 1 started when the template itself was created. The
	 * version in force at a moment is therefore the lowest N whose snapshot was
	 * created after it, or the head when every snapshot is older. A moment
	 * before the template existed has no version, and this method says so
	 * rather than rounding to the oldest one.
	 *
	 * @param string            $templateId The UUID of the parent template
	 * @param DateTimeInterface $moment     The moment asked about
	 *
	 * @return int|null The version number, or null when none was in force or the chain cannot say
	 *
	 * @throws Exception If the chain cannot be read
	 *
	 * @spec openspec/changes/generated-document-names-its-template-version/specs/template-version-provenance/spec.md#requirement-filinq-answers-which-version-was-in-force-on-a-date-req-ddtvp-004
	 */
	public function versionInForceAt(string $templateId, DateTimeInterface $moment): ?int {
		$began = $this->templateCreatedAt(templateId: $templateId);
		if ($began === null || $moment < $began) {
			return null;
		}

		$chain = array_map(
			fn ($row): array => $this->toArray(row: $row),
			($this->getVersions(templateId: $templateId, limit: 1000)['results'] ?? [])
		);
		usort($chain, static fn (array $a, array $b): int => (int) $a['version'] <=> (int) $b['version']);

		foreach ($chain as $snapshot) {
			$ended = $this->createdAt(object: $snapshot);
			if ($ended === null) {
				return null;
			}

			if ($moment < $ended) {
				return (int) $snapshot['version'];
			}
		}

		return (count($chain) + 1);
	}//end versionInForceAt()

	/**
	 * When the template object itself was created.
	 *
	 * @param string $templateId The UUID of the template
	 *
	 * @return DateTimeImmutable|null The creation moment, or null when unknown
	 */
	private function templateCreatedAt(string $templateId): ?DateTimeImmutable {
		$config = $this->registerResolver->getRegisterAndSchema();
		$template = $this->getObjectService()->find(
			id: $templateId,
			register: $config['register'],
			schema: $config['schema']
		);

		if (empty($template) === true) {
			return null;
		}

		return $this->createdAt(object: $this->toArray(row: $template));
	}//end templateCreatedAt()

	/**
	 * Read an object's creation moment from its `@self` block.
	 *
	 * @param array $object The serialised object
	 *
	 * @return DateTimeImmutable|null The moment, or null when absent or unreadable
	 */
	private function createdAt(array $object): ?DateTimeImmutable {
		$created = ($object['@self']['created'] ?? null);
		if (is_string($created) === false || $created === '') {
			return null;
		}

		try {
			return new DateTimeImmutable($created);
		} catch (Exception $e) {
			return null;
		}
	}//end createdAt()

	/**
	 * Serialise an OpenRegister result row to an array.
	 *
	 * @param mixed $row An ObjectEntity or an array
	 *
	 * @return array The row as an array
	 */
	private function toArray(mixed $row): array {
		if (is_object($row) === true && method_exists(object_or_class: $row, method: 'jsonSerialize') === true) {
			return $row->jsonSerialize();
		}

		return (array) $row;
	}//end toArray()

	/**
	 * Restore a template to a previous version
	 *
	 * Saves the current template state as a new version, then updates the
	 * template with the content from the target version.
	 *
	 * @param string $templateId The UUID of the template to restore
	 * @param string $versionId The UUID of the version to restore to
	 * @param string $editor The Nextcloud user ID performing the restore
	 * @param TemplateService $service The template service for updating the template
	 *
	 * @return array The restored template object
	 *
	 * @throws Exception If restore fails
	 *
	 * @spec openspec/specs/template-management/spec.md
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-6
	 */
	public function restoreVersion(
		string $templateId,
		string $versionId,
		string $editor,
		TemplateService $service,
	): array {
		$targetVersion = $this->getVersion(versionId: $versionId);
		$currentState = $service->getTemplate(id: $templateId);

		// Save current state as a new version before restoring.
		$this->createVersion(
			templateId: $templateId,
			templateState: $currentState,
			editor: $editor,
			changelog: 'Auto-saved before restore to version ' . $targetVersion['version']
		);

		// Restore the target version's data to the template.
		$restoreData = [
			'content' => $targetVersion['content'],
			'name' => $targetVersion['name'],
			'description' => $targetVersion['description'] ?? '',
			'format' => $targetVersion['format'] ?? 'A4',
			'orientation' => $targetVersion['orientation'] ?? 'P',
		];
		if (($targetVersion['templateType'] ?? 'twig') === 'office') {
			// Re-point the template at the exact office revision of that version.
			$restoreData['sourceFileId'] = $targetVersion['sourceFileId'] ?? null;
			$restoreData['contentHash'] = $targetVersion['contentHash'] ?? null;
		}

		return $service->updateTemplateWithoutVersion(
			id: $templateId,
			data: $restoreData
		);

	}//end restoreVersion()

	/**
	 * Get two versions for client-side diff comparison
	 *
	 * @param string $versionIdFrom The UUID of the source version
	 * @param string $versionIdTo The UUID of the target version
	 *
	 * @return array{from: array, to: array} Both version objects
	 *
	 * @throws Exception If either version is not found
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function getDiff(string $versionIdFrom, string $versionIdTo): array {
		$from = $this->getVersion(versionId: $versionIdFrom);
		$to = $this->getVersion(versionId: $versionIdTo);

		return [
			'from' => $from,
			'to' => $to,
		];

	}//end getDiff()
}//end class
