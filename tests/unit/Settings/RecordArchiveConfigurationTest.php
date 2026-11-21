<?php

/**
 * The record schemas OpenRegister's archival stack manages, and the
 * auto-delete annotation that must stay off them.
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
 * @spec openspec/changes/archiefwet-retention-engine/tasks.md#task-1.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Correspondence, generated documents and publication records carry an
 * `archive` block in the keys OpenRegister reads; no record schema carries
 * `x-openregister-archival`, and every remaining annotation has the object shape.
 *
 * The keys were read on OpenRegister `development`: `RetentionService::applyArchivalMetadata()`
 * reads `enabled`, `defaultNominatie` and `defaultBewaartermijn`, `ClassificationOverride`
 * reads `classification`, and `ArchiveActionDateCalculator` reads `afleidingswijze`
 * with `closureField` (afgehandeld, termijn) or `bronEigenschap` (eigenschap).
 */
class RecordArchiveConfigurationTest extends TestCase {

	/**
	 * Per record schema: the derivation method and the key naming its date field.
	 */
	private const RECORDS = [
		'correspondence'    => ['afgehandeld', 'closureField', 'generatedAt'],
		'generatedDocument' => ['afgehandeld', 'closureField', 'generatedAt'],
		'publicationRecord' => ['eigenschap', 'bronEigenschap', 'publicatiedatum'],
	];

	/**
	 * The schemas of the register.
	 *
	 * @return array<string, array<string, mixed>> Schemas by slug.
	 */
	private function schemas(): array {
		$parsed = json_decode(json: (string) file_get_contents(filename: __DIR__ . '/../../../lib/Settings/filinq_register.json'), associative: true);
		$this->assertIsArray(actual: $parsed);

		return $parsed['components']['schemas'];

	}//end schemas()

	/**
	 * The three record schemas switch archiving on with a category and a date
	 * field that exists on the schema.
	 *
	 * @return void
	 */
	public function testTheRecordSchemasCarryAnArchiveBlock(): void {
		$schemas = $this->schemas();

		foreach (self::RECORDS as $slug => [$method, $key, $field]) {
			$archive = $schemas[$slug]['archive'];
			$this->assertTrue(condition: $archive['enabled'], message: $slug);
			$this->assertSame(expected: $method, actual: $archive['afleidingswijze'], message: $slug);
			$this->assertSame(expected: $field, actual: $archive[$key], message: $slug);
			$this->assertArrayHasKey(key: $field, array: $schemas[$slug]['properties'], message: $slug . ' declares its date field');
			$this->assertIsString(actual: $archive['classification'], message: $slug);
			$this->assertArrayNotHasKey(key: 'classificatie', array: $archive, message: 'OpenRegister reads classification');
		}

	}//end testTheRecordSchemasCarryAnArchiveBlock()

	/**
	 * Correspondence keeps its seven-year destroy term as the default, now
	 * through a destruction list rather than the hourly sweep.
	 *
	 * @return void
	 */
	public function testCorrespondenceKeepsItsTermWithoutTheSweep(): void {
		$correspondence = $this->schemas()['correspondence'];

		$this->assertArrayNotHasKey(key: 'x-openregister-archival', array: $correspondence);
		$this->assertSame(expected: 'vernietigen', actual: $correspondence['archive']['defaultNominatie']);
		$this->assertSame(expected: 'P7Y', actual: $correspondence['archive']['defaultBewaartermijn']);

	}//end testCorrespondenceKeepsItsTermWithoutTheSweep()

	/**
	 * The category names are marked placeholders until the selectielijst
	 * manager names the real ones.
	 *
	 * @return void
	 */
	public function testTheCategoriesAreMarkedPlaceholders(): void {
		$schemas = $this->schemas();

		foreach (array_keys(self::RECORDS) as $slug) {
			$this->assertStringStartsWith(prefix: 'TODO-', string: $schemas[$slug]['archive']['classification'], message: $slug);
		}

	}//end testTheCategoriesAreMarkedPlaceholders()

	/**
	 * No schema with an archive category declares the auto-delete annotation (REQ-DDARE-008).
	 *
	 * @return void
	 */
	public function testNoArchivedSchemaCarriesTheSweepAnnotation(): void {
		$both = [];
		foreach ($this->schemas() as $slug => $schema) {
			$classified = (is_array($schema['archive'] ?? null) === true && isset($schema['archive']['classification']) === true);
			if ($classified === true && array_key_exists(key: 'x-openregister-archival', array: $schema) === true) {
				$both[] = $slug;
			}
		}

		$this->assertSame(expected: [], actual: $both);

	}//end testNoArchivedSchemaCarriesTheSweepAnnotation()

	/**
	 * Every remaining annotation uses the object shape OpenRegister validates (REQ-DDARE-008).
	 *
	 * @return void
	 */
	public function testEveryRemainingAnnotationHasTheObjectShape(): void {
		$bare = [];
		foreach ($this->schemas() as $slug => $schema) {
			if (array_key_exists(key: 'x-openregister-archival', array: $schema) === false) {
				continue;
			}

			$retention = ($schema['x-openregister-archival']['retention'] ?? null);
			if (is_array($retention) === false || is_string($retention['default'] ?? null) === false) {
				$bare[] = $slug;
			}
		}

		$this->assertSame(expected: [], actual: $bare);
		$this->assertSame(expected: 'P1Y', actual: $this->schemas()['batchCorrespondenceJob']['x-openregister-archival']['retention']['default']);

	}//end testEveryRemainingAnnotationHasTheObjectShape()

	/**
	 * With an OpenRegister checkout beside the app, its schema entity declares
	 * the `archive` column that `Schema::hydrate()` fills through its setter on
	 * import, and its calculator still reads the keys used here.
	 *
	 * @return void
	 */
	public function testOpenRegisterReadsTheseKeys(): void {
		$root = __DIR__ . '/../../../../openregister/lib';
		if (is_dir(filename: $root) === false) {
			$this->markTestSkipped(message: 'No OpenRegister checkout beside the app; the literals above stand in for it.');
		}

		$schema = (string) file_get_contents(filename: $root . '/Db/Schema.php');
		$calculator = (string) file_get_contents(filename: $root . '/Service/Archival/ArchiveActionDateCalculator.php');

		$this->assertStringContainsString(needle: 'protected ?array $archive', haystack: $schema);
		$this->assertStringContainsString(needle: 'setArchive(', haystack: $schema);
		$this->assertStringContainsString(needle: "'eigenschap' => ['bronEigenschap'", haystack: $calculator);
		$this->assertStringContainsString(needle: "'afgehandeld' => ['closureField'", haystack: $calculator);

	}//end testOpenRegisterReadsTheseKeys()
}//end class
