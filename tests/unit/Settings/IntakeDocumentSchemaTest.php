<?php

/**
 * Unit tests for the intakeDocument declaration
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
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Asserts what the register declares about a document waiting for a record.
 *
 * The register import is the only thing that creates this schema, and it runs
 * at boot with no surface to look at, so what it declares is checked here:
 * the lifecycle, the two terminal states, the English property names dossiq
 * decision D13 asks for, and the seeded examples that keep the inbox from being
 * empty on a fresh install.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class IntakeDocumentSchemaTest extends TestCase {

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
	 * The intakeDocument schema.
	 *
	 * @return array<string, mixed> The schema.
	 */
	private function intakeDocument(): array {
		$schemas = $this->descriptor()['components']['schemas'];
		$this->assertArrayHasKey('intakeDocument', $schemas);

		return $schemas['intakeDocument'];

	}//end intakeDocument()

	/**
	 * The register lists the schema, so the import reaches existing installs.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testTheRegisterListsTheIntakeSchema(): void {
		$descriptor = $this->descriptor();

		$this->assertContains(
			'intakeDocument',
			$descriptor['components']['registers']['filinq']['schemas'],
			'A schema the register does not list is never imported.'
		);
		$this->assertSame('8.5.0', $descriptor['info']['version']);

	}//end testTheRegisterListsTheIntakeSchema()

	/**
	 * A document arrives `received` and leaves for good.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testAssignedAndRejectedAreBothTerminal(): void {
		$lifecycle = $this->intakeDocument()['x-openregister-lifecycle'];

		$this->assertSame('status', $lifecycle['field']);
		$this->assertSame('received', $lifecycle['initial']);
		$this->assertFalse($lifecycle['states']['received']['terminal']);
		$this->assertTrue($lifecycle['states']['assigned']['terminal']);
		$this->assertTrue($lifecycle['states']['rejected']['terminal']);

		foreach ($lifecycle['transitions'] as $name => $transition) {
			$this->assertSame(
				'received',
				$transition['from'],
				'Transition ' . $name . ' leaves a terminal state; a document that left the inbox must stay left.'
			);
		}

	}//end testAssignedAndRejectedAreBothTerminal()

	/**
	 * The three channels, and only those three.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testTheChannelEnumHoldsExactlyTheThreeChannels(): void {
		$this->assertSame(
			['scan', 'mail', 'digitalPost'],
			$this->intakeDocument()['properties']['channel']['enum']
		);

	}//end testTheChannelEnumHoldsExactlyTheThreeChannels()

	/**
	 * Every property the spec names is declared, in English (decision D13).
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testEveryPropertyTheSpecNamesIsDeclared(): void {
		$properties = $this->intakeDocument()['properties'];

		foreach (
			[
				'channel',
				'file',
				'receivedAt',
				'sender',
				'subject',
				'sourceRef',
				'assignedTo',
				'assignedBy',
				'assignedAt',
				'rejectReason',
				'status',
			] as $name
		) {
			$this->assertArrayHasKey($name, $properties, $name . ' is missing from intakeDocument.');
		}

	}//end testEveryPropertyTheSpecNamesIsDeclared()

	/**
	 * Validation is hard: a channel outside the enum must not be stored.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testTheSchemaValidatesHard(): void {
		$this->assertTrue($this->intakeDocument()['hardValidation']);

	}//end testTheSchemaValidatesHard()

	/**
	 * Two waiting examples are seeded, one scanned and one mailed.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testTwoWaitingExamplesAreSeeded(): void {
		$seeds = [];
		foreach ($this->descriptor()['components']['objects'] as $object) {
			if (($object['@self']['schema'] ?? '') === 'intakeDocument') {
				$seeds[] = $object;
			}
		}

		$this->assertCount(2, $seeds);
		$this->assertSame(['scan', 'mail'], array_column($seeds, 'channel'));
		foreach ($seeds as $seed) {
			$this->assertSame('received', $seed['status']);
		}

	}//end testTwoWaitingExamplesAreSeeded()
}//end class
