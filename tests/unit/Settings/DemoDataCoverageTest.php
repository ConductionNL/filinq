<?php

/**
 * Every schema the register supplies ships three demo objects that its own
 * schema accepts.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude demo-data coverage (ADR-111 rule 1, hydra gate-101); the same rule the gate applies, run in the suite so it is red before push
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use OCA\Filinq\Tests\Unit\Service\Wizard\WizardObjectStore;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Service/Wizard/WizardDoubles.php';

/**
 * The mock register covers the real register, object by object.
 */
class DemoDataCoverageTest extends TestCase {

	/**
	 * Read a descriptor under lib/Settings.
	 *
	 * @param string $file The file name.
	 *
	 * @return array<string, mixed> The parsed descriptor.
	 */
	private function descriptor(string $file): array {
		$parsed = json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/' . $file), true);
		$this->assertIsArray($parsed, $file . ' does not parse');

		return $parsed;

	}//end descriptor()

	/**
	 * The schemas the register supplies that owe demo data.
	 *
	 * @return array<string, array<string, mixed>> Schema definitions by slug.
	 */
	private function schemasOwingDemoData(): array {
		$register = $this->descriptor(file: 'filinq_register.json');
		$owing = [];
		foreach ($register['components']['registers']['filinq']['schemas'] as $slug) {
			$schema = $register['components']['schemas'][$slug];
			if (array_key_exists('x-openregister-demo-data', $schema) === true && $schema['x-openregister-demo-data'] !== true) {
				continue;
			}

			$owing[$slug] = $schema;
		}

		return $owing;

	}//end schemasOwingDemoData()

	/**
	 * The demo objects per schema slug.
	 *
	 * @return array<string, array<int, array<string, mixed>>> Rows by schema slug.
	 */
	private function demoRows(): array {
		$rows = [];
		foreach ($this->descriptor(file: 'filinq_mock_register.json')['components']['objects'] as $row) {
			$rows[(string) ($row['@self']['schema'] ?? '')][] = $row;
		}

		return $rows;

	}//end demoRows()

	/**
	 * The demo objects per schema slug, decoded as JSON objects so an empty
	 * object stays an object for the validator.
	 *
	 * @return array<string, array<int, object>> Rows by schema slug.
	 */
	private function demoObjects(): array {
		$parsed = json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_mock_register.json'));
		$rows = [];
		foreach ($parsed->components->objects as $row) {
			$rows[(string) ($row->{'@self'}->schema ?? '')][] = $row;
		}

		return $rows;

	}//end demoObjects()

	/**
	 * Every schema has at least three demo objects.
	 *
	 * @return void
	 */
	public function testEverySchemaHasThreeDemoObjects(): void {
		$rows = $this->demoRows();
		$short = [];
		foreach (array_keys($this->schemasOwingDemoData()) as $slug) {
			if (count($rows[$slug] ?? []) < 3) {
				$short[] = $slug;
			}
		}

		$this->assertSame([], $short, 'schemas with fewer than three demo objects');

	}//end testEverySchemaHasThreeDemoObjects()

	/**
	 * Every demo object is accepted by its schema, with no property the schema
	 * does not declare, and names the filinq register.
	 *
	 * @return void
	 */
	public function testEveryDemoObjectValidatesAgainstItsSchema(): void {
		$schemas = $this->schemasOwingDemoData();
		$failures = [];
		foreach ($this->demoObjects() as $slug => $rows) {
			if (isset($schemas[$slug]) === false) {
				continue;
			}

			$json = $this->validatorSchema(schema: $schemas[$slug]);
			foreach ($rows as $index => $row) {
				if (($row->{'@self'}->register ?? '') !== 'filinq') {
					$failures[] = $slug . '#' . $index . ': @self.register is not filinq';
				}

				unset($row->{'@self'}, $row->uuid);
				$result = (new Validator())->validate($row, $json);
				if ($result->isValid() === false) {
					$failures[] = $slug . '#' . $index . ': ' . json_encode((new ErrorFormatter())->format($result->error()));
				}
			}
		}

		$this->assertSame([], $failures);

	}//end testEveryDemoObjectValidatesAgainstItsSchema()

	/**
	 * Demo slugs are unique within a schema, so an import does not fold two
	 * demo objects into one.
	 *
	 * @return void
	 */
	public function testDemoSlugsAreUniquePerSchema(): void {
		$duplicates = [];
		foreach ($this->demoRows() as $slug => $rows) {
			$slugs = array_map(static fn (array $row): string => (string) ($row['@self']['slug'] ?? ''), $rows);
			foreach (array_count_values($slugs) as $value => $count) {
				if ($value === '' || $count > 1) {
					$duplicates[] = $slug . ':' . $value;
				}
			}
		}

		$this->assertSame([], $duplicates);

	}//end testDemoSlugsAreUniquePerSchema()

	/**
	 * The JSON schema a demo row is validated with: the register schema's
	 * properties without OpenRegister's presentation keys, closed, with the
	 * null widening OpenRegister applies to every property that is not required.
	 *
	 * @param array<string, mixed> $schema The register schema.
	 *
	 * @return string The validator schema as JSON.
	 */
	private function validatorSchema(array $schema): string {
		$properties = [];
		foreach ($schema['properties'] as $name => $property) {
			$properties[$name] = $this->stripPresentation(property: $property);
		}

		$closed = json_decode(
			(string) json_encode(
				[
					'type'                 => 'object',
					'required'             => ($schema['required'] ?? []),
					'properties'           => (object) $properties,
					'additionalProperties' => false,
				]
			)
		);

		return (string) json_encode(WizardObjectStore::widenOptionalToNull(schema: $closed));

	}//end validatorSchema()

	/**
	 * Remove presentation keys (recursively) that are not JSON Schema.
	 *
	 * @param array<string, mixed> $property The property definition.
	 *
	 * @return array<string, mixed> The property without presentation keys.
	 */
	private function stripPresentation(array $property): array {
		unset($property['required'], $property['visible'], $property['order'], $property['facetable'], $property['x-enum-labels'], $property['title']);
		if (isset($property['items']) === true && is_array($property['items']) === true) {
			$property['items'] = $this->stripPresentation(property: $property['items']);
		}

		if (isset($property['properties']) === true && is_array($property['properties']) === true) {
			foreach ($property['properties'] as $name => $child) {
				$property['properties'][$name] = $this->stripPresentation(property: $child);
			}
		}

		return $property;

	}//end stripPresentation()
}//end class
