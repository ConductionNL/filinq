<?php

/**
 * Unit tests for the subjectErasureRequest and erasureCertificate declarations
 *
 * 🔴 THE CERTIFICATE IS THE ONE TO WATCH. It is the only proof that a wissing
 * happened and what was refused. A certificate that can be amended afterwards
 * records what somebody wanted it to say rather than what happened, and the
 * person it concerns is the one least able to check.
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
 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/anonymization-link/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Asserts what the register declares about an erasure request.
 */
class ErasureRequestSchemaTest extends TestCase {

	/**
	 * The register descriptor.
	 *
	 * @return array<string, mixed> The descriptor.
	 */
	private function register(): array {
		$path = dirname(__DIR__, 3).'/lib/Settings/filinq_register.json';
		$decoded = json_decode((string)file_get_contents($path), true);
		$this->assertIsArray($decoded);

		return $decoded;
	}//end register()

	/**
	 * One schema.
	 *
	 * @param string $slug The slug.
	 *
	 * @return array<string, mixed> The schema.
	 */
	private function schema(string $slug): array {
		$schemas = $this->register()['components']['schemas'];
		$this->assertArrayHasKey($slug, $schemas);

		return $schemas[$slug];
	}//end schema()

	/**
	 * Both schemas are declared and on the register, so the import creates them.
	 *
	 * @return void
	 */
	public function testBothSchemasAreDeclaredAndOnTheRegister(): void {
		$onRegister = $this->register()['components']['registers']['filinq']['schemas'];

		foreach (['subjectErasureRequest', 'erasureCertificate'] as $slug) {
			$this->assertContains($slug, $onRegister, $slug);
		}
	}//end testBothSchemasAreDeclaredAndOnTheRegister()

	/**
	 * 🔴 NOBODY AMENDS A CERTIFICATE. It is the only proof the wissing happened
	 * and what was refused, and the person it concerns cannot check it.
	 *
	 * @return void
	 */
	public function testACertificateCannotBeAmendedOrDeleted(): void {
		$authorization = $this->schema('erasureCertificate')['authorization'];

		$this->assertSame([], $authorization['update'], 'a certificate is not editable');
		$this->assertSame([], $authorization['delete'], 'a certificate is not deletable');
	}//end testACertificateCannotBeAmendedOrDeleted()

	/**
	 * 🔴 AND IT IS NOT READABLE BY EVERYONE. A request names an identified
	 * person and lists every document they appear in: reading it is reading a
	 * dossier about them.
	 *
	 * @return void
	 */
	public function testNeitherSchemaIsReadableByAnyAuthenticatedUser(): void {
		foreach (['subjectErasureRequest', 'erasureCertificate'] as $slug) {
			$authorization = $this->schema($slug)['authorization'];

			$this->assertNotContains('authenticated', $authorization['read'], $slug);
			$this->assertNotContains('authenticated', $authorization['create'], $slug);
			$this->assertContains('docudesk-privacy-officer', $authorization['read'], $slug);
		}
	}//end testNeitherSchemaIsReadableByAnyAuthenticatedUser()

	/**
	 * 🔴 THE REQUEST CARRIES ITS OWN PROGRESS, which is what makes an
	 * interrupted run resumable rather than restartable. Restarting re-walks
	 * documents already done while a statutory clock runs.
	 *
	 * @return void
	 */
	public function testTheRequestCarriesTheProgressAResumeNeeds(): void {
		$progress = $this->schema('subjectErasureRequest')['properties']['progress']['properties'];

		foreach (['documentsTotal', 'documentsDone', 'lastDocument', 'occurrencesErased'] as $field) {
			$this->assertArrayHasKey($field, $progress, $field);
		}
	}//end testTheRequestCarriesTheProgressAResumeNeeds()

	/**
	 * A partially completed run has a status of its own, so it cannot be read
	 * as either finished or never started.
	 *
	 * @return void
	 */
	public function testAPartiallyCompletedRunHasAStatusOfItsOwn(): void {
		$statuses = $this->schema('subjectErasureRequest')['properties']['status']['enum'];

		$this->assertContains('partially_completed', $statuses);
		$this->assertContains('running', $statuses);
	}//end testAPartiallyCompletedRunHasAStatusOfItsOwn()

	/**
	 * The request records several identifiers, because one spelling of a name
	 * does not find every occurrence of a person.
	 *
	 * @return void
	 */
	public function testTheRequestTakesMoreThanOneSpellingOfTheName(): void {
		$this->assertArrayHasKey('identifiers', $this->schema('subjectErasureRequest')['properties']);
	}//end testTheRequestTakesMoreThanOneSpellingOfTheName()

	/**
	 * An exclusion carries a reason. A voorkomen left standing with no reason
	 * cannot be justified to the person who asked for it to go.
	 *
	 * @return void
	 */
	public function testAnExclusionCarriesItsReason(): void {
		$exclusion = $this->schema('subjectErasureRequest')['properties']['excluded']['items']['properties'];

		$this->assertArrayHasKey('reason', $exclusion);
	}//end testAnExclusionCarriesItsReason()

	/**
	 * The certificate names what still needs republishing, separately from the
	 * refusals, and that is what stops `complete` being true.
	 *
	 * @return void
	 */
	public function testTheCertificateNamesWhatIsStillPublished(): void {
		$properties = $this->schema('erasureCertificate')['properties'];

		$this->assertArrayHasKey('needsRepublishing', $properties);
		$this->assertArrayHasKey('refused', $properties);
		$this->assertArrayHasKey('complete', $properties);
	}//end testTheCertificateNamesWhatIsStillPublished()

	/**
	 * The certificate outlives the request, because it is the proof.
	 *
	 * @return void
	 */
	public function testTheCertificateOutlivesTheRequest(): void {
		$certificate = $this->schema('erasureCertificate')['x-openregister-archival']['retention']['default'];
		$request = $this->schema('subjectErasureRequest')['x-openregister-archival']['retention']['default'];

		$this->assertSame('P10Y', $certificate);
		$this->assertSame('P5Y', $request);
	}//end testTheCertificateOutlivesTheRequest()

	/**
	 * The erasure is declared as its own processing activity (task 1.2).
	 *
	 * @return void
	 */
	public function testTheErasureIsItsOwnProcessingActivity(): void {
		$processing = $this->schema('subjectErasureRequest')['x-openregister-processing'];

		$this->assertSame('subject-erasure', $processing['activity']);
		$this->assertStringContainsString('AVG artikel 17', $processing['legalBasis']);
		$this->assertNotSame('', trim($processing['purpose']));
	}//end testTheErasureIsItsOwnProcessingActivity()

	/**
	 * The descriptor version is bumped, or the import never reaches an install.
	 *
	 * @return void
	 */
	public function testTheDescriptorVersionIsBumped(): void {
		$this->assertTrue(version_compare((string)$this->register()['info']['version'], '8.12.0', '>'));
	}//end testTheDescriptorVersionIsBumped()
}//end class
