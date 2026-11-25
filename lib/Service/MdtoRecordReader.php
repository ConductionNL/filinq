<?php

/**
 * MDTO Record Reader
 *
 * Reads what an MDTO sidecar and its completeness gate need from
 * OpenRegister: the record, its supplement, its files and a dossier's members.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-e-depot-handoff-rides-openregisters-transfer-stack-req-ddtmm-006
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\Files\File;
use OCP\IAppConfig;
use RuntimeException;
use Throwable;

/**
 * Read only: the record's `retention` block is read, never written (design D1).
 * Every read runs as the caller, so OpenRegister's access rules decide what
 * the caller may see. A file's facts are read from Nextcloud: name, media
 * type, size, a sha256 over its content and its modification time as the
 * checksum date, so an unchanged file gives an unchanged sidecar. A sidecar
 * attached earlier (`*.mdto.xml`) is not a representation of the record and
 * is left out.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-e-depot-handoff-rides-openregisters-transfer-stack-req-ddtmm-006
 */
class MdtoRecordReader {

	/**
	 * The suffix of an attached sidecar.
	 */
	public const SIDECAR_SUFFIX = '.mdto.xml';

	/**
	 * OpenRegister's object service.
	 */
	private const OBJECT_SERVICE = 'OCA\OpenRegister\Service\ObjectService';

	/**
	 * OpenRegister's file service.
	 */
	private const FILE_SERVICE = 'OCA\OpenRegister\Service\FileService';

	/**
	 * OpenRegister's schema mapper.
	 */
	private const SCHEMA_MAPPER = 'OCA\OpenRegister\Db\SchemaMapper';

	/**
	 * The slug of the dossier schema.
	 */
	private const DOSSIER_SCHEMA = 'dossier';

	/**
	 * Constructor.
	 *
	 * @param OpenRegisterServiceLocator $locator   Resolves OpenRegister's services.
	 * @param IAppConfig                 $appConfig Reads OpenRegister's organisation settings.
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-e-depot-handoff-rides-openregisters-transfer-stack-req-ddtmm-006
	 */
	public function __construct(
		private readonly OpenRegisterServiceLocator $locator,
		private readonly IAppConfig $appConfig,
	) {

	}//end __construct()

	/**
	 * The record's OpenRegister object.
	 *
	 * @param string $uuid The record UUID.
	 *
	 * @return object The ObjectEntity.
	 *
	 * @throws RuntimeException When the record cannot be read (404).
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-e-depot-handoff-rides-openregisters-transfer-stack-req-ddtmm-006
	 */
	public function entity(string $uuid): object {
		try {
			$entity = $this->locator->get(className: self::OBJECT_SERVICE)->find(id: $uuid);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'Record ' . $uuid . ' cannot be read.', code: 404, previous: $e);
		}

		if (is_object($entity) === false || method_exists($entity, 'getObject') === false) {
			throw new RuntimeException(message: 'Record ' . $uuid . ' cannot be read.', code: 404);
		}

		return $entity;

	}//end entity()

	/**
	 * The record as the gate and the sidecar read it.
	 *
	 * @param object $entity The ObjectEntity.
	 *
	 * @return array<string, mixed> `uuid`, `title`, `retention` and the object's own fields.
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-mdto-core-attributes-have-a-single-source-of-truth-req-ddtmm-001
	 */
	public function record(object $entity): array {
		$data      = (array) $entity->getObject();
		$retention = [];
		if (method_exists($entity, 'getRetention') === true) {
			$retention = (array) ($entity->getRetention() ?? []);
		}

		return array_merge(
			$data,
			[
				'uuid' => (string) $entity->getUuid(),
				'title' => (string) ($data['title'] ?? ($data['naam'] ?? ($data['name'] ?? ''))),
				'retention' => $retention,
			]
		);

	}//end record()

	/**
	 * Whether the record is a dossier, by the slug of its OpenRegister schema.
	 *
	 * A dossier that has no supplement yet names its type nowhere else, so
	 * the record type cannot be read from the supplement alone.
	 *
	 * @param object $entity The ObjectEntity.
	 *
	 * @return bool True when the record's schema is the dossier schema.
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-filinq-context-prefills-the-supplement-as-confirmable-proposals-req-ddtmm-003
	 */
	public function isDossier(object $entity): bool {
		if (method_exists($entity, 'getSchema') === false) {
			return false;
		}

		$schema = trim((string) $entity->getSchema());
		if ($schema === self::DOSSIER_SCHEMA) {
			return true;
		}

		if ($schema === '') {
			return false;
		}

		// The schema entity's getters are magic (__call), so they are called, not probed.
		try {
			return $this->locator->get(className: self::SCHEMA_MAPPER)->find($schema)->getSlug() === self::DOSSIER_SCHEMA;
		} catch (Throwable) {
			return false;
		}

	}//end isDossier()

	/**
	 * The record's supplement; empty when it has none.
	 *
	 * @param string $uuid The record UUID.
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-mdtosupplement-schema-models-the-attributes-or-lacks-req-ddtmm-002
	 */
	public function supplement(string $uuid): array {
		return ($this->search(schema: 'mdtoSupplement', filters: ['objectRef' => $uuid])[0] ?? []);

	}//end supplement()

	/**
	 * A dossier's members: the documents its `documents` (or `members`) list names.
	 *
	 * @param array<string, mixed> $record The dossier record.
	 *
	 * @return array<int, array<string, mixed>> Each member's `uuid` and `title`.
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	public function members(array $record): array {
		$members = [];
		foreach ((array) ($record['documents'] ?? ($record['members'] ?? [])) as $reference) {
			$uuid = $reference;
			if (is_array($reference) === true) {
				$uuid = ($reference['uuid'] ?? '');
			}

			$uuid = (string) $uuid;
			if ($uuid === '') {
				continue;
			}

			$members[] = $this->record(entity: $this->entity(uuid: $uuid));
		}

		usort($members, static fn (array $a, array $b): int => strcmp((string) $a['uuid'], (string) $b['uuid']));

		return $members;

	}//end members()

	/**
	 * The consent, prohibition and anonymisation state use restrictions are proposed from.
	 *
	 * Consents are the publicationConsent records whose `documentId` is the
	 * record or one of its files; prohibitions are the publicationProhibition
	 * records those consents were pre-empted by (`policyMatch`); renditions are
	 * the anonymizationLink records whose source is one of the record's files.
	 *
	 * @param string                           $uuid  The record UUID.
	 * @param array<int, array<string, mixed>> $files The record's file facts (`fileId`).
	 *
	 * @return MdtoRestrictionSources
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-filinq-context-prefills-the-supplement-as-confirmable-proposals-req-ddtmm-003
	 */
	public function restrictionSources(string $uuid, array $files): MdtoRestrictionSources {
		$fileIds  = array_values(array_filter(array_map(static fn (array $file): int => (int) ($file['fileId'] ?? 0), $files)));
		$consents = $this->search(schema: 'publicationConsent', filters: ['documentId' => $uuid]);
		foreach ($fileIds as $fileId) {
			$consents = array_merge($consents, $this->search(schema: 'publicationConsent', filters: ['documentId' => (string) $fileId]));
		}

		$prohibitions = [];
		foreach ($consents as $consent) {
			$match = trim((string) ($consent['policyMatch'] ?? ''));
			$prohibition = $this->prohibition(uuid: $match);
			if ($prohibition !== null) {
				$prohibitions[] = $prohibition;
			}
		}

		$renditions = [];
		foreach ($fileIds as $fileId) {
			$renditions = array_merge($renditions, $this->search(schema: 'anonymizationLink', filters: ['sourceFileId' => $fileId]));
		}

		return new MdtoRestrictionSources(consents: $consents, prohibitions: $prohibitions, renditions: $renditions);

	}//end restrictionSources()

	/**
	 * The publication prohibition a consent's `policyMatch` names, or null when
	 * it names none, cannot be read, or names a standing consent instead.
	 *
	 * @param string $uuid The policyMatch value.
	 *
	 * @return array<string, mixed>|null
	 */
	private function prohibition(string $uuid): ?array {
		if ($uuid === '') {
			return null;
		}

		try {
			$data = (array) $this->entity(uuid: $uuid)->getObject();
		} catch (RuntimeException) {
			return null;
		}

		if (array_key_exists('matchRules', $data) === false || array_key_exists('scope', $data) === true) {
			return null;
		}

		return $data;

	}//end prohibition()

	/**
	 * Objects of one Filinq schema matching the filters, as arrays.
	 *
	 * @param string               $schema  The schema slug.
	 * @param array<string, mixed> $filters The field filters.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function search(string $schema, array $filters): array {
		$rows = $this->locator->get(className: self::OBJECT_SERVICE)->searchObjects(
			array_merge(['@self' => ['register' => 'filinq', 'schema' => $schema]], $filters)
		);
		$objects = [];
		foreach ((array) $rows as $row) {
			$uuid = null;
			if (is_object($row) === true && method_exists($row, 'getObject') === true) {
				if (method_exists($row, 'getUuid') === true) {
					$uuid = $row->getUuid();
				}

				$row = $row->getObject();
			}

			$object = (array) $row;
			if ($uuid !== null && isset($object['id']) === false) {
				$object['id'] = (string) $uuid;
			}

			$objects[] = $object;
		}

		return $objects;

	}//end search()

	/**
	 * The facts of the record's files, in name order, earlier sidecars left out.
	 *
	 * @param object $entity The ObjectEntity.
	 *
	 * @return array<int, array<string, mixed>> name, format, size, checksum, checksumDate.
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	public function files(object $entity): array {
		$facts = [];
		foreach ((array) $this->locator->get(className: self::FILE_SERVICE)->getFiles(object: $entity) as $node) {
			if ($node instanceof File === false || str_ends_with(strtolower($node->getName()), self::SIDECAR_SUFFIX) === true) {
				continue;
			}

			$facts[] = [
				'fileId' => (int) $node->getId(),
				'name' => $node->getName(),
				'format' => $node->getMimetype(),
				'size' => (int) $node->getSize(),
				'checksum' => hash('sha256', $node->getContent()),
				'checksumDate' => gmdate('Y-m-d\TH:i:s\Z', $node->getMTime()),
			];
		}

		usort($facts, static fn (array $a, array $b): int => strcmp((string) $a['name'], (string) $b['name']));

		return $facts;

	}//end files()

	/**
	 * OpenRegister's organisation identifier, '' when unset.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-mdto-core-attributes-have-a-single-source-of-truth-req-ddtmm-001
	 */
	public function organisationId(): string {
		return $this->appConfig->getValueString('openregister', 'organisation_identifier', '');

	}//end organisationId()

	/**
	 * OpenRegister's organisation name, as its generator falls back.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-mdto-core-attributes-have-a-single-source-of-truth-req-ddtmm-001
	 */
	public function organisationName(): string {
		return $this->appConfig->getValueString('openregister', 'organisation_name', 'OpenRegister');

	}//end organisationName()
}//end class
