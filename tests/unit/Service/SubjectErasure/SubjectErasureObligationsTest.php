<?php

/**
 * What stops a document from being erased, read before any write: a file
 * that is gone or cannot be written, a type that cannot be checked, and a
 * hold register that cannot be read.
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
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SubjectErasure;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FinalDocumentRepository;
use OCA\Filinq\Service\LegalHold\LegalHoldCaseRepository;
use OCA\Filinq\Service\LegalHold\LegalHoldFileFreeze;
use OCA\Filinq\Service\LegalHold\LegalHoldRecordFreeze;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureObligations;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureRecordStanding;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

class SubjectErasureObligationsTest extends TestCase {
	use SubjectErasureDoubles;

	private const CASE_RECORD = '7d1e4f2a-9b3c-4d5e-8f6a-1b2c3d4e5f60';

	/**
	 * Record refs whose hold-side read fails.
	 *
	 * @var array<int, string>
	 */
	private array $unreadableHoldRecords = [];

	/**
	 * Files each held record holds.
	 *
	 * @var array<string, array<int, int>>
	 */
	private array $heldRecordFiles = [];

	protected function setUp(): void {
		parent::setUp();
		$this->addFile(fileId: 11, name: 'aanvraag.txt', content: 'Aanvrager: Jan Jansen.');
		$this->addFile(fileId: 12, name: 'paspoort.png', content: 'PNG bytes');
		$this->addFile(fileId: 13, name: 'besluit.txt', content: 'Besluit voor Jan Jansen.');

	}//end setUp()

	/**
	 * The real obligations over the in-memory register and files.
	 *
	 * @return SubjectErasureObligations The obligations.
	 */
	private function obligations(): SubjectErasureObligations {
		$container = $this->erasureContainer();
		$resolver = new DocumentObjectServiceResolver($container, $this->apps());
		$holdRecords = $this->createMock(LegalHoldRecordFreeze::class);
		$holdRecords->method('load')->willReturnCallback(
			function (string $ref): ?object {
				if (in_array($ref, $this->unreadableHoldRecords, true) === true) {
					throw new RuntimeException('record table unavailable');
				}

				return isset($this->heldRecordFiles[$ref]) === true ? $this->entity(uuid: $ref, row: []) : null;
			}
		);
		$holdFiles = $this->createMock(LegalHoldFileFreeze::class);
		$holdFiles->method('fileIdsOf')->willReturnCallback(fn (object $entity): array => $this->heldRecordFiles[$entity->getUuid()] ?? []);

		return new SubjectErasureObligations(
			$this->rootFolder(),
			new LegalHoldCaseRepository($resolver),
			new FinalDocumentRepository($resolver, new NullLogger()),
			$holdRecords,
			$holdFiles,
			new SubjectErasureRecordStanding($this->recordLoader())
		);

	}//end obligations()

	/**
	 * An active hold case over one record, stored as the register takes it.
	 *
	 * @param array<int, array<string, mixed>> $fanOut The fan-out entries.
	 *
	 * @return void
	 */
	private function holdCase(array $fanOut): void {
		$case = [
			'name' => 'Bezwaar Jansen',
			'holdType' => 'litigation',
			'reason' => 'Procedure bij de rechtbank',
			'status' => 'active',
			'placedBy' => 'petra',
			'placedAt' => '2026-09-20T09:00:00+00:00',
			'fanOut' => $fanOut,
		];
		$this->assertValidAgainstSchema(schema: 'legalHoldCase', payload: $case);
		$this->rows['legalHoldCase']['case-1'] = $case;

	}//end holdCase()

	/**
	 * The assessment of the given files, keyed by file id.
	 *
	 * @param array<int, int> $fileIds The located files.
	 *
	 * @return array<string, array<string, mixed>> The documents.
	 */
	private function assessed(array $fileIds): array {
		$located = array_map(static fn (int $id): array => ['fileId' => $id, 'occurrences' => 1, 'values' => ['Jan Jansen']], $fileIds);

		return array_column($this->obligations()->assess(located: $located), null, 'id');

	}//end assessed()

	/**
	 * A file that is gone, a picture and a locked file each say why they
	 * cannot be erased; a plain text file can.
	 *
	 * @return void
	 */
	public function testEachDocumentThatCannotBeErasedSaysWhy(): void {
		$this->unwritable = [13];

		$documents = $this->assessed(fileIds: [11, 12, 13, 99]);

		$this->assertSame('', $documents['11']['unreadable']);
		$this->assertSame('aanvraag.txt', $documents['11']['name']);
		$this->assertStringContainsString('Files of type "png" cannot be rewritten and checked', $documents['12']['unreadable']);
		$this->assertSame('The file cannot be written.', $documents['13']['unreadable']);
		$this->assertSame('The file no longer exists.', $documents['99']['unreadable']);
		$this->assertFalse($documents['11']['legal_hold']);

	}//end testEachDocumentThatCannotBeErasedSaysWhy()

	/**
	 * A hold whose fan-out names a record holds that record's files, and
	 * the document says which case holds it.
	 *
	 * @return void
	 */
	public function testAFileHeldThroughItsRecordNamesTheCase(): void {
		$this->heldRecordFiles[self::CASE_RECORD] = [13];
		$this->holdCase(fanOut: [['ref' => self::CASE_RECORD, 'kind' => 'dossier', 'record' => 'held', 'file' => 'locked']]);

		$documents = $this->assessed(fileIds: [11, 13]);

		$this->assertSame('Bezwaar Jansen', $documents['13']['legal_hold']);
		$this->assertFalse($documents['11']['legal_hold']);

	}//end testAFileHeldThroughItsRecordNamesTheCase()

	/**
	 * A hold register that cannot be read holds everything: no document is
	 * erased on the guess that it is not held.
	 *
	 * @return void
	 */
	public function testAnUnreadableHoldRegisterHoldsEveryDocument(): void {
		$this->unreadableHoldRecords = [self::CASE_RECORD];
		$this->holdCase(fanOut: [['ref' => self::CASE_RECORD, 'kind' => 'dossier', 'record' => 'held', 'file' => 'locked']]);

		$documents = $this->assessed(fileIds: [11, 13]);

		foreach (['11', '13'] as $id) {
			$this->assertSame('the hold register could not be read, so every document counts as held', $documents[$id]['legal_hold']);
		}

	}//end testAnUnreadableHoldRegisterHoldsEveryDocument()

	/**
	 * A released fan-out entry holds nothing, even if its record still
	 * carries files.
	 *
	 * @return void
	 */
	public function testAReleasedEntryHoldsNothing(): void {
		$this->heldRecordFiles[self::CASE_RECORD] = [13];
		$this->holdCase(fanOut: [['ref' => self::CASE_RECORD, 'kind' => 'dossier', 'record' => 'released', 'file' => 'unlocked']]);

		$this->assertFalse($this->assessed(fileIds: [13])['13']['legal_hold']);

	}//end testAReleasedEntryHoldsNothing()
}//end class
