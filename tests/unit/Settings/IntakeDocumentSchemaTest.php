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
		$this->assertSame('8.7.0', $descriptor['info']['version']);

	}//end testTheRegisterListsTheIntakeSchema()

	/**
	 * A document arrives `received`, and a rejection is the end of it.
	 *
	 * `assigned` stopped being terminal when the worklist arrived: a document
	 * filed on the wrong record is detached and comes back, which is exactly
	 * what the worklist is for. A REJECTION is still the end: it says the
	 * document does not belong in this organisation at all.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testARejectionIsTerminalAndADetachmentIsNot(): void {
		$lifecycle = $this->intakeDocument()['x-openregister-lifecycle'];

		$this->assertSame('status', $lifecycle['field']);
		$this->assertSame('received', $lifecycle['initial']);
		$this->assertFalse($lifecycle['states']['received']['terminal']);
		$this->assertFalse($lifecycle['states']['assigned']['terminal']);
		$this->assertTrue($lifecycle['states']['rejected']['terminal']);
		$this->assertFalse($lifecycle['states']['detached']['terminal']);

		foreach ($lifecycle['transitions'] as $name => $transition) {
			$this->assertNotSame(
				'rejected',
				$transition['from'],
				'Transition ' . $name . ' leaves the rejected state; a rejection must stay the end.'
			);
		}

		$this->assertSame('assigned', $lifecycle['transitions']['detach']['from']);
		$this->assertSame('detached', $lifecycle['transitions']['detach']['to']);
		$this->assertSame('detached', $lifecycle['transitions']['reassign']['from']);

	}//end testARejectionIsTerminalAndADetachmentIsNot()

	/**
	 * Everything the worklist change added is declared.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testTheWorklistFieldsAreDeclared(): void {
		$properties = $this->intakeDocument()['properties'];

		foreach (
			[
				'arrivedWith',
				'stampedDefaults',
				'defaultRule',
				'partySuggestion',
				'routing',
				'acceptance',
				'classificationProgress',
				'detachReason',
				'detachedBy',
				'attachmentNotes',
			] as $name
		) {
			$this->assertArrayHasKey($name, $properties, $name . ' is missing from intakeDocument.');
		}

		$this->assertContains('detached', $properties['status']['enum']);

	}//end testTheWorklistFieldsAreDeclared()

	/**
	 * Party extraction is declared as its own processing activity.
	 *
	 * The suggestion reads a name and an address out of somebody's letter, so
	 * it is a processing activity in its own right, with a purpose, a legal
	 * basis, the categories it touches and a retention. An activity nobody
	 * declared is one no register of processing activities can report.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testPartyExtractionIsItsOwnProcessingActivity(): void {
		$activity = $this->intakeDocument()['x-openregister-processing'];

		$this->assertSame('docudesk-party-extraction', $activity['code']);
		$this->assertNotSame('', $activity['doelbinding']);
		$this->assertSame('public-task', $activity['rechtsgrond']);
		$this->assertContains('PERSON', $activity['dataCategories']);
		$this->assertStringContainsString('P1Y', $activity['retentionReference']);

	}//end testPartyExtractionIsItsOwnProcessingActivity()

	/**
	 * The two rule schemas and the corrections corpus are in the register.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testTheRuleSchemasAndTheCorpusAreRegistered(): void {
		$descriptor = $this->descriptor();
		$listed = $descriptor['components']['registers']['filinq']['schemas'];

		foreach (['intakeDefaultRule', 'intakeRoutingRule', 'intakePartyCorrection'] as $slug) {
			$this->assertArrayHasKey($slug, $descriptor['components']['schemas']);
			$this->assertContains($slug, $listed);
		}

		$this->assertSame(
			['accepted', 'edited', 'rejected'],
			$descriptor['components']['schemas']['intakePartyCorrection']['properties']['decision']['enum']
		);

	}//end testTheRuleSchemasAndTheCorpusAreRegistered()

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
