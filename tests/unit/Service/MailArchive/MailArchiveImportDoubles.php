<?php

/**
 * Test doubles for the mail archive import
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\MailArchive
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\MailArchive;

use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCA\OpenRegister\Service\ObjectService;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use RuntimeException;
use stdClass;

/**
 * In-memory OpenRegister for mailArchiveImport rows, validating every save
 * against the real schema in filinq_register.json.
 */
class MailArchiveObjectStore extends ObjectService {

	/**
	 * Stored rows by uuid.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public array $rows = [];

	/**
	 * Every save: [payload, rbac, multitenancy].
	 *
	 * @var list<array{0: array<string, mixed>, 1: bool, 2: bool}>
	 */
	public array $saves = [];

	/**
	 * The rbac flag of every read.
	 *
	 * @var list<bool>
	 */
	public array $readRbac = [];

	/**
	 * Find a row.
	 *
	 * @param string                 $id            Id
	 * @param ObjectFindOptions|null $options       Register, schema and read options
	 * @param bool                   $_rbac         RBAC
	 * @param bool                   $_multitenancy Multitenancy
	 *
	 * @return array<string, mixed>|null
	 */
	public function find(
		string $id = '',
		?ObjectFindOptions $options = null,
		bool $_rbac = true,
		bool $_multitenancy = true
	) {
		$this->readRbac[] = $_rbac;
		if (isset($this->rows[$id]) === false) {
			return null;
		}

		return $this->rows[$id] + ['@self' => ['id' => $id]];

	}//end find()

	/**
	 * Save a row after validating it against the real schema.
	 *
	 * @param array<string, mixed> $object        Object
	 * @param ObjectSaveOptions    $options       Target register and schema, and the uuid when updating
	 * @param bool                 $_rbac         RBAC
	 * @param bool                 $_multitenancy Multitenancy
	 *
	 * @return array<string, mixed>
	 */
	public function saveObject(
		array $object = [],
		ObjectSaveOptions $options = new ObjectSaveOptions(),
		bool $_rbac = true,
		bool $_multitenancy = true
	) {
		$register = (string) ($options->target->register ?? '');
		$schema = (string) ($options->target->schema ?? '');
		$uuid = $options->uuid;
		if ($register !== 'filinq' || $schema !== 'mailArchiveImport') {
			throw new RuntimeException('unexpected target ' . $register . '/' . $schema);
		}

		self::assertValidImport(payload: $object);
		$this->saves[] = [$object, $_rbac, $_multitenancy];
		if ($uuid === null) {
			$uuid = 'import-' . (count($this->rows) + 1);
		}

		$this->rows[$uuid] = $object;

		return $object + ['@self' => ['id' => $uuid]];

	}//end saveObject()

	/**
	 * Equality search.
	 *
	 * @param string               $registerSlug  Register
	 * @param string               $schemaSlug    Schema
	 * @param array<string, mixed> $filters       Filters
	 * @param bool                 $_rbac         RBAC
	 * @param bool                 $_multitenancy Multitenancy
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function searchObjectsBySlug(
		string $registerSlug,
		string $schemaSlug,
		array $filters = [],
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		$this->readRbac[] = $_rbac;
		$hits = [];
		foreach ($this->rows as $id => $row) {
			foreach ($filters as $key => $value) {
				if (($row[$key] ?? null) !== $value) {
					continue 2;
				}
			}

			$hits[] = $row + ['@self' => ['id' => $id]];
		}

		return $hits;

	}//end searchObjectsBySlug()

	/**
	 * Assert a payload passes the mailArchiveImport schema of filinq_register.json, with no undeclared field.
	 *
	 * @param array<string, mixed> $payload The object as written.
	 *
	 * @return void
	 */
	public static function assertValidImport(array $payload): void {
		$register = json_decode((string) file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_register.json'));
		$schema = $register->components->schemas->mailArchiveImport;
		$properties = new stdClass();
		foreach ($schema->properties as $name => $property) {
			$copy = clone $property;
			unset($copy->required, $copy->facetable, $copy->visible, $copy->order);
			$properties->{$name} = $copy;
		}

		$jsonSchema = (object) [
			'type' => 'object',
			'required' => $schema->required,
			'properties' => $properties,
			'additionalProperties' => false,
		];
		unset($payload['uuid']);
		$result = (new Validator())->validate(json_decode((string) json_encode($payload)), json_encode($jsonSchema));
		if ($result->isValid() === false) {
			throw new RuntimeException('mailArchiveImport refused: ' . json_encode((new ErrorFormatter())->format($result->error())));
		}

	}//end assertValidImport()
}//end class
