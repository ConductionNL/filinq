<?php

/**
 * Erasing a person across documents: refusals first, the preview before any
 * write, every write audited, the records kept.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SubjectErasure
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-5.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SubjectErasure;

use OCA\Filinq\Exception\SubjectErasureRefusedException;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureAudit;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureEraser;
use PHPUnit\Framework\TestCase;

/**
 * The real request, preview, run and certificate services over in-memory
 * files, catalogue, versions and register.
 */
class SubjectErasureRunTest extends TestCase {
	use SubjectErasureDoubles;

	private const PERSON = 'Jan Jansen';

	private const EMAIL = 'jan.jansen@voorbeeld.example';

	/**
	 * Two documents naming the person, one naming somebody else too.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->addFile(fileId: 11, name: 'aanvraag.txt', content: 'Aanvrager: Jan Jansen, jan.jansen@voorbeeld.example.');
		$this->addFile(fileId: 12, name: 'advies.txt', content: 'Jan Jansen en Marieke de Vries spraken af. Jan Jansen tekende.');
		$this->catalogueHas(type: 'PERSON', value: self::PERSON, fileIds: [11, 12, 12]);
		$this->catalogueHas(type: 'EMAIL', value: self::EMAIL, fileIds: [11]);
		$this->catalogueHas(type: 'PERSON', value: 'Marieke de Vries', fileIds: [12]);

	}//end setUp()

	/**
	 * Place a request as the privacy officer.
	 *
	 * @param array<string, mixed> $services The wired services.
	 *
	 * @return string The request uuid.
	 */
	private function placed(array $services): string {
		return (string) $services['service']->create(
			input: ['subject' => self::PERSON, 'identifiers' => [self::EMAIL], 'ground' => 'AVG artikel 17 lid 1 onder a'],
			userId: 'petra'
		)['uuid'];

	}//end placed()

	/**
	 * Run a callable that must be refused and return the refusal.
	 *
	 * @param callable $action The action.
	 *
	 * @return SubjectErasureRefusedException The refusal.
	 */
	private function refusal(callable $action): SubjectErasureRefusedException {
		try {
			$action();
		} catch (SubjectErasureRefusedException $refusal) {
			return $refusal;
		}

		$this->fail('The step was expected to be refused.');

	}//end refusal()

	/**
	 * The file contents, for "nothing changed" assertions.
	 *
	 * @return array<int, string> Content by file id.
	 */
	private function contents(): array {
		return array_map(static fn (array $file): string => (string) $file['content'], $this->files);

	}//end contents()

	/**
	 * Somebody outside the erasure groups is refused before anything is read,
	 * and the denial is audited.
	 *
	 * @return void
	 */
	public function testAnOutsiderIsRefusedAndTheDenialIsAudited(): void {
		$services = $this->erasure();

		$refusal = $this->refusal(fn () => $services['service']->create(input: ['subject' => self::PERSON, 'ground' => 'art. 17'], userId: 'bob'));

		$this->assertSame(SubjectErasureRefusedException::REASON_NOT_ALLOWED, $refusal->getReason());
		$this->assertSame([], ($this->rows['subjectErasureRequest'] ?? []));
		$this->assertSame(0, $this->catalogueReads);
		$this->assertSame([SubjectErasureAudit::ACTION_DENIED], array_column($this->auditEntries, 0));

	}//end testAnOutsiderIsRefusedAndTheDenialIsAudited()

	/**
	 * A group setting that does not parse refuses everyone, admins included.
	 *
	 * @return void
	 */
	public function testABrokenGroupSettingRefusesEvenAnAdmin(): void {
		$this->erasureGroups = '{"not": "a list"}';
		$services = $this->erasure();

		$refusal = $this->refusal(fn () => $services['service']->list(userId: 'root'));

		$this->assertSame(SubjectErasureRefusedException::REASON_CONFIG_UNREADABLE, $refusal->getReason());

	}//end testABrokenGroupSettingRefusesEvenAnAdmin()

	/**
	 * The request records the subject, the ground and the requester before
	 * anything is looked up, in a payload the real schema accepts.
	 *
	 * @return void
	 */
	public function testTheRequestIsARecordBeforeAnythingIsLookedUp(): void {
		$services = $this->erasure();

		$uuid = $this->placed(services: $services);

		$request = $this->rows['subjectErasureRequest'][$uuid];
		$this->assertSame([self::PERSON, self::EMAIL], $request['identifiers']);
		$this->assertSame('petra', $request['requester']);
		$this->assertSame('received', $request['status']);
		$this->assertSame('2026-10-29T10:00:00+00:00', $request['dueAt']);
		$this->assertSame(0, $this->catalogueReads);
		$this->assertValidAgainstSchema(schema: 'subjectErasureRequest', payload: $request);

	}//end testTheRequestIsARecordBeforeAnythingIsLookedUp()

	/**
	 * Nothing runs before a preview was read.
	 *
	 * @return void
	 */
	public function testARunBeforeThePreviewIsRefused(): void {
		$services = $this->erasure();
		$uuid = $this->placed(services: $services);
		$before = $this->contents();

		$refusal = $this->refusal(fn () => $services['run']->run(uuid: $uuid, userId: 'petra'));

		$this->assertSame(SubjectErasureRefusedException::REASON_WRONG_STATE, $refusal->getReason());
		$this->assertSame($before, $this->contents());

	}//end testARunBeforeThePreviewIsRefused()

	/**
	 * The preview lists every document with its count, writes no file, and its
	 * audit entry carries counts, never the person.
	 *
	 * @return void
	 */
	public function testThePreviewShowsTheBlastRadiusAndWritesNothing(): void {
		$services = $this->erasure();
		$uuid = $this->placed(services: $services);
		$before = $this->contents();

		$preview = $services['service']->preview(uuid: $uuid, userId: 'petra');

		$this->assertSame($before, $this->contents());
		$this->assertSame(2, $preview['documentsTotal']);
		$this->assertSame(200, $preview['cap']);
		$this->assertSame(4, $preview['occurrencesTotal']);
		$this->assertSame(['11', '12'], array_column($preview['documents'], 'document'));
		$this->assertSame([2, 2], array_column($preview['documents'], 'occurrences'));
		$this->assertSame('previewed', $this->rows['subjectErasureRequest'][$uuid]['status']);
		$this->assertStringNotContainsString(self::PERSON, (string) json_encode($this->auditEntries));

	}//end testThePreviewShowsTheBlastRadiusAndWritesNothing()

	/**
	 * The run takes the person out of every document, keeps every document,
	 * deletes the earlier versions that still name them, and certifies it.
	 *
	 * @return void
	 */
	public function testTheRecordsStayWhileThePersonGoes(): void {
		$services = $this->erasure();
		$uuid = $this->placed(services: $services);
		$services['service']->preview(uuid: $uuid, userId: 'petra');

		$outcome = $services['run']->run(uuid: $uuid, userId: 'petra');

		foreach ([11, 12] as $fileId) {
			$this->assertFalse($this->files[$fileId]['deleted']);
			$this->assertStringNotContainsString(self::PERSON, $this->files[$fileId]['content']);
			$this->assertStringNotContainsString(self::EMAIL, $this->files[$fileId]['content']);
			$this->assertSame([], $this->files[$fileId]['versions'], 'An earlier version still names the person.');
		}

		$this->assertSame('Jan Jansen en Marieke de Vries spraken af. Jan Jansen tekende.', str_replace(SubjectErasureEraser::MARK, self::PERSON, $this->files[12]['content']));
		$this->assertStringContainsString('Marieke de Vries', $this->files[12]['content']);
		$this->assertSame('completed', $outcome['request']['status']);
		$this->assertSame(4, $outcome['request']['progress']['occurrencesErased']);
		$this->assertSame([['document' => '11', 'occurrences' => 2], ['document' => '12', 'occurrences' => 2]], $outcome['certificate']['erased']);
		$this->assertSame(0, $outcome['certificate']['documentRecordsDeleted']);
		$this->assertTrue($outcome['certificate']['complete']);
		$this->assertValidAgainstSchema(schema: 'erasureCertificate', payload: $outcome['certificate']);
		$this->assertValidAgainstSchema(schema: 'subjectErasureRequest', payload: $this->rows['subjectErasureRequest'][$uuid]);
		$this->assertSame(
			[
				SubjectErasureAudit::ACTION_REQUESTED,
				SubjectErasureAudit::ACTION_PREVIEWED,
				SubjectErasureAudit::ACTION_RUN_STARTED,
				SubjectErasureAudit::ACTION_DOCUMENT_ERASED,
				SubjectErasureAudit::ACTION_DOCUMENT_ERASED,
				SubjectErasureAudit::ACTION_CERTIFIED,
			],
			array_column($this->auditEntries, 0)
		);
		$this->assertStringNotContainsString(self::PERSON, (string) json_encode(array_column($this->auditEntries, 2)));

	}//end testTheRecordsStayWhileThePersonGoes()

	/**
	 * A document under a legal hold is untouched and listed with the hold and who decides.
	 *
	 * @return void
	 */
	public function testALegalHoldWins(): void {
		$this->rows['legalHoldCase']['hold-1'] = ['name' => 'Rechtszaak 2025-117', 'status' => 'active', 'fanOut' => [['ref' => 'doc-12', 'record' => 'held', 'file' => 'locked', 'fileIds' => [12]]]];
		$services = $this->erasure();
		$uuid = $this->placed(services: $services);
		$services['service']->preview(uuid: $uuid, userId: 'petra');
		$held = $this->files[12];

		$outcome = $services['run']->run(uuid: $uuid, userId: 'petra');

		$this->assertSame($held, $this->files[12]);
		$this->assertStringNotContainsString(self::PERSON, $this->files[11]['content']);
		$this->assertSame('legal_hold', $outcome['certificate']['refused'][0]['obligation']);
		$this->assertStringContainsString('Rechtszaak 2025-117', $outcome['certificate']['refused'][0]['reason']);
		$this->assertSame('the legal department that placed the hold', $outcome['certificate']['refused'][0]['decidedBy']);
		$this->assertFalse($outcome['certificate']['complete']);
		$this->assertContains(SubjectErasureAudit::ACTION_DOCUMENT_REFUSED, array_column($this->auditEntries, 0));

	}//end testALegalHoldWins()

	/**
	 * A hold placed after the preview still wins: refusals are read at run time.
	 *
	 * @return void
	 */
	public function testAHoldPlacedAfterThePreviewStillWins(): void {
		$services = $this->erasure();
		$uuid = $this->placed(services: $services);
		$services['service']->preview(uuid: $uuid, userId: 'petra');
		$this->rows['legalHoldCase']['hold-2'] = ['name' => 'Bezwaar 88', 'status' => 'active', 'fanOut' => [['ref' => 'doc-11', 'record' => 'held', 'file' => 'locked', 'fileIds' => [11]]]];
		$held = $this->files[11];

		$services['run']->run(uuid: $uuid, userId: 'petra');

		$this->assertSame($held, $this->files[11]);

	}//end testAHoldPlacedAfterThePreviewStillWins()

	/**
	 * A final besluit is superseded by a new version with the erased content,
	 * and the superseded version's bytes are erased while its record stays.
	 *
	 * @return void
	 */
	public function testAFinalBesluitIsSupersededNotEdited(): void {
		$this->rows['documentVersion']['final-11'] = ['fileId' => 11, 'versionLabel' => '', 'status' => 'final', 'documentName' => 'aanvraag.txt', 'fileChecksum' => hash('sha256', $this->files[11]['content'])];
		$services = $this->erasure();
		$uuid = $this->placed(services: $services);
		$services['service']->preview(uuid: $uuid, userId: 'petra');

		$outcome = $services['run']->run(uuid: $uuid, userId: 'petra');

		$result = array_column($outcome['request']['results'], null, 'document')['11'];
		$successorId = $result['newVersionFileId'];
		$this->assertGreaterThan(12, $successorId);
		$this->assertSame('aanvraag erased.txt', $this->files[$successorId]['name']);
		$this->assertStringNotContainsString(self::PERSON, $this->files[$successorId]['content']);
		$superseded = $this->rows['documentVersion']['final-11'];
		$this->assertSame('final', $superseded['status']);
		$this->assertSame($uuid, $superseded['erasureRequest']);
		$this->assertSame(hash('sha256', $this->files[11]['content']), $superseded['fileChecksum']);
		$this->assertStringNotContainsString(self::PERSON, $this->files[11]['content']);
		$successor = array_values(array_filter($this->rows['documentVersion'], static fn (array $row): bool => $row['fileId'] === $successorId))[0];
		$this->assertSame('final-11', $successor['supersedes']);
		$this->assertSame('draft', $successor['status']);
		$this->assertValidAgainstSchema(schema: 'documentVersion', payload: $superseded);

	}//end testAFinalBesluitIsSupersededNotEdited()

	/**
	 * An identifier excluded for one document stays in it; the rest goes.
	 * An exclusion without a reason is refused and nothing is stored.
	 *
	 * @return void
	 */
	public function testAnExcludedOccurrenceIsLeftAndNeedsAReason(): void {
		$services = $this->erasure();
		$uuid = $this->placed(services: $services);
		$services['service']->preview(uuid: $uuid, userId: 'petra');

		$refusal = $this->refusal(fn () => $services['service']->exclude(uuid: $uuid, exclusions: [['occurrence' => '11:' . self::EMAIL, 'reason' => '']], userId: 'petra'));
		$this->assertSame(SubjectErasureRefusedException::REASON_INVALID, $refusal->getReason());
		$this->assertSame([], $this->rows['subjectErasureRequest'][$uuid]['excluded']);

		$services['service']->exclude(uuid: $uuid, exclusions: [['occurrence' => '11:' . self::EMAIL, 'reason' => 'Gedeeld functioneel adres, niet van de betrokkene.']], userId: 'petra');
		$outcome = $services['run']->run(uuid: $uuid, userId: 'petra');

		$this->assertStringContainsString(self::EMAIL, $this->files[11]['content']);
		$this->assertStringNotContainsString(self::PERSON, $this->files[11]['content']);
		$this->assertSame([['occurrence' => '11:' . self::EMAIL, 'reason' => 'Gedeeld functioneel adres, niet van de betrokkene.']], $outcome['certificate']['excluded']);

	}//end testAnExcludedOccurrenceIsLeftAndNeedsAReason()

	/**
	 * The reversible key loses the person's entries and keeps everybody else's;
	 * the destruction is audited.
	 *
	 * @return void
	 */
	public function testTheWayBackIsClosed(): void {
		$crypto = $this->crypto();
		$pairs = [
			['placeholder' => '[PERSOON: 1]', 'originalValue' => self::PERSON, 'entityType' => 'PERSON'],
			['placeholder' => '[PERSOON: 2]', 'originalValue' => 'Marieke de Vries', 'entityType' => 'PERSON'],
		];
		$this->rows['pseudonymMap']['map-12'] = ['anonymizationLink' => 'link-12', 'sourceFileId' => 12, 'mappings' => $crypto->encrypt((string) json_encode($pairs)), 'algorithm' => 'nextcloud-icrypto-v1', 'entryCount' => 2, 'scope' => 'document', 'storedAt' => '2026-09-01T10:00:00+00:00', 'storedBy' => 'petra'];
		$services = $this->erasure();
		$uuid = $this->placed(services: $services);
		$services['service']->preview(uuid: $uuid, userId: 'petra');

		$outcome = $services['run']->run(uuid: $uuid, userId: 'petra');

		$kept = json_decode($crypto->decrypt($this->rows['pseudonymMap']['map-12']['mappings']), true);
		$this->assertSame(['Marieke de Vries'], array_column($kept, 'originalValue'));
		$this->assertSame(1, $this->rows['pseudonymMap']['map-12']['entryCount']);
		$this->assertSame(1, $outcome['certificate']['mappingEntriesDestroyed']);
		$this->assertContains(SubjectErasureAudit::ACTION_MAPPING_DESTROYED, array_column($this->auditEntries, 0));

	}//end testTheWayBackIsClosed()

	/**
	 * A rewrite the person survives leaves the document untouched, and the
	 * certificate says it was not done.
	 *
	 * @return void
	 */
	public function testARewriteThatMissesChangesNothing(): void {
		$this->rewriteMisses = true;
		$services = $this->erasure();
		$uuid = $this->placed(services: $services);
		$services['service']->preview(uuid: $uuid, userId: 'petra');
		$before = $this->contents();

		$outcome = $services['run']->run(uuid: $uuid, userId: 'petra');

		$this->assertSame($before, array_intersect_key($this->contents(), $before));
		$this->assertSame([], $outcome['certificate']['erased']);
		$this->assertSame(['not_processable', 'not_processable'], array_column($outcome['certificate']['refused'], 'obligation'));
		$this->assertFalse($outcome['certificate']['complete']);
		$this->assertSame([], array_filter($this->files, static fn (array $file): bool => $file['deleted'] === false && str_starts_with($file['name'], '.filinq-erasure-')), 'A working copy was left behind.');

	}//end testARewriteThatMissesChangesNothing()

	/**
	 * A run the audit trail stops reads partially_completed, keeps what it
	 * erased, and resumes where it stopped.
	 *
	 * @return void
	 */
	public function testAStoppedRunSaysWhereAndResumes(): void {
		$services = $this->erasure();
		$uuid = $this->placed(services: $services);
		$services['service']->preview(uuid: $uuid, userId: 'petra');
		$this->auditFails = static fn (string $action, array $context): bool => $action === SubjectErasureAudit::ACTION_DOCUMENT_ERASED && ($context['fileId'] ?? 0) === 12;

		$refusal = $this->refusal(fn () => $services['run']->run(uuid: $uuid, userId: 'petra'));

		$this->assertSame(SubjectErasureRefusedException::REASON_AUDIT_UNAVAILABLE, $refusal->getReason());
		$request = $this->rows['subjectErasureRequest'][$uuid];
		$this->assertSame('partially_completed', $request['status']);
		$this->assertSame('11', $request['progress']['lastDocument']);
		$this->assertSame(1, $request['progress']['documentsDone']);
		$erasedFirst = $this->files[11]['content'];

		$this->auditFails = null;
		$outcome = $services['run']->run(uuid: $uuid, userId: 'petra');

		$this->assertSame('completed', $outcome['request']['status']);
		$this->assertSame($erasedFirst, $this->files[11]['content']);
		$this->assertStringNotContainsString(self::PERSON, $this->files[12]['content']);

	}//end testAStoppedRunSaysWhereAndResumes()

	/**
	 * No start entry, no run.
	 *
	 * @return void
	 */
	public function testNoAuditNoRun(): void {
		$services = $this->erasure();
		$uuid = $this->placed(services: $services);
		$services['service']->preview(uuid: $uuid, userId: 'petra');
		$this->auditFails = static fn (string $action): bool => $action === SubjectErasureAudit::ACTION_RUN_STARTED;
		$before = $this->contents();

		$refusal = $this->refusal(fn () => $services['run']->run(uuid: $uuid, userId: 'petra'));

		$this->assertSame(SubjectErasureRefusedException::REASON_AUDIT_UNAVAILABLE, $refusal->getReason());
		$this->assertSame($before, $this->contents());
		$this->assertSame('previewed', $this->rows['subjectErasureRequest'][$uuid]['status']);

	}//end testNoAuditNoRun()

	/**
	 * A catalogue that cannot be read refuses the preview: nobody can say where the person is.
	 *
	 * @return void
	 */
	public function testAnUnreadableCatalogueRefusesThePreview(): void {
		$services = $this->erasure();
		$uuid = $this->placed(services: $services);
		$this->catalogueDown = true;

		$refusal = $this->refusal(fn () => $services['service']->preview(uuid: $uuid, userId: 'petra'));

		$this->assertSame(SubjectErasureRefusedException::REASON_CATALOGUE_UNAVAILABLE, $refusal->getReason());
		$this->assertSame('received', $this->rows['subjectErasureRequest'][$uuid]['status']);

	}//end testAnUnreadableCatalogueRefusesThePreview()

	/**
	 * A completed request does not run again.
	 *
	 * @return void
	 */
	public function testACompletedRequestDoesNotRunAgain(): void {
		$services = $this->erasure();
		$uuid = $this->placed(services: $services);
		$services['service']->preview(uuid: $uuid, userId: 'petra');
		$services['run']->run(uuid: $uuid, userId: 'petra');

		$refusal = $this->refusal(fn () => $services['run']->run(uuid: $uuid, userId: 'petra'));

		$this->assertSame(SubjectErasureRefusedException::REASON_WRONG_STATE, $refusal->getReason());
		$this->assertSame($this->rows['subjectErasureRequest'][$uuid]['certificate'], $services['run']->certificate(uuid: $uuid, userId: 'petra')['uuid']);

	}//end testACompletedRequestDoesNotRunAgain()
}//end class
