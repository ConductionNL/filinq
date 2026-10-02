<?php

/**
 * Doubles for the office template tests
 *
 * OfficeObjectStore keeps textFragment, templateImportJob and template
 * objects in memory and refuses any payload the real fragment of
 * filinq_register.json refuses (Opis, through WizardObjectStore::assertValid).
 * MemorySourceStore keeps office sources in memory instead of the app data
 * folder. OfficeFixtures reads the DOCX/ODT fixtures.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\OfficeTemplate
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

namespace OCA\Filinq\Tests\Unit\Service\OfficeTemplate;

require_once __DIR__ . '/../Wizard/WizardDoubles.php';

use OCA\Filinq\Service\OfficeTemplate\OfficeSourceStore;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateRefused;
use OCA\Filinq\Tests\Unit\Service\Wizard\WizardObjectStore;
use OCA\OpenRegister\Service\ObjectService;
use RuntimeException;

/**
 * In-memory filinq-register objects with the register's validation.
 */
class OfficeObjectStore extends ObjectService {

	/**
	 * Stored objects by uuid, with their schema under `_schema`.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public array $rows = [];

	/**
	 * Saves made, in order: [schema, object].
	 *
	 * @var array<int, array{0: string, 1: array<string, mixed>}>
	 */
	public array $saves = [];

	/**
	 * The _rbac flag of every call, in order.
	 *
	 * @var bool[]
	 */
	public array $rbac = [];

	/**
	 * Constructor (no OpenRegister wiring).
	 */
	public function __construct() {
	}

	/**
	 * Find one.
	 *
	 * @param string $id            The uuid.
	 * @param string $register      The register.
	 * @param string $schema        The schema.
	 * @param bool   $_rbac         RBAC.
	 * @param bool   $_multitenancy Multitenancy.
	 * @param bool   $_render       Render.
	 * @param bool   $_audit        Audit.
	 *
	 * @return array<string, mixed>|null The object.
	 */
	public function find(
		string $id = '',
		string $register = '',
		string $schema = '',
		bool $_rbac = true,
		bool $_multitenancy = true,
		bool $_render = true,
		bool $_audit = true,
	) {
		$this->rbac[] = $_rbac;
		if (isset($this->rows[$id]) === false) {
			return null;
		}

		$row = $this->rows[$id];
		unset($row['_schema']);

		return $row + ['@self' => ['id' => $id]];
	}

	/**
	 * Save one.
	 *
	 * @param array<string, mixed> $object        The object.
	 * @param string               $register      The register.
	 * @param string               $schema        The schema.
	 * @param string|null          $uuid          The uuid.
	 * @param bool                 $_rbac         RBAC.
	 * @param bool                 $_multitenancy Multitenancy.
	 *
	 * @return array<string, mixed> The stored object.
	 */
	public function saveObject(
		array $object = [],
		string $register = '',
		string $schema = '',
		?string $uuid = null,
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		if ($register !== 'filinq') {
			throw new RuntimeException('unexpected register ' . $register);
		}

		WizardObjectStore::assertValid(schema: $schema, payload: $object);
		$this->rbac[] = $_rbac;
		$this->saves[] = [$schema, $object];
		$uuid ??= $schema . '-' . (count($this->rows) + 1);
		$this->rows[$uuid] = $object + ['_schema' => $schema];

		return $this->find(id: $uuid);
	}

	/**
	 * Search by equality filters within a schema.
	 *
	 * @param string               $registerSlug  The register.
	 * @param string               $schemaSlug    The schema.
	 * @param array<string, mixed> $filters       The filters.
	 * @param bool                 $_rbac         RBAC.
	 * @param bool                 $_multitenancy Multitenancy.
	 *
	 * @return array<int, array<string, mixed>> The hits.
	 */
	public function searchObjectsBySlug(
		string $registerSlug,
		string $schemaSlug,
		array $filters = [],
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		$hits = [];
		foreach ($this->rows as $id => $row) {
			if ($row['_schema'] !== $schemaSlug) {
				continue;
			}

			foreach ($filters as $key => $value) {
				if (($row[$key] ?? null) !== $value) {
					continue 2;
				}
			}

			$hits[] = $this->find(id: (string) $id);
		}

		return $hits;
	}

	/**
	 * Delete one.
	 *
	 * @param string $uuid          The uuid.
	 * @param string $register      The register.
	 * @param string $schema        The schema.
	 * @param bool   $_rbac         RBAC.
	 * @param bool   $_multitenancy Multitenancy.
	 *
	 * @return bool True.
	 */
	public function deleteObject(
		string $uuid = '',
		string $register = '',
		string $schema = '',
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		unset($this->rows[$uuid]);

		return true;
	}

	/**
	 * Stored objects of one schema.
	 *
	 * @param string $schema The schema.
	 *
	 * @return array<string, array<string, mixed>> By uuid.
	 */
	public function of(string $schema): array {
		return array_filter($this->rows, static fn (array $row): bool => $row['_schema'] === $schema);
	}
}

/**
 * Office sources in memory.
 */
class MemorySourceStore extends OfficeSourceStore {

	/**
	 * Stored files by id: [extension, bytes].
	 *
	 * @var array<int, array{0: string, 1: string}>
	 */
	public array $files = [];

	/**
	 * Removed ids.
	 *
	 * @var int[]
	 */
	public array $removed = [];

	/**
	 * Constructor (no file system).
	 */
	public function __construct() {
	}

	public function put(string $bytes, string $extension): int {
		$id = 9000 + count($this->files) + count($this->removed);
		$this->files[$id] = [$extension, $bytes];

		return $id;
	}

	public function read(int $fileId): string {
		if (isset($this->files[$fileId]) === false) {
			throw new OfficeTemplateRefused(message: 'gone', reason: 'source-missing', code: 404);
		}

		return $this->files[$fileId][1];
	}

	public function copy(int $fileId): int {
		return $this->put(bytes: $this->read(fileId: $fileId), extension: $this->files[$fileId][0]);
	}

	public function remove(int $fileId): void {
		unset($this->files[$fileId]);
		$this->removed[] = $fileId;
	}
}

/**
 * The office fixtures.
 */
final class OfficeFixtures {

	/**
	 * The bytes of a fixture in tests/sample-documents/office-templates.
	 *
	 * @param string $name The file name.
	 *
	 * @return string The bytes.
	 */
	public static function bytes(string $name): string {
		return (string) file_get_contents(__DIR__ . '/../../../sample-documents/office-templates/' . $name);
	}

	/**
	 * The text of a DOCX's main part.
	 *
	 * @param string $docx The DOCX.
	 *
	 * @return string The text.
	 */
	public static function text(string $docx): string {
		$path = tempnam(sys_get_temp_dir(), 'fx_');
		file_put_contents($path, $docx);
		$zip = new \ZipArchive();
		$zip->open($path);
		$xml = (string) $zip->getFromName('word/document.xml');
		$zip->close();
		unlink($path);

		return html_entity_decode(strip_tags(str_replace('</w:p>', "\n", $xml)), ENT_QUOTES | ENT_XML1);
	}

	/**
	 * A ZIP of the given entries.
	 *
	 * @param array<string, string> $entries Path to bytes.
	 *
	 * @return string The ZIP.
	 */
	public static function zip(array $entries): string {
		$path = tempnam(sys_get_temp_dir(), 'fxz_');
		$zip = new \ZipArchive();
		$zip->open($path, \ZipArchive::OVERWRITE);
		foreach ($entries as $name => $bytes) {
			$zip->addFromString($name, $bytes);
		}

		$zip->close();
		$bytes = (string) file_get_contents($path);
		unlink($path);

		return $bytes;
	}
}
