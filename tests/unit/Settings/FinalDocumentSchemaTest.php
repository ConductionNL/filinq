<?php

/**
 * Unit tests for the documentVersion lifecycle declaration
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
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Asserts what the register declares about a final version.
 *
 * The lifecycle is the mechanism, not a boolean somebody sets, so the terminal
 * state and the absence of a way back are properties of the descriptor and are
 * checked there. A transition out of `final` added later fails here, which is
 * the only place it would be visible before it shipped.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FinalDocumentSchemaTest extends TestCase {

	/**
	 * The parsed register descriptor.
	 *
	 * @return array<string, mixed> The descriptor.
	 */
	private function descriptor(): array {
		$path = __DIR__ . '/../../../lib/Settings/filinq_register.json';
		$raw = file_get_contents($path);
		$this->assertIsString($raw, 'The register descriptor must be readable.');

		$parsed = json_decode($raw, true);
		$this->assertIsArray($parsed, 'The register descriptor must be valid JSON.');

		return $parsed;

	}//end descriptor()

	/**
	 * The documentVersion schema.
	 *
	 * @return array<string, mixed> The schema.
	 */
	private function documentVersion(): array {
		$schemas = $this->descriptor()['components']['schemas'];
		$this->assertArrayHasKey('documentVersion', $schemas);

		return $schemas['documentVersion'];

	}//end documentVersion()

	/**
	 * A version is draft or final, and final is terminal with no way back.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testFinalIsATerminalLifecycleStateWithNoTransitionOut(): void {
		$lifecycle = $this->documentVersion()['x-openregister-lifecycle'];

		$this->assertSame('status', $lifecycle['field']);
		$this->assertSame('draft', $lifecycle['initial']);
		$this->assertSame(['draft', 'final'], array_keys($lifecycle['states']));
		$this->assertFalse($lifecycle['states']['draft']['terminal']);
		$this->assertTrue($lifecycle['states']['final']['terminal']);

		foreach ($lifecycle['transitions'] as $name => $transition) {
			$from = $transition['from'];
			if (is_array($from) === false) {
				$from = [$from];
			}

			$this->assertNotContains(
				'final',
				$from,
				sprintf('Transition "%s" leaves the final state, and final has no way out.', $name)
			);
		}

	}//end testFinalIsATerminalLifecycleStateWithNoTransitionOut()

	/**
	 * Reaching final records who, when, why and the file's checksum.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testTheSchemaCarriesTheFactsAFinalVersionMustRecord(): void {
		$properties = $this->documentVersion()['properties'];

		foreach (['finalisedBy', 'finalisedAt', 'finalReason', 'fileChecksum', 'supersedes', 'unfrozen'] as $field) {
			$this->assertArrayHasKey($field, $properties, sprintf('A final version records "%s".', $field));
		}

		$this->assertSame(['draft', 'final'], $properties['status']['enum']);
		$this->assertSame('draft', $properties['status']['default']);

	}//end testTheSchemaCarriesTheFactsAFinalVersionMustRecord()

	/**
	 * The descriptor version is bumped, or the import never reaches an install.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testTheDescriptorVersionIsAtLeastTheOneThatShipsThisSchema(): void {
		$this->assertGreaterThanOrEqual(
			0,
			version_compare((string)$this->descriptor()['info']['version'], '8.3.0'),
			'A schema shipped without a descriptor bump never reaches an existing install.'
		);

	}//end testTheDescriptorVersionIsAtLeastTheOneThatShipsThisSchema()

	/**
	 * A consuming app's declaration has somewhere to live.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testTheFinalityRuleSchemaExists(): void {
		$schemas = $this->descriptor()['components']['schemas'];
		$this->assertArrayHasKey('documentFinalityRule', $schemas);

		$properties = $schemas['documentFinalityRule']['properties'];
		foreach (['declaringApp', 'typeReference', 'finalStates'] as $field) {
			$this->assertArrayHasKey($field, $properties);
		}

	}//end testTheFinalityRuleSchemaExists()
}//end class
