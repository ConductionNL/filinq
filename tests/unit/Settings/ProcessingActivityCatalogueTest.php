<?php

/**
 * Unit tests for the AVG Art. 30 processing-activity catalogue annotations
 * added to `filinq_register.json` by the `processing-activity-export` change.
 *
 * Filinq is a THIN CONSUMER of OpenRegister's platform processing-activity
 * register (OR-PA-1..9): it declares its four processing activities as
 * `x-openregister-processing` catalogue annotations and opts the carrying
 * schemas into OpenRegister's per-access read-logging. The aggregation,
 * export, no-literal-PII contract, and access gating live in OpenRegister
 * (ADR-022); Filinq owns NO export service, controller, or template.
 *
 * These tests assert:
 *   - the four activities are declared with the required catalogue fields;
 *   - each annotation opts the schema into read-logging and attributes to its
 *     own activity code (resolvable by OpenRegister's ProcessingLogService);
 *   - retention references mirror the existing `x-openregister-archival`
 *     annotations ("not declared" where the schema has none);
 *   - Filinq ships NO route or controller that aggregates / exports
 *     processing activities (the export is OR-PA-7's).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author  Conduction Development Team <info@conduction.nl>
 * @license EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/specs/processing-activity-export/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Verifies the processing-activity catalogue contract + the no-export guard.
 */
class ProcessingActivityCatalogueTest extends TestCase {

	/**
	 * Decoded register configuration.
	 *
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * Map of carrying schema => expected activity code.
	 *
	 * @var array<string, string>
	 */
	private const ACTIVITY_SCHEMAS = [
		'anonymizationLink' => 'docudesk-anonymisation',
		'generatedDocument' => 'docudesk-ocr',
		'base' => 'docudesk-metadata-enrichment',
		'signingAuditEntry' => 'docudesk-signing',
	];

	/**
	 * Load the register configuration once per test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$path = __DIR__ . '/../../../lib/Settings/filinq_register.json';
		$raw = file_get_contents($path);
		$this->assertNotFalse($raw, 'filinq_register.json must be readable');

		$decoded = json_decode($raw, true);
		$this->assertIsArray($decoded, 'filinq_register.json must be valid JSON');
		$this->config = $decoded;

	}//end setUp()

	/**
	 * The four activities are declared, each on its carrying schema, with the
	 * required Art. 30 catalogue fields and its own attribution code.
	 *
	 * @return void
	 */
	public function testFourActivitiesDeclaredWithCatalogueFields(): void {
		$schemas = $this->config['components']['schemas'] ?? [];
		$codes = [];

		foreach (self::ACTIVITY_SCHEMAS as $schemaName => $expectedCode) {
			$this->assertArrayHasKey($schemaName, $schemas, "schema $schemaName MUST exist");
			$processing = $schemas[$schemaName]['x-openregister-processing'] ?? null;
			$this->assertIsArray($processing, "$schemaName MUST carry x-openregister-processing");

			// Required Art. 30 catalogue fields.
			foreach (['code', 'naam', 'doelbinding', 'rechtsgrond', 'dataCategories', 'backend', 'retentionReference', 'grondslagSource'] as $field) {
				$this->assertArrayHasKey($field, $processing, "$schemaName.$field MUST be declared");
				$this->assertNotEmpty($processing[$field], "$schemaName.$field MUST be non-empty");
			}

			$this->assertSame($expectedCode, $processing['code'], "$schemaName MUST declare code $expectedCode");
			$this->assertSame('EntityRelation.bases', $processing['grondslagSource'], 'grondslag MUST source from EntityRelation.bases (OR-PA-4)');
			$codes[] = $processing['code'];
		}

		$this->assertCount(4, array_unique($codes), 'Exactly four distinct activity codes expected');

	}//end testFourActivitiesDeclaredWithCatalogueFields()

	/**
	 * Each annotation opts the schema into read-logging and attributes reads
	 * to its own activity code (resolvable by OpenRegister by code).
	 *
	 * @return void
	 */
	public function testActivitiesOptInToReadLoggingAndSelfAttribute(): void {
		$schemas = $this->config['components']['schemas'] ?? [];

		foreach (self::ACTIVITY_SCHEMAS as $schemaName => $expectedCode) {
			$processing = $schemas[$schemaName]['x-openregister-processing'];

			$this->assertTrue(
				($processing['logReads'] ?? false) === true,
				"$schemaName MUST opt into per-access read logging (logReads: true)"
			);

			$this->assertArrayHasKey('attribution', $processing, "$schemaName MUST declare attribution");
			$this->assertSame(
				$expectedCode,
				$processing['attribution']['default'] ?? null,
				"$schemaName attribution.default MUST reference its own activity code"
			);

			$this->assertArrayHasKey('subjectIdFields', $processing, "$schemaName MUST declare subjectIdFields (may be empty)");
			$this->assertIsArray($processing['subjectIdFields']);
		}

	}//end testActivitiesOptInToReadLoggingAndSelfAttribute()

	/**
	 * Retention references mirror the schema's existing `x-openregister-archival`
	 * annotation, and stay visible as "not declared" when the schema has none.
	 *
	 * @return void
	 */
	public function testRetentionReferencesMirrorArchivalOrStayVisible(): void {
		$schemas = $this->config['components']['schemas'] ?? [];

		foreach (self::ACTIVITY_SCHEMAS as $schemaName => $code) {
			$processing = $schemas[$schemaName]['x-openregister-processing'];
			$hasArchival = isset($schemas[$schemaName]['x-openregister-archival']);
			$reference = (string)$processing['retentionReference'];

			if ($hasArchival === false) {
				$this->assertStringContainsStringIgnoringCase(
					'not declared',
					$reference,
					"$schemaName has no x-openregister-archival, so retentionReference MUST read 'not declared'"
				);
			} else {
				$this->assertStringNotContainsStringIgnoringCase(
					'not declared',
					$reference,
					"$schemaName HAS x-openregister-archival, so retentionReference MUST cite it, not 'not declared'"
				);
			}
		}

	}//end testRetentionReferencesMirrorArchivalOrStayVisible()

	/**
	 * The register requires the OpenRegister version that ships the
	 * per-access read-logging dialect (>= 0.2.14).
	 *
	 * @return void
	 */
	public function testRegisterRequiresProcessingCapableOpenRegister(): void {
		$constraint = (string)($this->config['x-openregister']['openregister'] ?? '');
		$this->assertNotSame('', $constraint, 'OpenRegister version constraint MUST be declared');

		// Extract the minor version from a `^vX.Y.Z` style constraint.
		$this->assertMatchesRegularExpression('/0\.2\.(1[4-9]|[2-9][0-9]|[3-9])/', $constraint, 'OpenRegister constraint MUST be >= 0.2.14');

	}//end testRegisterRequiresProcessingCapableOpenRegister()

	/**
	 * Filinq ships NO endpoint that aggregates / exports processing
	 * activities — that surface is OpenRegister's (OR-PA-7), per ADR-022.
	 *
	 * @return void
	 */
	public function testNoFilinqProcessingExportEndpointExists(): void {
		$routesPath = __DIR__ . '/../../../appinfo/routes.php';
		$routes = require $routesPath;
		$this->assertIsArray($routes);

		$names = [];
		foreach (($routes['routes'] ?? []) as $route) {
			$names[] = strtolower((string)($route['name'] ?? ''));
		}

		$forbidden = ['verwerkingen', 'processingactivit', 'art30', 'art-30', 'verwerkingsregister'];
		foreach ($names as $name) {
			foreach ($forbidden as $needle) {
				$this->assertStringNotContainsString(
					$needle,
					$name,
					"Filinq MUST NOT register a processing-activity export route ($name) — the export is OR-PA-7's"
				);
			}
		}

		// No controller class implements an aggregation/export surface.
		$controllerDir = __DIR__ . '/../../../lib/Controller';
		$this->assertDirectoryExists($controllerDir);
		$hits = glob($controllerDir . '/*ProcessingActivit*Controller.php') ?: [];
		$hits = array_merge($hits, (glob($controllerDir . '/*Verwerking*Controller.php') ?: []));
		$this->assertSame([], $hits, 'Filinq MUST NOT ship a processing-activity / verwerkingsregister controller');

	}//end testNoFilinqProcessingExportEndpointExists()
}//end class
