<?php

/**
 * InboundClassificationService: suggestions, never field writes
 *
 * The real classifier, ranker, matcher, repository and sources; only the
 * OpenRegister edges are doubles (an in-memory object store that validates
 * every classificationResult against the real register fragment, and an
 * EntityRelationMapper double answering detected entities).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/inbound-auto-classification/tasks.md#4-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

require_once __DIR__ . '/Wizard/WizardDoubles.php';
require_once __DIR__ . '/Classification/ClassificationDoubles.php';
require_once __DIR__ . '/Classification/InboundClassificationFixture.php';

use OCA\Filinq\Service\DocumentTypeClassifier;
use OCA\Filinq\Tests\Unit\Service\Classification\ClassificationObjectStore;
use OCA\Filinq\Tests\Unit\Service\Classification\InboundClassificationFixture;
use PHPUnit\Framework\TestCase;

/**
 * Inbound classification over the real classes.
 */
class InboundClassificationServiceTest extends TestCase {

	use InboundClassificationFixture;

	/**
	 * Set up the store.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->store = new ClassificationObjectStore();

	}//end setUp()

	/**
	 * An invoice gets a factuur suggestion with a confidence, and nothing is written onto the document.
	 *
	 * @return void
	 */
	public function testAnInboundInvoiceIsTypedWithConfidence(): void {
		$intake = $this->intake();
		$outcome = $this->service()->classify(objectData: $intake, objectRef: $this->ref());

		$this->assertSame('suggested', $outcome['outcome']);
		$this->assertSame('factuur', $outcome['record']['suggestedDocumentType']);
		$this->assertGreaterThan(DocumentTypeClassifier::MIN_CONFIDENCE, $outcome['record']['documentTypeConfidence']);
		$this->assertSame('suggested', $outcome['record']['status']);
		// Only classificationResult rows were written: the store refuses any other target.
		$this->assertCount(1, $this->store->saves);

	}//end testAnInboundInvoiceIsTypedWithConfidence()

	/**
	 * With the toggle off nothing is created.
	 *
	 * @return void
	 */
	public function testTheToggleDisablesTheSurface(): void {
		$this->toggle = '0';
		$outcome = $this->service()->classify(objectData: $this->intake(), objectRef: $this->ref());

		$this->assertSame(['outcome' => 'skipped', 'reason' => 'disabled'], $outcome);
		$this->assertSame([], $this->store->saves);

	}//end testTheToggleDisablesTheSurface()

	/**
	 * Only inbound documents are classified: the enrichment path sees every
	 * object, and a conformance report or an archive job also carries a file
	 * and a subject.
	 *
	 * @return void
	 */
	public function testOnlyInboundDocumentsAreClassified(): void {
		$report = ['fileId' => 812010, 'subject' => self::INVOICE, 'flavour' => 'pdfa-2b', 'compliant' => true];
		$archiveJob = ['file' => 812011, 'subject' => self::INVOICE, 'status' => 'queued'];

		foreach ([$report, $archiveJob] as $objectData) {
			$outcome = $this->service()->classify(objectData: $objectData, objectRef: ['id' => 'x', 'register' => 'filinq', 'schema' => 'conformanceReport']);
			$this->assertSame(['outcome' => 'skipped', 'reason' => 'not_inbound'], $outcome);
		}

		$this->assertSame([], $this->store->saves);

	}//end testOnlyInboundDocumentsAreClassified()

	/**
	 * A document without text is skipped with the reason, not silently.
	 *
	 * @return void
	 */
	public function testADocumentWithoutTextIsSkippedWithItsReason(): void {
		$outcome = $this->service()->classify(objectData: ['file' => 812020, 'channel' => 'scan', 'status' => 'received'], objectRef: $this->ref());

		$this->assertSame(['outcome' => 'skipped', 'reason' => 'no_text'], $outcome);

	}//end testADocumentWithoutTextIsSkippedWithItsReason()

	/**
	 * The letterhead organisation becomes the correspondent, over three body mentions of a person.
	 *
	 * @return void
	 */
	public function testTheLetterheadOrganisationBecomesTheCorrespondent(): void {
		$this->entities[812010] = [
			['entity_type' => 'ORGANIZATION', 'entity_value' => 'Heijmans B.V.', 'position_start' => 0],
			['entity_type' => 'PERSON', 'entity_value' => 'J. de Vries', 'position_start' => 1400],
			['entity_type' => 'PERSON', 'entity_value' => 'J. de Vries', 'position_start' => 1800],
			['entity_type' => 'PERSON', 'entity_value' => 'J. de Vries', 'position_start' => 2300],
		];

		$record = $this->service()->classify(objectData: $this->intake(), objectRef: $this->ref())['record'];

		$this->assertSame(['name' => 'Heijmans B.V.', 'entityType' => 'ORGANIZATION', 'source' => 'ner'], $record['suggestedCorrespondent']);
		$this->assertFalse($record['correspondentPending']);
		$this->assertSame('mixed', $record['method']);

	}//end testTheLetterheadOrganisationBecomesTheCorrespondent()

	/**
	 * Without detection the type leg stands and the correspondent is pending, not invented.
	 *
	 * @return void
	 */
	public function testNoDetectionYetIsFlaggedNotFabricated(): void {
		$record = $this->service()->classify(objectData: $this->intake(), objectRef: $this->ref())['record'];

		$this->assertSame('factuur', $record['suggestedDocumentType']);
		$this->assertTrue($record['correspondentPending']);
		$this->assertNull($record['suggestedCorrespondent']);
		$this->assertSame('rules', $record['method']);

	}//end testNoDetectionYetIsFlaggedNotFabricated()

	/**
	 * Classified twice with detection in between: one active record, the first superseded.
	 *
	 * @return void
	 */
	public function testOneActiveRecordPerFile(): void {
		$service = $this->service();
		$service->classify(objectData: $this->intake(), objectRef: $this->ref());
		$this->entities[812010] = [['entity_type' => 'ORGANIZATION', 'entity_value' => 'Heijmans B.V.', 'position_start' => 0]];
		$service->classify(objectData: $this->intake(), objectRef: $this->ref());

		$statuses = array_column($this->store->rows['classificationResult'], 'status');
		sort($statuses);
		$this->assertSame(['suggested', 'superseded'], $statuses);

	}//end testOneActiveRecordPerFile()

	/**
	 * A suggestion, however confident, is never applied by a re-run.
	 *
	 * @return void
	 */
	public function testNoSuggestionEverAutoApplies(): void {
		$this->entities[812010] = [['entity_type' => 'ORGANIZATION', 'entity_value' => 'Heijmans B.V.', 'position_start' => 0]];
		$service = $this->service();
		$service->classify(objectData: $this->intake(), objectRef: $this->ref());
		$again = $service->classify(objectData: $this->intake(), objectRef: $this->ref());

		$this->assertSame(['outcome' => 'skipped', 'reason' => 'already_classified'], $again);
		$this->assertCount(1, $this->store->saves);
		$this->assertSame('suggested', array_values($this->store->rows['classificationResult'])[0]['status']);

	}//end testNoSuggestionEverAutoApplies()

	/**
	 * The record carries the correspondent name at most: no text, no other entity values.
	 *
	 * @return void
	 */
	public function testTheRecordCarriesNoDocumentContent(): void {
		$this->entities[812010] = [
			['entity_type' => 'ORGANIZATION', 'entity_value' => 'Heijmans B.V.', 'position_start' => 0],
			['entity_type' => 'PERSON', 'entity_value' => 'Piet Puk', 'position_start' => 900],
			['entity_type' => 'IBAN', 'entity_value' => 'NL91ABNA0417164300', 'position_start' => 120],
		];

		$this->service()->classify(objectData: $this->intake(), objectRef: $this->ref());
		$stored = json_encode($this->store->saves[0]);

		$this->assertStringNotContainsString('Factuurnummer', $stored);
		$this->assertStringNotContainsString('Piet Puk', $stored);
		$this->assertStringNotContainsString('NL91ABNA0417164300', $stored);
		$this->assertStringContainsString('Heijmans B.V.', $stored);

	}//end testTheRecordCarriesNoDocumentContent()

	/**
	 * A dossier whose name starts with the correspondent is suggested; nothing moves.
	 *
	 * @return void
	 */
	public function testAMatchingDossierIsSuggestedNotApplied(): void {
		$this->store->rows['dossier']['00000000-0000-0000-0000-00000000d001'] = ['name' => 'Heijmans B.V. — nieuwbouw'];
		$this->store->rows['dossier']['00000000-0000-0000-0000-00000000d002'] = ['name' => 'Bouwbedrijf Jansen'];
		$this->entities[812010] = [['entity_type' => 'ORGANIZATION', 'entity_value' => 'Heijmans B.V.', 'position_start' => 0]];

		$record = $this->service()->classify(objectData: $this->intake(), objectRef: $this->ref())['record'];

		$this->assertSame('00000000-0000-0000-0000-00000000d001', $record['suggestedDossier']);
		$this->assertSame('suggested', $record['status']);

	}//end testAMatchingDossierIsSuggestedNotApplied()
}//end class
