<?php

/**
 * Unit tests for the property types the register declares
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec exclude bug fix: OpenRegister's schema import refuses a union type, which dropped two schemas
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Pins that every property declares one type, never a union array.
 *
 * OpenRegister's PropertyValidatorHandler accepts `type` only as one string
 * out of its vocabulary. `["string", "null"]` fails that check, the import
 * drops the whole schema with "Invalid type", and the import as a whole still
 * answers 200. That is how `intakeDocument` and `classificationResult` went
 * missing on every instance. A property that may be empty stays out of
 * `required`; OpenRegister widens it to accept null when it validates a write.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SchemaTypeVocabularyTest extends TestCase {

	/**
	 * Every property, at every depth, declares its type as a single string.
	 *
	 * @return void
	 *
	 * @spec exclude bug fix: OpenRegister's schema import refuses a union type, which dropped two schemas
	 */
	public function testNoPropertyDeclaresAUnionType(): void {
		$raw = file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json');
		$this->assertIsString(actual: $raw);
		$parsed = json_decode($raw, true);
		$this->assertIsArray(actual: $parsed);

		$schemas = $parsed['components']['schemas'];
		$this->assertNotEmpty(actual: $schemas);

		$unions = [];
		foreach ($schemas as $key => $schema) {
			$this->collectUnions(properties: ($schema['properties'] ?? []), path: $key, unions: $unions);
		}

		$this->assertSame(expected: [], actual: $unions, message: 'OpenRegister refuses these schemas on import.');

	}//end testNoPropertyDeclaresAUnionType()

	/**
	 * Collect the paths of properties whose `type` is not a string.
	 *
	 * @param array<string, mixed> $properties The properties to walk.
	 * @param string               $path       The path so far.
	 * @param array<int, string>   $unions     The offending paths, appended to.
	 *
	 * @return void
	 */
	private function collectUnions(array $properties, string $path, array &$unions): void {
		foreach ($properties as $name => $property) {
			if (is_array($property) === false) {
				continue;
			}

			$here = $path . '/' . $name;
			if (isset($property['type']) === true && is_string($property['type']) === false) {
				$unions[] = $here;
			}

			if (is_array($property['properties'] ?? null) === true) {
				$this->collectUnions(properties: $property['properties'], path: $here, unions: $unions);
			}

			if (is_array($property['items']['properties'] ?? null) === true) {
				$this->collectUnions(properties: $property['items']['properties'], path: $here . '[]', unions: $unions);
			}
		}

	}//end collectUnions()
}//end class
