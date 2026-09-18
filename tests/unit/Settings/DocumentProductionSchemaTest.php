<?php

/**
 * Unit tests for the layout, periodic and archive declarations
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
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Asserts what the register declares about the paper, the bundle, the schedule
 * and the review, none of which has a surface that would show it otherwise.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DocumentProductionSchemaTest extends TestCase {

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
	 * The three new schemas are declared and listed on the register.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testTheThreeSchemasAreDeclaredAndListed(): void {
		$descriptor = $this->descriptor();
		$listed = $descriptor['components']['registers']['filinq']['schemas'];

		foreach (['pageLayout', 'periodicDocument', 'archiveJob'] as $slug) {
			$this->assertArrayHasKey($slug, $descriptor['components']['schemas']);
			$this->assertContains($slug, $listed);
			$this->assertTrue($descriptor['components']['schemas'][$slug]['hardValidation']);
		}

	}//end testTheThreeSchemasAreDeclaredAndListed()

	/**
	 * A layout carries a version and what it supersedes, which is what keeps
	 * an April edit from rewriting a March besluit.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testALayoutIsVersionedAndSaysWhatItReplaced(): void {
		$layout = $this->descriptor()['components']['schemas']['pageLayout'];

		$this->assertSame('integer', $layout['properties']['layoutVersion']['type']);
		$this->assertArrayHasKey('supersedes', $layout['properties']);
		$this->assertContains('layoutVersion', $layout['required']);
		foreach (['paperSize', 'margins', 'header', 'footer', 'logo', 'firstPageDiffers'] as $name) {
			$this->assertArrayHasKey($name, $layout['properties']);
		}

	}//end testALayoutIsVersionedAndSaysWhatItReplaced()

	/**
	 * A generated document records the layout version, the view and the review.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testAGeneratedDocumentRecordsWhatMadeItAndWhenItComesBack(): void {
		$document = $this->descriptor()['components']['schemas']['generatedDocument'];

		foreach (
			[
				'layoutId',
				'layoutVersion',
				'viewSlug',
				'recordCount',
				'reviewInterval',
				'reviewDate',
				'reviewedAt',
				'owner',
			] as $name
		) {
			$this->assertArrayHasKey($name, $document['properties'], $name . ' is missing from generatedDocument.');
		}

		$this->assertSame('1.1.0', $document['version']);
		$this->assertArrayHasKey(
			'documentDueForReview',
			$document['x-openregister-notifications'],
			'A due document notifies its owner through the notification dialect, not through a bespoke mail.'
		);

	}//end testAGeneratedDocumentRecordsWhatMadeItAndWhenItComesBack()

	/**
	 * An archive job records the ceiling, both counts and the manifest.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testAnArchiveJobRecordsWhatWasHandedOver(): void {
		$job = $this->descriptor()['components']['schemas']['archiveJob'];

		foreach (['ceilingBytes', 'includedCount', 'excludedCount', 'manifest'] as $name) {
			$this->assertArrayHasKey($name, $job['properties']);
		}

		$lifecycle = $job['x-openregister-lifecycle'];
		$this->assertSame('pending', $lifecycle['initial']);
		$this->assertTrue($lifecycle['states']['completed']['terminal']);
		$this->assertTrue($lifecycle['states']['failed']['terminal']);

	}//end testAnArchiveJobRecordsWhatWasHandedOver()

	/**
	 * One briefpapier is seeded, so a fresh install has paper to print on.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testOneLayoutIsSeeded(): void {
		$seeded = [];
		foreach ($this->descriptor()['components']['objects'] as $object) {
			if (($object['@self']['schema'] ?? '') === 'pageLayout') {
				$seeded[] = $object;
			}
		}

		$this->assertCount(1, $seeded);
		$this->assertSame(1, $seeded[0]['layoutVersion']);
		$this->assertTrue($seeded[0]['firstPageDiffers'], 'Briefpapier has a different first page; that is what makes it briefpapier.');

	}//end testOneLayoutIsSeeded()
}//end class
