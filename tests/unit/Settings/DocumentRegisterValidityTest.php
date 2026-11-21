<?php

/**
 * Unit tests for the validity of the register with the document schemas
 *
 * The register descriptor is imported at boot with nothing on screen to show
 * a failure, and with `hardValidation` on, a seed that does not fit its
 * schema is refused on import. This test reads the whole descriptor the way
 * the import does: every schema the register lists exists, every seed names a
 * declared register and schema, and the `document` and `documentType` seeds
 * fit their schemas field by field (document-register REQ-DREG-D05).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Settings
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/document-register/tasks.md#task-3.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * The full register still holds together with `document` and `documentType`.
 *
 * @coversNothing
 */
#[CoversNothing]
class DocumentRegisterValidityTest extends TestCase {

	/**
	 * The two schemas this change added.
	 */
	private const SCHEMAS = ['document', 'documentType'];

	/**
	 * The parsed register descriptor.
	 *
	 * @return array<string, mixed>
	 */
	private static function descriptor(): array {
		$raw = file_get_contents(filename: __DIR__ . '/../../../lib/Settings/filinq_register.json');
		self::assertIsString(actual: $raw, message: 'The register descriptor must be readable.');
		$parsed = json_decode(json: $raw, associative: true, flags: JSON_THROW_ON_ERROR);
		self::assertIsArray(actual: $parsed);

		return $parsed;

	}//end descriptor()

	/**
	 * Every schema a register lists is declared, and the document schemas are listed.
	 *
	 * @return void
	 */
	public function testEveryListedSchemaIsDeclared(): void {
		$components = self::descriptor()['components'];
		$declared   = array_keys($components['schemas']);

		foreach ($components['registers'] as $slug => $register) {
			$missing = array_values(array_diff($register['schemas'] ?? [], $declared));
			$this->assertSame(expected: [], actual: $missing, message: 'Register ' . $slug . ' lists schemas that are not declared.');
		}

		foreach (self::SCHEMAS as $schema) {
			$this->assertContains(needle: $schema, haystack: $components['registers']['filinq']['schemas']);
		}

	}//end testEveryListedSchemaIsDeclared()

	/**
	 * Every seed names a declared register and schema, and slugs are unique per schema.
	 *
	 * @return void
	 */
	public function testEverySeedNamesADeclaredRegisterAndSchema(): void {
		$components = self::descriptor()['components'];
		$seen       = [];

		foreach ($components['objects'] as $index => $seed) {
			$self = $seed['@self'] ?? [];
			$register = (string) ($self['register'] ?? '');
			$schema   = (string) ($self['schema'] ?? '');
			$this->assertArrayHasKey(key: $register, array: $components['registers'], message: 'Seed ' . $index . ' names an unknown register.');
			$this->assertArrayHasKey(key: $schema, array: $components['schemas'], message: 'Seed ' . $index . ' names an unknown schema.');

			$slug = (string) ($self['slug'] ?? '');
			if ($slug !== '') {
				$key = $self['schema'] . '/' . $slug;
				$this->assertArrayNotHasKey(key: $key, array: $seen, message: 'Seed slug ' . $key . ' is used twice.');
				$seen[$key] = true;
			}
		}

	}//end testEverySeedNamesADeclaredRegisterAndSchema()

	/**
	 * The document schemas keep what the import reads: hard validation, a name field that exists,
	 * and for `document` a lifecycle over its own status values and an archival block.
	 *
	 * @return void
	 */
	public function testTheDocumentSchemasKeepWhatTheImportReads(): void {
		$schemas = self::descriptor()['components']['schemas'];

		foreach (self::SCHEMAS as $name) {
			$schema = $schemas[$name];
			$this->assertTrue(condition: $schema['hardValidation'] ?? false, message: $name . ' must validate on import.');
			$nameField = (string) ($schema['configuration']['objectNameField'] ?? '');
			$this->assertArrayHasKey(key: $nameField, array: $schema['properties'], message: $name . ' names a missing name field.');
			foreach ($schema['required'] ?? [] as $required) {
				$this->assertArrayHasKey(key: $required, array: $schema['properties'], message: $name . ' requires an undeclared property ' . $required . '.');
			}
		}

		$document  = $schemas['document'];
		$lifecycle = $document['x-openregister-lifecycle'];
		$statuses  = $document['properties'][$lifecycle['field']]['enum'];
		$this->assertContains(needle: $lifecycle['initial'], haystack: $statuses);
		$this->assertSame(expected: [], actual: array_values(array_diff(array_keys($lifecycle['states']), $statuses)));
		foreach ($lifecycle['transitions'] as $transition => $move) {
			foreach ((array) $move['from'] as $from) {
				$this->assertContains(needle: $from, haystack: $statuses, message: 'Transition ' . $transition . ' leaves an unknown state.');
			}

			$this->assertContains(needle: $move['to'], haystack: $statuses, message: 'Transition ' . $transition . ' enters an unknown state.');
		}

		$this->assertArrayHasKey(key: 'retention', array: $document['x-openregister-archival']);

	}//end testTheDocumentSchemasKeepWhatTheImportReads()

	/**
	 * Every `document` and `documentType` seed fits its schema, and both are seeded.
	 *
	 * @return void
	 */
	public function testTheDocumentSeedsFitTheirSchemas(): void {
		$components = self::descriptor()['components'];
		$counted    = array_fill_keys(self::SCHEMAS, 0);

		foreach ($components['objects'] as $seed) {
			$schemaName = (string) ($seed['@self']['schema'] ?? '');
			if (in_array($schemaName, self::SCHEMAS, true) === false) {
				continue;
			}

			$counted[$schemaName]++;
			$label  = $schemaName . '/' . (string) ($seed['@self']['slug'] ?? '?');
			$errors = self::violations(seed: $seed, schema: $components['schemas'][$schemaName]);
			$this->assertSame(expected: [], actual: $errors, message: 'Seed ' . $label . ' does not fit its schema.');
		}

		$this->assertGreaterThanOrEqual(expected: 7, actual: $counted['documentType'], message: 'The starter set of document types is seeded.');
		$this->assertGreaterThanOrEqual(expected: 3, actual: $counted['document'], message: 'Example documents are seeded.');

	}//end testTheDocumentSeedsFitTheirSchemas()

	/**
	 * A seed whose field breaks its schema is caught: the checker itself is tested.
	 *
	 * @return void
	 */
	public function testABrokenSeedIsCaught(): void {
		$schema = self::descriptor()['components']['schemas']['document'];

		$errors = self::violations(
			seed: [
				'@self' => [],
				'title' => 'x',
				'status' => 'verzonnen',
				'confidentiality' => 'openbaar',
				'unknownField' => 1,
				'creationDate' => '14-02-2026',
			],
			schema: $schema
		);

		$this->assertContains(needle: 'status: not one of the allowed values', haystack: $errors);
		$this->assertContains(needle: 'unknownField: not a property of the schema', haystack: $errors);
		$this->assertContains(needle: 'creationDate: not a date', haystack: $errors);
		$this->assertContains(needle: 'title: missing', haystack: self::violations(seed: ['@self' => []], schema: $schema));

	}//end testABrokenSeedIsCaught()

	/**
	 * What in a seed does not fit its schema: unknown fields, missing required
	 * ones, and values of the wrong type, length, pattern, format or enum.
	 *
	 * @param array<string, mixed> $seed   The seed object.
	 * @param array<string, mixed> $schema The schema.
	 *
	 * @return list<string> One line per violation; empty when the seed fits.
	 */
	private static function violations(array $seed, array $schema): array {
		$errors     = [];
		$properties = $schema['properties'];
		unset($seed['@self']);

		foreach ($schema['required'] ?? [] as $required) {
			if (array_key_exists($required, $seed) === false) {
				$errors[] = $required . ': missing';
			}
		}

		foreach ($seed as $field => $value) {
			if (array_key_exists($field, $properties) === false) {
				$errors[] = $field . ': not a property of the schema';
				continue;
			}

			$errors = array_merge($errors, self::valueViolations(field: $field, value: $value, property: $properties[$field]));
		}

		return $errors;

	}//end violations()

	/**
	 * What in one value does not fit its property.
	 *
	 * @param string               $field    The field name.
	 * @param mixed                $value    The value.
	 * @param array<string, mixed> $property The property declaration.
	 *
	 * @return list<string> One line per violation.
	 */
	private static function valueViolations(string $field, mixed $value, array $property): array {
		$type = (string) ($property['type'] ?? '');
		if ($type === 'array') {
			if (is_array($value) === false || array_is_list($value) === false) {
				return [$field . ': not a list'];
			}

			$errors = [];
			foreach ($value as $item) {
				$errors = array_merge($errors, self::valueViolations(field: $field . '[]', value: $item, property: (array) ($property['items'] ?? [])));
			}

			return $errors;
		}

		if ($type === 'boolean') {
			if (is_bool($value) === true) {
				return [];
			}

			return [$field . ': not a boolean'];
		}

		if ($type !== 'string') {
			return [];
		}

		if (is_string($value) === false) {
			return [$field . ': not a string'];
		}

		$errors = [];
		if (isset($property['enum']) === true && in_array($value, $property['enum'], true) === false) {
			$errors[] = $field . ': not one of the allowed values';
		}

		if (isset($property['maxLength']) === true && mb_strlen($value) > (int) $property['maxLength']) {
			$errors[] = $field . ': longer than ' . $property['maxLength'];
		}

		if (isset($property['pattern']) === true && preg_match('/' . str_replace('/', '\/', (string) $property['pattern']) . '/u', $value) !== 1) {
			$errors[] = $field . ': does not match its pattern';
		}

		if (($property['format'] ?? '') === 'date' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
			$errors[] = $field . ': not a date';
		}

		return $errors;

	}//end valueViolations()
}//end class
