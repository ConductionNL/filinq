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
	 * Schemas only the server writes.
	 *
	 * @var list<string>
	 */
	private const SERVER_ONLY = [
		'documentVersion',
		'publicationLogEntry',
		'prohibitionOverrideAudit',
		'signerRecord',
		'signingSession',
		'signingRequest',
		'anonymizationLink',
		'sanitizationRecord',
		'erasureCertificate',
		'legalHoldCase',
		'publicationRecord',
		'printJob',
	];

	/**
	 * The classes that create those records.
	 *
	 * @var list<string>
	 */
	private const UNOWNED_WRITERS = [
		'lib/Service/FinalDocumentRepository.php',
		'lib/Service/Publication/PublicationStore.php',
		'lib/Service/ProhibitionOverrideCommitter.php',
		'lib/Service/SigningService.php',
		'lib/Service/Signing/NativeSigningProvider.php',
		'lib/Service/AnonymizationPersistenceService.php',
		'lib/Service/Sanitization/SanitizationRecordRepository.php',
		'lib/Service/SubjectErasure/SubjectErasureStore.php',
		'lib/Service/LegalHold/LegalHoldCaseRepository.php',
		'lib/Service/PrintJobRepository.php',
	];

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
	 * Evidence and audit records are written by the server only.
	 *
	 * Their writers pass `_rbac: false` and `_unowned: true`, so create,
	 * update and delete stay empty: nobody but an admin writes one through the
	 * OpenRegister API, and no user owns one to reopen it through the owner bypass.
	 *
	 * @return void
	 */
	public function testEvidenceSchemasAreWrittenByTheServerOnly(): void {
		$schemas = $this->schemas();

		foreach (self::SERVER_ONLY as $slug) {
			foreach (['create', 'update', 'delete'] as $action) {
				$this->assertSame(
					expected: [],
					actual: $schemas[$slug]['authorization'][$action],
					message: sprintf('%s.%s must stay empty: the record is evidence the server writes', $slug, $action)
				);
			}
		}
	}//end testEvidenceSchemasAreWrittenByTheServerOnly()

	/**
	 * Every server-only schema's writer stores it owned by the system.
	 *
	 * @return void
	 */
	public function testEvidenceWritersStoreRecordsUnowned(): void {
		$root = dirname(__DIR__, 3);
		foreach (self::UNOWNED_WRITERS as $file) {
			$source = (string)file_get_contents($root.'/'.$file);
			$this->assertMatchesRegularExpression(pattern: '/_unowned:\s*true/', string: $source, message: $file.' must save with _unowned: true');
		}
	}//end testEvidenceWritersStoreRecordsUnowned()

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
	/**
	 * Nobody grants themselves a standing consent, and the intake inbox is the clerks' alone.
	 *
	 * @return void
	 */
	public function testConsentAndIntakeAreNotOpenToEveryUser(): void {
		$schemas = $this->schemas();

		$this->assertNotContains(needle: 'authenticated', haystack: $schemas['publicationConsent']['authorization']['create']);
		$this->assertContains(
			needle: ['group' => 'authenticated', 'match' => ['scope' => 'document']],
			haystack: $schemas['publicationConsent']['authorization']['create']
		);
		$this->assertSame(expected: ['filinq-intake-clerks'], actual: $schemas['intakeDocument']['authorization']['read']);
		$this->assertSame(expected: ['filinq-intake-clerks'], actual: $schemas['intakeDocument']['authorization']['update']);
		$this->assertNotContains(needle: 'consent', haystack: $schemas['publicationProhibition']['authorization']['read']);

	}//end testConsentAndIntakeAreNotOpenToEveryUser()
}//end class
