<?php

/**
 * Unit tests for the slug every register schema is imported under
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
 * @spec openspec/changes/filinq-configurable-report-templates/specs/template-management/spec.md#requirement-template-slug-resolution-req-tmpl-13
 */

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Pins that every schema keeps its own slug, and that the template's `slug`
 * and `tenantId` fields are properties rather than schema keys.
 *
 * A property definition written one level too high lands beside the schema's
 * own keys. For `tenantId` that only leaves the field undeclared. For `slug`
 * it is a duplicate JSON key: `json_decode` keeps the LAST one, so the
 * schema's slug `template` became the property definition object. The
 * OpenRegister import refuses a fragment whose slug is not a string and
 * continues with the others, so the whole `template` schema is skipped while
 * the import as a whole still reports success.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SchemaSlugIntegrityTest extends TestCase {

	/**
	 * The parsed register descriptor.
	 *
	 * @return array<string, mixed> The descriptor.
	 */
	private function descriptor(): array {
		$raw = file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json');
		$this->assertIsString($raw);

		$parsed = json_decode($raw, true);
		$this->assertIsArray($parsed);

		return $parsed;

	}//end descriptor()

	/**
	 * Every schema is imported under a string slug equal to its component key.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/template-management/spec.md#requirement-template-slug-resolution-req-tmpl-13
	 */
	public function testEverySchemaCarriesItsOwnKeyAsAStringSlug(): void {
		$schemas = $this->descriptor()['components']['schemas'];
		$this->assertNotEmpty($schemas);

		foreach ($schemas as $key => $schema) {
			$this->assertIsString(
				$schema['slug'] ?? null,
				'schema ' . $key . ' has no string slug, so the import skips it.'
			);
			$this->assertSame($key, $schema['slug'], 'schema ' . $key . ' is imported under another slug.');
		}

	}//end testEverySchemaCarriesItsOwnKeyAsAStringSlug()

	/**
	 * The template declares `slug` and `tenantId` as properties, and neither
	 * sits among the schema's own keys.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/template-management/spec.md#requirement-template-slug-resolution-req-tmpl-13
	 */
	public function testTheTemplateDeclaresSlugAndTenantAsProperties(): void {
		$template = $this->descriptor()['components']['schemas']['template'];

		foreach (['slug', 'tenantId'] as $field) {
			$declaration = $template['properties'][$field] ?? null;
			$this->assertIsArray($declaration, $field . ' is not declared as a template property.');
			$this->assertSame('string', $declaration['type']);
		}

		$this->assertArrayNotHasKey('tenantId', $template, 'tenantId sits among the schema keys, not the properties.');

	}//end testTheTemplateDeclaresSlugAndTenantAsProperties()
}//end class
