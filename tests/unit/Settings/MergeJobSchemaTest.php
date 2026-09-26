<?php

/**
 * Unit tests for the mergeJob declaration
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
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Asserts what the register declares about a merge. The import runs at boot
 * with no surface to look at, so the lifecycle and the fields are checked here
 * or nowhere.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class MergeJobSchemaTest extends TestCase {

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
	 * The merge job schema.
	 *
	 * @return array<string, mixed> The schema.
	 */
	private function mergeJob(): array {
		$schemas = $this->descriptor()['components']['schemas'];
		$this->assertArrayHasKey('mergeJob', $schemas);

		return $schemas['mergeJob'];

	}//end mergeJob()

	/**
	 * The register lists the schema, so the import reaches existing installs.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function testTheRegisterListsTheMergeJob(): void {
		$descriptor = $this->descriptor();

		$this->assertContains('mergeJob', $descriptor['components']['registers']['filinq']['schemas']);
		$this->assertTrue(
			version_compare((string)$descriptor['info']['version'], '8.9.0', '>='),
			'The descriptor version must be at least the one that added the merge job.'
		);

	}//end testTheRegisterListsTheMergeJob()

	/**
	 * A merge starts queued and ends done or failed, with no way back.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function testDoneAndFailedAreBothTerminal(): void {
		$lifecycle = $this->mergeJob()['x-openregister-lifecycle'];

		$this->assertSame('status', $lifecycle['field']);
		$this->assertSame('queued', $lifecycle['initial']);
		$this->assertFalse($lifecycle['states']['queued']['terminal']);
		$this->assertFalse($lifecycle['states']['running']['terminal']);
		$this->assertTrue($lifecycle['states']['done']['terminal']);
		$this->assertTrue($lifecycle['states']['failed']['terminal']);

		foreach ($lifecycle['transitions'] as $name => $transition) {
			$this->assertNotSame(
				'done',
				$transition['from'],
				'Transition ' . $name . ' leaves a terminal state.'
			);
			$this->assertNotSame(
				'failed',
				$transition['from'],
				'Transition ' . $name . ' leaves a terminal state.'
			);
		}

	}//end testDoneAndFailedAreBothTerminal()

	/**
	 * Every field the spec names is declared.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function testEveryFieldTheSpecNamesIsDeclared(): void {
		$properties = $this->mergeJob()['properties'];

		foreach (
			[
				'inputs',
				'options',
				'requestedBy',
				'hostObject',
				'resultFileId',
				'pageCount',
				'progress',
				'lastError',
				'status',
			] as $name
		) {
			$this->assertArrayHasKey($name, $properties, $name . ' is missing from mergeJob.');
		}

		$this->assertSame('array', $properties['inputs']['type'], 'The inputs are ORDERED; an object would lose that.');
		$this->assertTrue($this->mergeJob()['hardValidation']);

	}//end testEveryFieldTheSpecNamesIsDeclared()

	/**
	 * The job says whether the result really is PDF/A-3b.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function testTheJobSaysWhatTheResultActuallyIs(): void {
		$conformance = $this->mergeJob()['properties']['conformance'];

		$this->assertSame(['pdfa-3b', 'pdf'], $conformance['enum']);
		$this->assertSame('pdf', $conformance['default'], 'The safe default is the weaker claim.');

	}//end testTheJobSaysWhatTheResultActuallyIs()
}//end class
