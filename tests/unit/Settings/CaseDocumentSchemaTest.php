<?php

/**
 * Unit tests for the case-document declarations
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
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Asserts that a document record carries domains rather than one owner, and
 * that the upload policy is an administered object with a seeded default.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CaseDocumentSchemaTest extends TestCase {

	/**
	 * The parsed register descriptor.
	 *
	 * @return array<string, mixed> The descriptor.
	 */
	private function descriptor(): array {
		$raw = file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json');
		$this->assertIsString($raw, 'The register descriptor must be readable.');

		$parsed = json_decode($raw, true);
		$this->assertIsArray($parsed, 'The register descriptor must be valid JSON.');

		return $parsed;

	}//end descriptor()

	/**
	 * A document record carries domains, plural, and names its creator.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testADocumentRecordCarriesDomainsRatherThanOneOwner(): void {
		$record = $this->descriptor()['components']['schemas']['documentVersion'];

		$this->assertSame('array', $record['properties']['domains']['type']);
		$this->assertArrayHasKey('createdByUser', $record['properties']);
		$this->assertSame('1.1.0', $record['version'], 'The schema version must move or the import never reaches existing installs.');

	}//end testADocumentRecordCarriesDomainsRatherThanOneOwner()

	/**
	 * The upload policy is declared, and one standard policy is seeded.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testTheUploadPolicyIsDeclaredAndSeeded(): void {
		$descriptor = $this->descriptor();
		$policy = $descriptor['components']['schemas']['uploadPolicy'];

		$this->assertTrue($policy['hardValidation']);
		foreach (['allowedExtensions', 'allowedMediaTypes', 'maxSizeBytes', 'refuseUnknownType'] as $name) {
			$this->assertArrayHasKey($name, $policy['properties']);
		}

		$this->assertContains('uploadPolicy', $descriptor['components']['registers']['filinq']['schemas']);

		$seeded = [];
		foreach ($descriptor['components']['objects'] as $object) {
			if (($object['@self']['schema'] ?? '') === 'uploadPolicy') {
				$seeded[] = $object;
			}
		}

		$this->assertCount(1, $seeded);
		$this->assertTrue($seeded[0]['active']);
		$this->assertTrue(
			$seeded[0]['refuseUnknownType'],
			'The seeded policy refuses a file whose type cannot be read: that is the renamed executable.'
		);
		$this->assertNotContains(
			'exe',
			$seeded[0]['allowedExtensions'],
			'The seeded policy must not allow an executable extension.'
		);

	}//end testTheUploadPolicyIsDeclaredAndSeeded()

	/**
	 * The descriptor version moved, so the import reaches existing installs.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testTheDescriptorVersionMoved(): void {
		$this->assertSame('8.7.0', $this->descriptor()['info']['version']);

	}//end testTheDescriptorVersionMoved()
}//end class
