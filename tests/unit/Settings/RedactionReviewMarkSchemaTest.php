<?php

/**
 * Unit tests for the redactionReviewMark and downloadAgreement declarations
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
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-and-what-leaves-the-building/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Asserts what the register declares about a human review of a redaction.
 *
 * The register import is the only thing that creates these schemas, it runs at
 * boot, and there is no surface to look at afterwards. So what it declares is
 * checked here.
 *
 * 🔴 THE CASCADE IS THE ONE TO WATCH. An omitted `authorization` cascade is
 * OPEN in OpenRegister — this descriptor's own v7.9.0 note records that
 * `PermissionHandler::resolveAuthorization()` returns null and every caller is
 * admitted. On a review mark that means any authenticated user could write the
 * approval for their own document, which defeats the gate through the object
 * model rather than through any code path anybody would review.
 */
class RedactionReviewMarkSchemaTest extends TestCase {

	/**
	 * The register descriptor.
	 *
	 * @return array<string, mixed> The decoded descriptor.
	 */
	private function register(): array {
		$path = dirname(__DIR__, 3).'/lib/Settings/filinq_register.json';
		$this->assertFileExists($path);

		$decoded = json_decode((string)file_get_contents($path), true);
		$this->assertIsArray($decoded, 'the register descriptor must be readable JSON');

		return $decoded;
	}//end register()

	/**
	 * One schema from the descriptor.
	 *
	 * @param string $slug The schema slug.
	 *
	 * @return array<string, mixed> The schema.
	 */
	private function schema(string $slug): array {
		$schemas = $this->register()['components']['schemas'];
		$this->assertArrayHasKey($slug, $schemas, sprintf('%s must be declared', $slug));

		return $schemas[$slug];
	}//end schema()

	/**
	 * Both schemas are declared and named on the register, so the import
	 * actually creates them.
	 *
	 * @return void
	 */
	public function testBothSchemasAreDeclaredAndOnTheRegister(): void {
		$register = $this->register();
		$onRegister = $register['components']['registers']['filinq']['schemas'];

		foreach (['redactionReviewMark', 'downloadAgreement'] as $slug) {
			$this->assertArrayHasKey($slug, $register['components']['schemas']);
			$this->assertContains($slug, $onRegister, sprintf('%s must be listed on the register', $slug));
		}
	}//end testBothSchemasAreDeclaredAndOnTheRegister()

	/**
	 * 🔴 THE VERSION BUMP IS THE POINT. SettingsInitializer gates the import on
	 * `info.version` against the stored configuration version, so a schema
	 * shipped without a bump never reaches an existing install and the gate
	 * there keeps refusing every document because no mark can be stored.
	 *
	 * @return void
	 */
	public function testTheDescriptorVersionIsBumped(): void {
		$version = (string)$this->register()['info']['version'];

		$this->assertTrue(
			version_compare($version, '8.10.0', '>'),
			'the descriptor version must be strictly greater than the previous release'
		);
	}//end testTheDescriptorVersionIsBumped()

	/**
	 * 🔴 A MARK NOBODY MAY WRITE BUT ITS SUBJECT IS NO CONTROL AT ALL. An
	 * omitted cascade is open, so this asserts the cascade exists and that
	 * creating a mark is restricted rather than left to any authenticated user.
	 *
	 * @return void
	 */
	public function testAMarkCannotBeSignedByJustAnybody(): void {
		$authorization = $this->schema('redactionReviewMark')['authorization'];

		$this->assertIsArray($authorization, 'an omitted cascade is OPEN in OpenRegister');
		$this->assertNotContains(
			'authenticated',
			$authorization['create'],
			'any authenticated user creating a mark would let somebody approve their own document'
		);
		$this->assertContains('docudesk-woo-officers', $authorization['create']);
	}//end testAMarkCannotBeSignedByJustAnybody()

	/**
	 * Amending or removing a mark is restricted too: a mark that can be edited
	 * afterwards records what somebody wanted it to say, not what happened.
	 *
	 * @return void
	 */
	public function testAMarkCannotBeAmendedByJustAnybody(): void {
		$authorization = $this->schema('redactionReviewMark')['authorization'];

		foreach (['update', 'delete'] as $action) {
			$this->assertNotContains('authenticated', $authorization[$action], $action);
			$this->assertContains('docudesk-policy-admins', $authorization[$action], $action);
		}
	}//end testAMarkCannotBeAmendedByJustAnybody()

	/**
	 * 🔴 THE MARK BINDS TO A DETECTION RUN, NOT ONLY TO A DOCUMENT. Without the
	 * run on the record there is nothing for the gate to compare, and every
	 * mark looks current forever.
	 *
	 * @return void
	 */
	public function testTheMarkNamesTheDetectionRunItCovers(): void {
		$schema = $this->schema('redactionReviewMark');

		$this->assertContains('detectionRun', $schema['required']);
		$this->assertContains('document', $schema['required']);
		$this->assertArrayHasKey('detectionRun', $schema['properties']);
	}//end testTheMarkNamesTheDetectionRunItCovers()

	/**
	 * A check nobody signed is a check nobody can be asked about, so both the
	 * person and the moment are required rather than optional.
	 *
	 * @return void
	 */
	public function testTheMarkRequiresWhoCheckedItAndWhen(): void {
		$schema = $this->schema('redactionReviewMark');

		$this->assertContains('checkedBy', $schema['required']);
		$this->assertContains('checkedAt', $schema['required']);
	}//end testTheMarkRequiresWhoCheckedItAndWhen()

	/**
	 * 🔴 THE DETECTOR VERSION IS RECORDED, WHICH IS WHAT MAKES A FLEET-WIDE
	 * MODEL UPGRADE ANSWERABLE. An upgrade does not invalidate existing marks —
	 * doing that silently would refuse thousands of already-approved documents
	 * in one night — so the version is stored to make the question askable
	 * instead: which approved documents were checked under the old model?
	 *
	 * @return void
	 */
	public function testTheMarkRecordsWhichDetectorVersionCheckedIt(): void {
		$properties = $this->schema('redactionReviewMark')['properties'];

		$this->assertArrayHasKey('detectorVersion', $properties);
		$this->assertTrue(
			($properties['detectorVersion']['facetable'] ?? false),
			'it must be facetable, or the question cannot be asked across a fleet'
		);
	}//end testTheMarkRecordsWhichDetectorVersionCheckedIt()

	/**
	 * The download agreement records which version was accepted separately from
	 * the current version, so an acceptance of an older text stays visible
	 * instead of sliding forward when the text is changed.
	 *
	 * @return void
	 */
	public function testAnAcceptanceNamesTheVersionItAccepted(): void {
		$properties = $this->schema('downloadAgreement')['properties'];

		$this->assertArrayHasKey('acceptedVersion', $properties);
		$this->assertArrayHasKey('version', $properties);
		$this->assertArrayHasKey('acceptedBy', $properties);
		$this->assertArrayHasKey('acceptedAt', $properties);
	}//end testAnAcceptanceNamesTheVersionItAccepted()

	/**
	 * Both schemas declare a cascade at all, which is the check that would have
	 * caught the 20-of-21 gap this descriptor's v7.9.0 note records.
	 *
	 * @return void
	 */
	public function testEveryNewSchemaDeclaresACascade(): void {
		foreach (['redactionReviewMark', 'downloadAgreement'] as $slug) {
			$authorization = ($this->schema($slug)['authorization'] ?? null);

			$this->assertIsArray($authorization, sprintf('%s must declare a cascade', $slug));
			foreach (['read', 'create', 'update', 'delete'] as $action) {
				$this->assertArrayHasKey($action, $authorization, sprintf('%s.%s', $slug, $action));
			}
		}
	}//end testEveryNewSchemaDeclaresACascade()
}//end class
