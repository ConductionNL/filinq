<?php

/**
 * One document of a subject erasure run: refused, excluded, erased or failed.
 *
 * Golden outcomes and audit entries recorded on the implementation before
 * process() and its helpers were split: what is refused stays refused, what is
 * erased is erased, and every outcome but an exclusion is audited.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\SubjectErasure
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SubjectErasure;

use OCA\Filinq\Service\FinalDocumentRepository;
use OCA\Filinq\Service\Pseudonymisation\PseudonymMapService;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureAudit;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureDocumentStep;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureEraser;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureObligations;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureRules;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for SubjectErasureDocumentStep.
 */
class SubjectErasureDocumentStepTest extends TestCase {

	/**
	 * The audit entries written, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $audits = [];

	/**
	 * The eraser calls made, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $erasures = [];

	/**
	 * The mapping calls made, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $forgotten = [];

	/**
	 * A step whose collaborators answer as told.
	 *
	 * @param array<string, mixed> $setup refusals, file (bool), record, eraseThrows, final, forget (int|RuntimeException).
	 *
	 * @return SubjectErasureDocumentStep
	 */
	private function step(array $setup = []): SubjectErasureDocumentStep {
		$rules = $this->createMock(SubjectErasureRules::class);
		$rules->method('refusals')->willReturn($setup['refusals'] ?? []);

		$file = $this->createMock(File::class);
		$obligations = $this->createMock(SubjectErasureObligations::class);
		if (($setup['file'] ?? true) === false) {
			$file = null;
		}

		$obligations->method('file')->willReturn($file);

		$eraser = $this->createMock(SubjectErasureEraser::class);
		$eraser->method('erase')->willReturnCallback(
			function (File $file, array $values) use ($setup): array {
				$this->erasures[] = ['erase', $values];
				if (isset($setup['eraseThrows']) === true) {
					throw new RuntimeException($setup['eraseThrows']);
				}

				return ['ignored' => true];
			}
		);
		$eraser->method('eraseFinal')->willReturnCallback(
			function (File $file, array $values, array $record, string $requestUuid): array {
				$this->erasures[] = ['eraseFinal', $values, $record, $requestUuid];
				return ['newVersionFileId' => '99'];
			}
		);

		$finals = $this->createMock(FinalDocumentRepository::class);
		$finals->method('findCurrent')->willReturnCallback(
			function (int $fileId) use ($setup): ?array {
				$this->erasures[] = ['findCurrent', $fileId];
				return $setup['record'] ?? null;
			}
		);

		$maps = $this->createMock(PseudonymMapService::class);
		$maps->method('forgetValues')->willReturnCallback(
			function (int $sourceFileId, array $values) use ($setup): array {
				$this->forgotten[] = [$sourceFileId, $values];
				$forget = ($setup['forget'] ?? 0);
				if ($forget instanceof RuntimeException) {
					throw $forget;
				}

				return ['maps' => ['m1'], 'entriesDestroyed' => $forget, 'mapsDeleted' => 0];
			}
		);

		$audit = $this->createMock(SubjectErasureAudit::class);
		$audit->method('record')->willReturnCallback(
			function (string $requestUuid, string $action, string $userId, array $details = []): void {
				$this->audits[] = [$requestUuid, $action, $userId, $details];
			}
		);

		return new SubjectErasureDocumentStep($rules, $obligations, $eraser, $finals, $maps, $audit);
	}//end step()

	/**
	 * The assessed document 7.
	 *
	 * @param array<string, mixed> $extra Extra fields.
	 *
	 * @return array<string, mixed>
	 */
	private function document(array $extra = []): array {
		return $extra + ['id' => '7', 'name' => 'Besluit.pdf', 'values' => ['Jan Jansen', 'Kerkstraat 1'], 'occurrences' => '3'];
	}//end document()

	/**
	 * The result shape with the given fields.
	 *
	 * @param array<string, mixed> $fields The fields that differ from an excluded result.
	 *
	 * @return array<string, mixed>
	 */
	private function expected(array $fields): array {
		return array_merge(
			[
				'document' => '7',
				'name' => 'Besluit.pdf',
				'outcome' => 'excluded',
				'occurrences' => 0,
				'reason' => '',
				'obligations' => [],
				'newVersionFileId' => 0,
				'mappingEntriesDestroyed' => 0,
			],
			$fields
		);
	}//end expected()

	/**
	 * A held document is refused, audited, and neither erased nor its key touched.
	 *
	 * @return void
	 */
	public function testAHeldDocumentIsRefusedAndAudited(): void {
		$refusals = [['obligation' => 'legal-hold', 'reason' => 'Under legal hold'], ['obligation' => 'archive', 'reason' => 'Archived']];

		$result = $this->step(['refusals' => $refusals])->process($this->document(), [['occurrence' => '7', 'reason' => 'x']], 'req-1', 'alice');

		$this->assertSame($this->expected(['outcome' => 'refused', 'obligations' => $refusals, 'reason' => 'Under legal hold']), $result);
		$this->assertSame(
			[['req-1', SubjectErasureAudit::ACTION_DOCUMENT_REFUSED, 'alice', ['fileId' => 7, 'obligations' => ['legal-hold', 'archive']]]],
			$this->audits
		);
		$this->assertSame([], $this->erasures);
		$this->assertSame([], $this->forgotten);
	}

	/**
	 * Whole-document and per-value exclusions leave the document as it is, without an audit entry.
	 *
	 * @return void
	 */
	public function testExclusionsLeaveTheDocumentUntouchedAndUnaudited(): void {
		$whole = $this->step()->process($this->document(['name' => null]), [['occurrence' => '6'], ['occurrence' => 7, 'reason' => 'Operator']], 'r', 'u');
		$this->assertSame($this->expected(['name' => '', 'reason' => 'Operator']), $whole);

		$noReason = $this->step()->process($this->document(), [['occurrence' => '7']], 'r', 'u');
		$this->assertSame($this->expected([]), $noReason);

		$valueExclusions = [['occurrence' => '7:Jan Jansen'], ['occurrence' => '7:Kerkstraat 1'], ['occurrence' => '8:x']];
		$every = $this->step()->process($this->document(), $valueExclusions, 'r', 'u');
		$this->assertSame($this->expected(['reason' => 'Every identifier found in this document was excluded.']), $every);

		$none = $this->step()->process(['id' => 7], [], 'r', 'u');
		$this->assertSame($this->expected(['name' => '', 'reason' => 'Every identifier found in this document was excluded.']), $none);

		$this->assertSame([], $this->audits);
		$this->assertSame([], $this->erasures);
		$this->assertSame([], $this->forgotten);
	}

	/**
	 * An erased document loses its reversible key entries, and both are audited.
	 *
	 * @return void
	 */
	public function testAnErasedDocumentLosesItsWayBack(): void {
		$result = $this->step(['forget' => 2])->process($this->document(), [['occurrence' => '7:Kerkstraat 1']], 'req', 'bob');

		$this->assertSame($this->expected(['outcome' => 'erased', 'occurrences' => 3, 'mappingEntriesDestroyed' => 2]), $result);
		$this->assertSame([['erase', ['Jan Jansen']]], $this->erasures);
		$this->assertSame([[7, ['Jan Jansen']]], $this->forgotten);
		$this->assertSame(
			[
				['req', SubjectErasureAudit::ACTION_MAPPING_DESTROYED, 'bob', ['fileId' => 7, 'maps' => ['m1'], 'entriesDestroyed' => 2, 'mapsDeleted' => 0]],
				['req', SubjectErasureAudit::ACTION_DOCUMENT_ERASED, 'bob', ['fileId' => 7, 'occurrences' => 3, 'newVersionFileId' => 0, 'reason' => '']],
			],
			$this->audits
		);
	}

	/**
	 * A final current version is erased into a new version; without a record it is erased in place.
	 *
	 * @return void
	 */
	public function testAFinalVersionGetsANewVersion(): void {
		$record = ['uuid' => 'final-1'];
		$result = $this->step(['record' => $record])->process($this->document(['finalVersion' => true]), [], 'req', 'bob');

		$this->assertSame($this->expected(['outcome' => 'erased', 'occurrences' => 3, 'newVersionFileId' => 99]), $result);
		$this->assertSame([['findCurrent', 7], ['eraseFinal', ['Jan Jansen', 'Kerkstraat 1'], $record, 'req']], $this->erasures);
		$this->assertSame(
			[['req', SubjectErasureAudit::ACTION_DOCUMENT_ERASED, 'bob', ['fileId' => 7, 'occurrences' => 3, 'newVersionFileId' => 99, 'reason' => '']]],
			$this->audits
		);

		$this->erasures = [];
		$this->step()->process($this->document(['finalVersion' => true]), [], 'req', 'bob');
		$this->assertSame([['findCurrent', 7], ['erase', ['Jan Jansen', 'Kerkstraat 1']]], $this->erasures);

		$this->erasures = [];
		$this->step(['record' => $record])->process($this->document(['finalVersion' => 'yes']), [], 'req', 'bob');
		$this->assertSame([['erase', ['Jan Jansen', 'Kerkstraat 1']]], $this->erasures);
	}

	/**
	 * An unreadable, missing or failing file is a failure, audited, and its key is still destroyed.
	 *
	 * @return void
	 */
	public function testFailuresAreAuditedAndStillCloseTheWayBack(): void {
		$unreadable = $this->step(['forget' => 1])->process($this->document(['unreadable' => 'Encrypted PDF']), [], 'req', 'bob');
		$this->assertSame($this->expected(['outcome' => 'failed', 'reason' => 'Encrypted PDF', 'mappingEntriesDestroyed' => 1]), $unreadable);
		$this->assertSame([], $this->erasures);

		$gone = $this->step(['file' => false])->process($this->document(), [], 'req', 'bob');
		$this->assertSame($this->expected(['outcome' => 'failed', 'reason' => 'The file no longer exists.']), $gone);

		$broken = $this->step(['eraseThrows' => 'Disk full'])->process($this->document(), [], 'req', 'bob');
		$this->assertSame($this->expected(['outcome' => 'failed', 'reason' => 'Disk full']), $broken);

		$this->assertSame(SubjectErasureAudit::ACTION_MAPPING_DESTROYED, $this->audits[0][1]);
		$this->assertSame(['fileId' => 7, 'occurrences' => 0, 'newVersionFileId' => 0, 'reason' => 'Encrypted PDF'], $this->audits[1][3]);
		$this->assertSame(SubjectErasureAudit::ACTION_DOCUMENT_FAILED, $this->audits[1][1]);
		$this->assertSame(SubjectErasureAudit::ACTION_DOCUMENT_FAILED, $this->audits[2][1]);
		$this->assertSame(SubjectErasureAudit::ACTION_DOCUMENT_FAILED, $this->audits[3][1]);
		$this->assertCount(4, $this->audits);
		$this->assertCount(3, $this->forgotten);
	}

	/**
	 * A reversible key that cannot be destroyed fails the document, whatever happened before.
	 *
	 * @return void
	 */
	public function testAKeyThatCannotBeDestroyedFailsTheDocument(): void {
		$result = $this->step(['forget' => new RuntimeException('map locked')])->process($this->document(), [], 'req', 'bob');
		$this->assertSame(
			$this->expected(['outcome' => 'failed', 'occurrences' => 3, 'reason' => 'The reversible key could not be destroyed: map locked']),
			$result
		);
		$this->assertSame(
			[
				[
					'req',
					SubjectErasureAudit::ACTION_DOCUMENT_FAILED,
					'bob',
					['fileId' => 7, 'occurrences' => 3, 'newVersionFileId' => 0, 'reason' => 'The reversible key could not be destroyed: map locked'],
				],
			],
			$this->audits
		);

		$this->audits = [];
		$both = $this->step(['file' => false, 'forget' => new RuntimeException('locked')])->process($this->document(), [], 'req', 'bob');
		$this->assertSame('The file no longer exists. The reversible key could not be destroyed: locked', $both['reason']);
	}
}
