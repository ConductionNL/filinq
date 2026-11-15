<?php

/**
 * SubjectErasureCertifier: the certificate an erasure ends with, naming what
 * was erased, what was refused and why, and which erased documents still have
 * a published copy that carries the person.
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
 * @link https://github.com/ConductionNL/filinq
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SubjectErasure;

use DateTime;
use OCA\Filinq\Service\Publication\PublicationStore;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureAudit;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureCertifier;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureRules;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureStore;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Issuing the erasure certificate.
 *
 * @covers \OCA\Filinq\Service\SubjectErasure\SubjectErasureCertifier
 * @uses   \OCA\Filinq\Service\SubjectErasure\SubjectErasureRules
 */
class SubjectErasureCertifierTest extends TestCase {

	/**
	 * The erasure request.
	 *
	 * @var array<string, mixed>
	 */
	private const REQUEST = ['uuid' => 'req-1', 'ground' => 'art. 17 AVG', 'excluded' => ['b' => 'doc-x']];

	/**
	 * The store double.
	 *
	 * @var SubjectErasureStore&MockObject
	 */
	private SubjectErasureStore $store;

	/**
	 * The publication store double.
	 *
	 * @var PublicationStore&MockObject
	 */
	private PublicationStore $publications;

	/**
	 * The audit double.
	 *
	 * @var SubjectErasureAudit&MockObject
	 */
	private SubjectErasureAudit $audit;

	/**
	 * Set up the doubles; the store answers what it saved with a uuid.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->store = $this->createMock(SubjectErasureStore::class);
		$this->store->method('save')->willReturnCallback(
			static fn (string $schema, array $row): array => ['uuid' => 'cert-1', 'schema' => $schema] + $row
		);
		$this->publications = $this->createMock(PublicationStore::class);
		$this->audit = $this->createMock(SubjectErasureAudit::class);
	}//end setUp()

	/**
	 * The certifier under test, at a fixed moment.
	 *
	 * @return SubjectErasureCertifier
	 */
	private function certifier(): SubjectErasureCertifier {
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getDateTime')->willReturn(new DateTime('2026-11-13T10:00:00+00:00'));

		return new SubjectErasureCertifier($this->store, new SubjectErasureRules(), $this->publications, $this->audit, $time);
	}//end certifier()

	/**
	 * A document step result.
	 *
	 * @param string $document The document.
	 * @param string $outcome  The outcome.
	 * @param array  $extra    Other fields.
	 *
	 * @return array<string, mixed>
	 */
	private static function stepResult(string $document, string $outcome, array $extra = []): array {
		return ['document' => $document, 'outcome' => $outcome] + $extra;
	}//end stepResult()

	/**
	 * Erased, refused and failed documents each land in the certificate, with refusals itemised per obligation.
	 *
	 * @return void
	 */
	public function testTheCertificateNamesWhatWasErasedAndWhatWasRefused(): void {
		$this->publications->method('listRecords')->willReturn([]);
		$this->audit->expects($this->once())->method('record')->with(
			'req-1',
			SubjectErasureAudit::ACTION_CERTIFIED,
			'privacy-officer',
			['certificate' => 'cert-1', 'erased' => 1, 'refused' => 3, 'complete' => false]
		);

		$certificate = $this->certifier()->issue(
			request: self::REQUEST,
			results: [
				self::stepResult('doc-a', 'erased', ['occurrences' => '3', 'mappingEntriesDestroyed' => 2]),
				self::stepResult('doc-b', 'refused', [
					'mappingEntriesDestroyed' => 1,
					'obligations' => [
						['obligation' => 'legal_hold', 'reason' => 'Case 12 is on hold', 'decidedBy' => 'the legal hold'],
						['obligation' => 'archive', 'reason' => 'Must be kept 20 years', 'decidedBy' => 'the selection list'],
					],
				]),
				self::stepResult('doc-c', 'failed', ['reason' => 'Unreadable PDF']),
			],
			userId: 'privacy-officer'
		);

		self::assertSame('erasureCertificate', $certificate['schema']);
		self::assertSame('req-1', $certificate['request']);
		self::assertSame('2026-11-13T10:00:00+00:00', $certificate['issuedAt']);
		self::assertSame('art. 17 AVG', $certificate['ground']);
		self::assertSame([['document' => 'doc-a', 'occurrences' => 3]], $certificate['erased']);
		self::assertSame(
			[
				['document' => 'doc-b', 'obligation' => 'legal_hold', 'reason' => 'Case 12 is on hold', 'decidedBy' => 'the legal hold'],
				['document' => 'doc-b', 'obligation' => 'archive', 'reason' => 'Must be kept 20 years', 'decidedBy' => 'the selection list'],
				['document' => 'doc-c', 'obligation' => 'not_processable', 'reason' => 'Unreadable PDF', 'decidedBy' => 'the privacy officer, by hand'],
			],
			$certificate['refused']
		);
		self::assertSame(['doc-x'], $certificate['excluded']);
		self::assertSame([], $certificate['needsRepublishing']);
		self::assertSame(3, $certificate['mappingEntriesDestroyed']);
		self::assertSame(0, $certificate['documentRecordsDeleted']);
		self::assertFalse($certificate['complete']);
	}//end testTheCertificateNamesWhatWasErasedAndWhatWasRefused()

	/**
	 * An erased document with a live publication of itself needs republishing; one only withdrawn, or another document's, does not.
	 *
	 * @return void
	 */
	public function testAnErasedDocumentStillPublishedNeedsRepublishing(): void {
		$this->publications->method('listRecords')->willReturnCallback(
			static fn (array $filters): array => match ($filters['documentFileRef']) {
				'doc-a' => [['documentFileRef' => 'doc-a', 'status' => 'withdrawn'], ['documentFileRef' => 'doc-a', 'status' => 'published']],
				'doc-b' => [['documentFileRef' => 'other', 'status' => 'published'], ['documentFileRef' => 'doc-b', 'status' => 'draft']],
				'doc-c' => [['documentFileRef' => 'doc-c', 'status' => 'depublication_requested']],
				default => [],
			}
		);

		$certificate = $this->certifier()->issue(
			request: self::REQUEST,
			results: [
				self::stepResult('doc-a', 'erased', ['occurrences' => 1]),
				self::stepResult('doc-b', 'erased', ['occurrences' => 1]),
				self::stepResult('doc-c', 'erased', ['occurrences' => 1]),
			],
			userId: 'po'
		);

		self::assertSame(['doc-a', 'doc-c'], $certificate['needsRepublishing']);
		self::assertFalse($certificate['complete'], 'A published copy still carries the person.');
	}//end testAnErasedDocumentStillPublishedNeedsRepublishing()

	/**
	 * When the publications cannot be read, every erased document is named for republishing rather than none.
	 *
	 * @return void
	 */
	public function testUnreadablePublicationsNameEveryErasedDocument(): void {
		$this->publications->method('listRecords')->willThrowException(new RuntimeException('register down'));

		$certificate = $this->certifier()->issue(
			request: self::REQUEST,
			results: [self::stepResult('doc-a', 'erased', ['occurrences' => 1]), self::stepResult('doc-b', 'erased', ['occurrences' => 2])],
			userId: 'po'
		);

		self::assertSame(['doc-a', 'doc-b'], $certificate['needsRepublishing']);
		self::assertFalse($certificate['complete']);
	}//end testUnreadablePublicationsNameEveryErasedDocument()

	/**
	 * Nothing erased means nothing to republish and no publication lookup; with no refusals the erasure is complete.
	 *
	 * @return void
	 */
	public function testNothingErasedAndNothingRefusedIsComplete(): void {
		$this->publications->expects($this->never())->method('listRecords');

		$certificate = $this->certifier()->issue(request: ['uuid' => 'req-2'], results: [], userId: str_repeat('u', 300));

		self::assertTrue($certificate['complete']);
		self::assertSame('', $certificate['ground']);
		self::assertSame([], $certificate['excluded']);
		self::assertSame(255, mb_strlen($certificate['actor']));
	}//end testNothingErasedAndNothingRefusedIsComplete()
}//end class
