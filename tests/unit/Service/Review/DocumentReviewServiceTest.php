<?php

/**
 * Unit tests for DocumentReviewService.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Review
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Review;

use OCA\Filinq\Exception\DocumentReviewRefusedException;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\Review\DocumentReviewRepository;
use OCA\Filinq\Service\Review\DocumentReviewService;
use OCP\IAppConfig;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The checked mark: fingerprint, invalidation, check/uncheck and the gate verdict.
 *
 * @covers \OCA\Filinq\Service\Review\DocumentReviewService
 * @uses   \OCA\Filinq\Exception\DocumentReviewRefusedException
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DocumentReviewServiceTest extends TestCase {

	/**
	 * The relations the mapper returns, per file id.
	 *
	 * @var array<int, array<int, array<string, mixed>>>
	 */
	private array $relations = [];

	/**
	 * The stored marks, per file id.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $marks = [];

	/**
	 * Marks the repository was asked to save, in order.
	 *
	 * @var list<array<string, mixed>>
	 */
	private array $saved = [];

	/**
	 * Uuids the repository was asked to delete, in order.
	 *
	 * @var list<string>
	 */
	private array $deleted = [];

	/**
	 * The configured gate mode.
	 *
	 * @var string
	 */
	private string $mode = 'enforced';

	/**
	 * Whether reading relations fails.
	 *
	 * @var bool
	 */
	private bool $mapperFails = false;

	/**
	 * Build the service over in-memory doubles.
	 *
	 * @return DocumentReviewService The service.
	 */
	private function service(): DocumentReviewService {
		$test = $this;
		$mapper = new class ($test) {
			/**
			 * Constructor.
			 *
			 * @param DocumentReviewServiceTest $test The test holding the relations.
			 */
			public function __construct(private DocumentReviewServiceTest $test) {
			}

			/**
			 * The relations of a file.
			 *
			 * @param int $fileId The file.
			 *
			 * @return array<int, mixed> The relations.
			 */
			public function findByFileId(int $fileId): array {
				return $this->test->relationsFor(fileId: $fileId);
			}
		};

		$locator = $this->createMock(OpenRegisterServiceLocator::class);
		$locator->method('get')->willReturnCallback(
			function (string $className) use ($mapper): object {
				$this->assertSame('OCA\OpenRegister\Db\EntityRelationMapper', $className);
				if ($this->mapperFails === true) {
					throw new RuntimeException('OpenRegister is not available.');
				}

				return $mapper;
			}
		);

		/** @var DocumentReviewRepository&MockObject $repository */
		$repository = $this->createMock(DocumentReviewRepository::class);
		$repository->method('findFor')->willReturnCallback(fn (int $fileId): ?array => ($this->marks[$fileId] ?? null));
		$repository->method('save')->willReturnCallback(
			function (array $mark): array {
				$this->saved[] = $mark;
				$this->marks[(int)$mark['fileId']] = $mark + ['uuid' => 'm-'.count($this->saved)];
				return $this->marks[(int)$mark['fileId']];
			}
		);
		$repository->method('delete')->willReturnCallback(
			function (string $uuid): void {
				$this->deleted[] = $uuid;
				foreach ($this->marks as $fileId => $mark) {
					if ($mark['uuid'] === $uuid) {
						unset($this->marks[$fileId]);
					}
				}
			}
		);

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = ''): string {
				$this->assertSame('filinq', $app);
				$this->assertSame('filinq.review.checked_gate', $key);
				return $this->mode;
			}
		);

		return new DocumentReviewService(
			repository: $repository,
			locator: $locator,
			appConfig: $config,
			logger: $this->createMock(LoggerInterface::class),
			l10n: $this->l10n()
		);
	}//end service()

	/**
	 * A translator that returns the source string.
	 *
	 * @return IL10N The translator.
	 */
	private function l10n(): IL10N {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		return $l10n;
	}//end l10n()

	/**
	 * The relations of a file, for the mapper double.
	 *
	 * @param int $fileId The file.
	 *
	 * @return array<int, mixed> The relations.
	 */
	public function relationsFor(int $fileId): array {
		return ($this->relations[$fileId] ?? []);
	}//end relationsFor()

	/**
	 * Two detected relations and one manual one for file 10.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->relations[10] = [
			['id' => 2, 'entityId' => 20, 'skipAnonymization' => false, 'bases' => ['b', 'a'], 'detectionMethod' => 'presidio'],
			['id' => 1, 'entityId' => 21, 'skipAnonymization' => true, 'bases' => null, 'detectionMethod' => 'regex'],
			['id' => 3, 'entityId' => 22, 'skipAnonymization' => false, 'bases' => [], 'detectionMethod' => 'manual'],
		];
	}//end setUp()

	/**
	 * Checking stores the session's reviewer, the counts and a fingerprint, and the mark holds.
	 *
	 * @return void
	 */
	public function testCheckRecordsTheMarkAndItHolds(): void {
		$status = $this->service()->check(fileId: 10, userId: 'w.devries', note: 'p. 4');

		$this->assertCount(1, $this->saved);
		$mark = $this->saved[0];
		$this->assertSame(10, $mark['fileId']);
		$this->assertSame('w.devries', $mark['checkedBy']);
		$this->assertSame(3, $mark['entityCountAtCheck']);
		$this->assertSame(1, $mark['manualEntityCount']);
		$this->assertSame('p. 4', $mark['note']);
		$this->assertSame(64, strlen($mark['decisionFingerprint']));
		$this->assertNotFalse(\DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $mark['checkedOn']));

		$this->assertTrue($status['checked']);
		$this->assertFalse($status['stale']);
		$this->assertTrue($status['extracted']);
		$this->assertSame(3, $status['entityCount']);
		$this->assertSame(1, $status['manualEntityCount']);
		$this->assertSame([], $this->deleted);
	}//end testCheckRecordsTheMarkAndItHolds()

	/**
	 * An empty note is not stored.
	 *
	 * @return void
	 */
	public function testAnEmptyNoteIsLeftOut(): void {
		$this->service()->check(fileId: 10, userId: 'u');

		$this->assertArrayNotHasKey('note', $this->saved[0]);
	}//end testAnEmptyNoteIsLeftOut()

	/**
	 * The fingerprint ignores relation order and bases order.
	 *
	 * @return void
	 */
	public function testTheFingerprintIgnoresOrder(): void {
		$service = $this->service();
		$service->check(fileId: 10, userId: 'u');
		$this->relations[10] = array_reverse($this->relations[10]);
		$this->relations[10][2]['bases'] = ['a', 'b'];

		$this->assertTrue($service->status(fileId: 10)['checked']);
	}//end testTheFingerprintIgnoresOrder()

	/**
	 * A toggle, a bases change or a manual entity after the check voids the mark.
	 *
	 * @return void
	 */
	public function testAnyDecisionChangeAfterTheCheckVoidsIt(): void {
		$service = $this->service();
		$original = $this->relations[10];

		$changes = [
			'toggle' => static function (array $r): array {
				$r[0]['skipAnonymization'] = true;
				return $r;
			},
			'bases' => static function (array $r): array {
				$r[0]['bases'] = ['a'];
				return $r;
			},
			'manual' => static function (array $r): array {
				$r[] = ['id' => 9, 'entityId' => 23, 'skipAnonymization' => false, 'detectionMethod' => 'manual'];
				return $r;
			},
		];
		foreach ($changes as $name => $change) {
			$this->relations[10] = $original;
			$service->check(fileId: 10, userId: 'u');
			$this->relations[10] = $change($original);

			$status = $service->status(fileId: 10);
			$this->assertFalse($status['checked'], $name);
			$this->assertTrue($status['stale'], $name);
		}
	}//end testAnyDecisionChangeAfterTheCheckVoidsIt()

	/**
	 * A file without findings cannot be checked, and nothing is written.
	 *
	 * @return void
	 */
	public function testAFileWithoutFindingsIsRefused(): void {
		try {
			$this->service()->check(fileId: 99, userId: 'u');
			$this->fail('expected a refusal');
		} catch (DocumentReviewRefusedException $e) {
			$this->assertSame('not_extracted', $e->getReason());
		}

		$this->assertSame([], $this->saved);
	}//end testAFileWithoutFindingsIsRefused()

	/**
	 * Unreadable relations refuse the check and read as unchecked.
	 *
	 * @return void
	 */
	public function testUnreadableRelationsFailClosed(): void {
		$this->marks[10] = ['uuid' => 'm-0', 'fileId' => 10, 'decisionFingerprint' => 'x'];
		$this->mapperFails = true;
		$service = $this->service();

		$status = $service->status(fileId: 10);
		$this->assertFalse($status['checked']);
		$this->assertFalse($status['extracted']);
		$this->assertSame(0, $status['entityCount']);

		$this->expectException(DocumentReviewRefusedException::class);
		$service->check(fileId: 10, userId: 'u');
	}//end testUnreadableRelationsFailClosed()

	/**
	 * A re-check removes the earlier mark before creating the new one.
	 *
	 * @return void
	 */
	public function testARecheckReplacesTheEarlierMark(): void {
		$service = $this->service();
		$service->check(fileId: 10, userId: 'first');
		$service->check(fileId: 10, userId: 'second');

		$this->assertSame(['m-1'], $this->deleted);
		$this->assertCount(2, $this->saved);
		$this->assertSame('second', $this->marks[10]['checkedBy']);
	}//end testARecheckReplacesTheEarlierMark()

	/**
	 * Unchecking removes an existing mark and reports whether there was one.
	 *
	 * @return void
	 */
	public function testUncheck(): void {
		$service = $this->service();
		$this->assertFalse($service->uncheck(fileId: 10));
		$service->check(fileId: 10, userId: 'u');

		$this->assertTrue($service->uncheck(fileId: 10));
		$this->assertSame(['m-1'], $this->deleted);
		$status = $service->status(fileId: 10);
		$this->assertFalse($status['checked']);
		$this->assertFalse($status['stale']);
		$this->assertNull($status['review']);
	}//end testUncheck()

	/**
	 * The gate lists every file whose mark does not hold, with its name.
	 *
	 * @return void
	 */
	public function testTheGateListsUncheckedFiles(): void {
		$this->relations[11] = [['id' => 5, 'entityId' => 6]];
		$service = $this->service();
		$service->check(fileId: 10, userId: 'u');

		$this->assertSame(
			[
				'passed' => false,
				'mode' => 'enforced',
				'uncheckedFiles' => [['fileId' => 11, 'fileName' => 'b.pdf'], ['fileId' => 12, 'fileName' => '']],
			],
			$service->gate(files: [['fileId' => '10', 'fileName' => 'a.pdf'], ['fileId' => 11, 'fileName' => 'b.pdf'], ['fileId' => 12]])
		);
		$this->assertSame(['passed' => true, 'mode' => 'enforced', 'uncheckedFiles' => []], $service->gate(files: [['fileId' => 10]]));
	}//end testTheGateListsUncheckedFiles()

	/**
	 * Only `advisory` turns enforcement off; anything else reads as enforced.
	 *
	 * @return void
	 */
	public function testGateMode(): void {
		$this->mode = 'advisory';
		$this->assertSame('advisory', $this->service()->gateMode());
		$this->mode = 'off';
		$this->assertSame('enforced', $this->service()->gateMode());
		$this->mode = 'enforced';
		$this->assertSame('enforced', $this->service()->gateMode());
	}//end testGateMode()

	/**
	 * Relations given as serialisable objects and in snake_case are read the same way.
	 *
	 * @return void
	 */
	public function testRelationShapes(): void {
		$this->relations[10] = [
			new class implements \JsonSerializable {
				/**
				 * The relation's fields.
				 *
				 * @return array<string, mixed> The fields.
				 */
				public function jsonSerialize(): array {
					return ['id' => 1, 'entityId' => 2, 'skipAnonymization' => false, 'detectionMethod' => 'manual'];
				}
			},
			['relation_id' => 4, 'entity_id' => 5, 'skip_anonymization' => true],
			'not a relation',
		];

		$status = $this->service()->check(fileId: 10, userId: 'u');

		$this->assertSame(2, $status['entityCount']);
		$this->assertSame(1, $status['manualEntityCount']);
	}//end testRelationShapes()

	/**
	 * Enforced and unchecked: a 409 refusal with the reason and the files, no report.
	 *
	 * @return void
	 */
	public function testCommitGateRefusesWhenEnforced(): void {
		$service = $this->service();

		$single = $service->commitGate(files: [['fileId' => 10]], reason: DocumentReviewService::REASON_DOCUMENT);
		$batch = $service->commitGate(files: [['fileId' => 10, 'fileName' => 'a.pdf']], reason: DocumentReviewService::REASON_BATCH);

		$this->assertNull($single['report']);
		$this->assertSame(409, $single['refusal']['status']);
		$this->assertSame('document_not_reviewed', $single['refusal']['body']['reason']);
		$this->assertSame([['fileId' => 10, 'fileName' => '']], $single['refusal']['body']['uncheckedFiles']);
		$this->assertStringContainsString('Review it before anonymising.', $single['refusal']['body']['error']);
		$this->assertSame('documents_not_reviewed', $batch['refusal']['body']['reason']);
		$this->assertStringContainsString('Nothing was anonymised.', $batch['refusal']['body']['error']);
	}//end testCommitGateRefusesWhenEnforced()

	/**
	 * Enforced and checked: neither a refusal nor a report.
	 *
	 * @return void
	 */
	public function testCommitGatePassesSilentlyWhenChecked(): void {
		$service = $this->service();
		$service->check(fileId: 10, userId: 'u');

		$this->assertSame(['refusal' => null, 'report' => null], $service->commitGate(files: [['fileId' => 10]], reason: DocumentReviewService::REASON_DOCUMENT));
	}//end testCommitGatePassesSilentlyWhenChecked()

	/**
	 * Advisory: never a refusal, always the report.
	 *
	 * @return void
	 */
	public function testCommitGateReportsWhenAdvisory(): void {
		$this->mode = 'advisory';

		$this->assertSame(
			['refusal' => null, 'report' => ['passed' => false, 'uncheckedFiles' => [['fileId' => 10, 'fileName' => '']]]],
			$this->service()->commitGate(files: [['fileId' => 10]], reason: DocumentReviewService::REASON_DOCUMENT)
		);
	}//end testCommitGateReportsWhenAdvisory()

	/**
	 * A batch's non-error files are judged; an unknown batch has none.
	 *
	 * @return void
	 */
	public function testBatchFiles(): void {
		$service = $this->service();

		$this->assertSame(
			[['fileId' => 1, 'fileName' => 'a.pdf'], ['fileId' => 2, 'fileName' => '']],
			$service->batchFiles(
				batch: [
					'files' => [
						['fileId' => '1', 'fileName' => 'a.pdf', 'status' => 'extracted'],
						['fileId' => 2, 'status' => 'uploaded'],
						['fileId' => 3, 'fileName' => 'c.pdf', 'status' => 'error'],
						['fileName' => 'no-id.pdf', 'status' => 'extracted'],
					],
				]
			)
		);
		$this->assertSame([], $service->batchFiles(batch: null));
	}//end testBatchFiles()
}//end class
