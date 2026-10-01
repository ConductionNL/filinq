<?php

/**
 * Tests for ClassificationDecisionService
 *
 * Confirm, correct and reject over the real repository and the real
 * MetadataService; every classificationResult and intakeDocument write is
 * validated with Opis against its real register fragment.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Classification
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

namespace OCA\Filinq\Tests\Unit\Service\Classification;

require_once __DIR__ . '/ClassificationDecisionFixture.php';

use OCA\Filinq\Service\Classification\ClassificationRefused;
use PHPUnit\Framework\TestCase;

/**
 * A person's decision on a suggestion.
 */
class ClassificationDecisionServiceTest extends TestCase {
	use ClassificationDecisionFixture;

	/**
	 * Fresh stores.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->resetFixture();

	}//end setUp()

	/**
	 * Confirming applies the type and correspondent to the document and closes the record.
	 *
	 * @return void
	 */
	public function testConfirmingAppliesCanonicalMetadata(): void {
		$this->suggestion(fileId: 812010);
		$this->reachable(userId: 'annemarie', fileId: 812010);

		$record = $this->decisions()->confirm(fileId: 812010, userId: 'annemarie', choices: []);

		$this->assertSame('confirmed', $record['status']);
		$this->assertSame('factuur', $record['confirmedDocumentType']);
		$this->assertSame('annemarie', $record['confirmedBy']);
		$this->assertSame('2026-10-02T09:30:00+00:00', $record['confirmedAt']);
		$this->assertNull($record['filing']);
		$this->assertCount(1, $this->documentSaves);
		$this->assertSame('factuur', $this->documentSaves[0]['documentType']);
		$this->assertSame('Heijmans B.V.', $this->documentSaves[0]['correspondent']['name']);
		// The rest of the document is kept as it was.
		$this->assertSame('scan', $this->documentSaves[0]['channel']);
		$this->assertSame([], $this->moves);

	}//end testConfirmingAppliesCanonicalMetadata()

	/**
	 * A correction keeps what the classifier said beside what the person decided.
	 *
	 * @return void
	 */
	public function testCorrectingRecordsTheCorpusRow(): void {
		$this->suggestion(fileId: 812010, fields: ['suggestedDocumentType' => 'brief']);
		$this->reachable(userId: 'annemarie', fileId: 812010);

		$record = $this->decisions()->confirm(
			fileId: 812010,
			userId: 'annemarie',
			choices: ['documentType' => 'besluit', 'correspondent' => ['name' => '  Gemeente Tilburg ', 'entityType' => 'ORGANIZATION']]
		);

		$this->assertSame('brief', $record['suggestedDocumentType']);
		$this->assertSame('besluit', $record['confirmedDocumentType']);
		$this->assertSame(['name' => 'Gemeente Tilburg', 'entityType' => 'ORGANIZATION', 'source' => 'person'], $record['confirmedCorrespondent']);
		$this->assertSame('besluit', $this->documentSaves[0]['documentType']);

	}//end testCorrectingRecordsTheCorpusRow()

	/**
	 * A typed correspondent without a known entity type is a person; a blank one clears it.
	 *
	 * @return void
	 */
	public function testCorrespondentChoicesAreNormalised(): void {
		$this->suggestion(fileId: 1);
		$this->suggestion(fileId: 2);
		$this->reachable(userId: 'annemarie', fileId: 1);
		$this->reachable(userId: 'annemarie', fileId: 2);

		$person = $this->decisions()->confirm(fileId: 1, userId: 'annemarie', choices: ['correspondent' => ['name' => 'J. de Vries', 'entityType' => 'ROBOT']]);
		$blank = $this->decisions()->confirm(fileId: 2, userId: 'annemarie', choices: ['correspondent' => ['name' => '   ']]);

		$this->assertSame('PERSON', $person['confirmedCorrespondent']['entityType']);
		$this->assertNull($blank['confirmedCorrespondent']);
		$this->assertNull($this->documentSaves[1]['correspondent']);

	}//end testCorrespondentChoicesAreNormalised()

	/**
	 * Rejecting closes the record and touches neither the document nor the file.
	 *
	 * @return void
	 */
	public function testRejectionHasNoCanonicalEffect(): void {
		$this->suggestion(fileId: 812010, fields: ['suggestedDossier' => 'dossier-1']);
		$this->reachable(userId: 'annemarie', fileId: 812010);
		$this->dossierFolder(userId: 'annemarie', dossier: 'dossier-1', folderId: 900);

		$record = $this->decisions()->reject(fileId: 812010, userId: 'annemarie');

		$this->assertSame('rejected', $record['status']);
		$this->assertSame('annemarie', $record['confirmedBy']);
		$this->assertSame([], $this->documentSaves);
		$this->assertSame([], $this->moves);

	}//end testRejectionHasNoCanonicalEffect()

	/**
	 * Confirming with the dossier moves the file into the dossier's bound folder.
	 *
	 * @return void
	 */
	public function testConfirmationFilesTheDocument(): void {
		$this->suggestion(fileId: 812010, fields: ['suggestedDossier' => 'dossier-1']);
		$this->reachable(userId: 'annemarie', fileId: 812010);
		$this->dossierFolder(userId: 'annemarie', dossier: 'dossier-1', folderId: 900);

		$record = $this->decisions()->confirm(fileId: 812010, userId: 'annemarie', choices: []);

		$this->assertSame('moved', $record['filing']);
		$this->assertSame('dossier-1', $record['confirmedDossier']);
		$this->assertSame(['/annemarie/files/Dossiers/Heijmans/scan.pdf'], $this->moves);

	}//end testConfirmationFilesTheDocument()

	/**
	 * A dossier folder the reviewer cannot reach, or a dossier that is gone, is recorded, not moved.
	 *
	 * @return void
	 */
	public function testAnUnreachableDossierIsRecordedNotMoved(): void {
		$this->suggestion(fileId: 1, fields: ['suggestedDossier' => 'dossier-1']);
		$this->suggestion(fileId: 2, fields: ['suggestedDossier' => 'dossier-gone']);
		$this->reachable(userId: 'annemarie', fileId: 1);
		$this->reachable(userId: 'annemarie', fileId: 2);
		// The dossier's folder exists only in another user's tree.
		$this->dossierFolder(userId: 'bram', dossier: 'dossier-1', folderId: 900);

		$this->assertSame('recorded', $this->decisions()->confirm(fileId: 1, userId: 'annemarie', choices: [])['filing']);
		$this->assertSame('recorded', $this->decisions()->confirm(fileId: 2, userId: 'annemarie', choices: [])['filing']);
		$this->assertSame([], $this->moves);

	}//end testAnUnreachableDossierIsRecordedNotMoved()

	/**
	 * The reviewer can drop the suggested dossier: nothing moves.
	 *
	 * @return void
	 */
	public function testDroppingTheDossierFilesNothing(): void {
		$this->suggestion(fileId: 812010, fields: ['suggestedDossier' => 'dossier-1']);
		$this->reachable(userId: 'annemarie', fileId: 812010);
		$this->dossierFolder(userId: 'annemarie', dossier: 'dossier-1', folderId: 900);

		$record = $this->decisions()->confirm(fileId: 812010, userId: 'annemarie', choices: ['dossier' => null]);

		$this->assertNull($record['confirmedDossier']);
		$this->assertNull($record['filing']);
		$this->assertSame([], $this->moves);

	}//end testDroppingTheDossierFilesNothing()

	/**
	 * A file outside the reviewer's folder answers 404, exactly like a file without a suggestion.
	 *
	 * @return void
	 */
	public function testAFileTheReviewerCannotReachIsNotFound(): void {
		$this->suggestion(fileId: 812010);
		$this->reachable(userId: 'bram', fileId: 812010);
		$this->reachable(userId: 'annemarie', fileId: 5);

		foreach ([812010, 5, 0] as $fileId) {
			try {
				$this->decisions()->confirm(fileId: $fileId, userId: 'annemarie', choices: []);
				$this->fail('expected a refusal for ' . $fileId);
			} catch (ClassificationRefused $e) {
				$this->assertSame(404, $e->getCode());
				$this->assertSame('No suggestion for this file', $e->getMessage());
			}
		}

		$this->assertSame([], $this->store->saves);
		$this->assertSame([], $this->documentSaves);

	}//end testAFileTheReviewerCannotReachIsNotFound()

	/**
	 * A decided suggestion cannot be decided again.
	 *
	 * @return void
	 */
	public function testADecidedSuggestionIsAConflict(): void {
		$this->suggestion(fileId: 812010);
		$this->reachable(userId: 'annemarie', fileId: 812010);
		$this->decisions()->reject(fileId: 812010, userId: 'annemarie');

		$this->expectException(ClassificationRefused::class);
		$this->expectExceptionCode(409);
		$this->decisions()->confirm(fileId: 812010, userId: 'annemarie', choices: []);

	}//end testADecidedSuggestionIsAConflict()

	/**
	 * An unknown type is refused before anything is written.
	 *
	 * @return void
	 */
	public function testAnUnknownTypeIsRefused(): void {
		$this->suggestion(fileId: 812010);
		$this->reachable(userId: 'annemarie', fileId: 812010);

		try {
			$this->decisions()->confirm(fileId: 812010, userId: 'annemarie', choices: ['documentType' => 'memo']);
			$this->fail('expected a refusal');
		} catch (ClassificationRefused $e) {
			$this->assertSame(422, $e->getCode());
		}

		$this->assertSame([], $this->documentSaves);
		$this->assertSame([], $this->store->saves);

	}//end testAnUnknownTypeIsRefused()

	/**
	 * A suggestion without a document object still confirms: only the record changes.
	 *
	 * @return void
	 */
	public function testASuggestionWithoutAnObjectConfirmsTheRecordOnly(): void {
		$this->suggestion(fileId: 812010, fields: ['objectId' => '']);
		$this->reachable(userId: 'annemarie', fileId: 812010);

		$record = $this->decisions()->confirm(fileId: 812010, userId: 'annemarie', choices: []);

		$this->assertSame('confirmed', $record['status']);
		$this->assertSame([], $this->documentSaves);

	}//end testASuggestionWithoutAnObjectConfirmsTheRecordOnly()

	/**
	 * The pending list holds only open suggestions on files the reviewer can reach.
	 *
	 * @return void
	 */
	public function testPendingListsOnlyReachableOpenSuggestions(): void {
		$this->suggestion(fileId: 1);
		$this->suggestion(fileId: 2);
		$this->suggestion(fileId: 3, fields: ['status' => 'confirmed']);
		$this->reachable(userId: 'annemarie', fileId: 1);
		$this->reachable(userId: 'annemarie', fileId: 3);
		$this->reachable(userId: 'bram', fileId: 2);

		$pending = $this->decisions()->pending(userId: 'annemarie');

		$this->assertSame([1], array_column($pending, 'fileId'));
		$this->assertSame('classification-1', $pending[0]['uuid']);

	}//end testPendingListsOnlyReachableOpenSuggestions()
}//end class
