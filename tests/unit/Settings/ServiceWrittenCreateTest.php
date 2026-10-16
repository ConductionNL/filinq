<?php

/**
 * Which schemas may be created by any authenticated user, and why.
 *
 * 🔴 THE CASCADE IS THE EFFECTIVE PERMISSION WHEN THE SERVICE DOES NOT BYPASS
 * IT. Filinq's own writes mostly pass `_rbac: false`, which is why the v7.9.0
 * note says the cascade governs only direct OpenRegister API access. For a
 * service that does NOT pass it, the cascade is what decides whether the write
 * succeeds — so restricting create there does not close a hole, it breaks the
 * feature.
 *
 * That is not hypothetical: v7.7.0 records restricting
 * `prohibitionOverrideAudit.create` raising 500 on every use of a fail-closed
 * override path.
 *
 * So this file records the DECISION per schema, and the reason, rather than
 * leaving a future sweep to rediscover it and get it wrong in either direction.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/specs/consumer-schema-authorization-audit/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Asserts the create cascade of the three service-written schemas.
 */
class ServiceWrittenCreateTest extends TestCase {

	/**
	 * The declared schemas.
	 *
	 * @return array<string, mixed> The schemas.
	 */
	private function schemas(): array {
		$path = dirname(__DIR__, 3).'/lib/Settings/filinq_register.json';
		$decoded = json_decode((string)file_get_contents($path), true);
		$this->assertIsArray($decoded);

		return $decoded['components']['schemas'];
	}//end schemas()

	/**
	 * 🔴 NOTHING WRITES signingAuditEntry ANY MORE. The entries migrated to
	 * OpenRegister's own audit and its config keys are deprecated, so there is
	 * no path to break — and an open create on an audit shape is a forgeable
	 * signing history standing for no reason.
	 *
	 * @return void
	 */
	public function testTheRetiredAuditSchemaNoLongerAcceptsAnybodysCreate(): void {
		$create = $this->schemas()['signingAuditEntry']['authorization']['create'];

		$this->assertNotContains('authenticated', $create);
		$this->assertContains('docudesk-signing-admins', $create);
	}//end testTheRetiredAuditSchemaNoLongerAcceptsAnybodysCreate()

	/**
	 * 🔴 AND THESE TWO STAY OPEN ON PURPOSE. Both are written from
	 * #[NoAdminRequired] endpoints by ordinary users, and neither write passes
	 * `_rbac: false`, so the acting user is that user. Restricting create would
	 * fail the anonymise and the finalisation for everyone outside the group.
	 *
	 * The assertion is deliberately the opposite way round from the one above:
	 * a later sweep that "tidies" these two closed would break a feature, and
	 * this is what stops it.
	 *
	 * @return void
	 */
	public function testTheTwoUserWrittenSchemasStayOpenWithTheirReasonRecorded(): void {
		$schemas = $this->schemas();

		foreach (['anonymizationLink', 'documentVersion'] as $slug) {
			$this->assertContains(
				'authenticated',
				$schemas[$slug]['authorization']['create'],
				sprintf('%s is written by the acting user; restricting create breaks the feature', $slug)
			);

			$this->assertStringContainsString(
				'DELIBERATELY',
				$schemas[$slug]['description'],
				sprintf('%s must carry the reason it is open, or the next sweep re-opens the question', $slug)
			);
			$this->assertStringContainsString('NoAdminRequired', $schemas[$slug]['description']);
		}
	}//end testTheTwoUserWrittenSchemasStayOpenWithTheirReasonRecorded()

	/**
	 * The descriptor version is bumped, or the change never reaches an install.
	 *
	 * @return void
	 */
	public function testTheDescriptorVersionIsBumped(): void {
		$path = dirname(__DIR__, 3).'/lib/Settings/filinq_register.json';
		$version = (string)json_decode((string)file_get_contents($path), true)['info']['version'];

		$this->assertTrue(version_compare($version, '8.11.0', '>'));
	}//end testTheDescriptorVersionIsBumped()

	/**
	 * Every schema still declares a cascade, so this edit did not drop one.
	 *
	 * @return void
	 */
	public function testEverySchemaStillDeclaresACascade(): void {
		foreach ($this->schemas() as $slug => $schema) {
			$this->assertIsArray(
				($schema['authorization'] ?? null),
				sprintf('%s must declare a cascade; an omitted one is OPEN', $slug)
			);
		}
	}//end testEverySchemaStillDeclaresACascade()
}//end class
