<?php

/**
 * Unit tests for DocumentReviewService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentReviewService;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Asserts that a released document comes back on its date and STAYS on the list
 * until somebody reviews it. An unread notification changes nothing.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DocumentReviewServiceTest extends TestCase {

	/**
	 * Documents the fake OpenRegister was asked to store.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $written = [];

	/**
	 * Build the service over a register holding the given documents.
	 *
	 * @param array<int, array<string, mixed>> $documents The stored documents.
	 *
	 * @return DocumentReviewService The service under test.
	 */
	private function service(array $documents): DocumentReviewService {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjects')->willReturn($documents);
		$objectService->method('find')->willReturnCallback(
			static function (...$arguments) use ($documents): ?array {
				$id = (string)($arguments[0] ?? '');
				foreach ($documents as $document) {
					if ((string)($document['uuid'] ?? '') === $id) {
						return $document;
					}
				}

				return null;
			}
		);
		$objectService->method('saveObject')->willReturnCallback(
			function (...$arguments): array {
				$this->written[] = ($arguments[0] ?? []);

				return ($arguments[0] ?? []);
			}
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		return new DocumentReviewService($resolver, $this->createMock(LoggerInterface::class));

	}//end service()

	/**
	 * Twelve months after release is when it comes back.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testAReviewIntervalComputesAReviewDate(): void {
		$service = $this->service(documents: []);

		$date = $service->reviewDate(interval: 'P12M', from: '2026-03-01T10:00:00+00:00');

		$this->assertStringStartsWith('2027-03-01', $date);

	}//end testAReviewIntervalComputesAReviewDate()

	/**
	 * Something that is not a duration is refused, rather than read as zero.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testAnIntervalThatIsNotADurationIsRefused(): void {
		$service = $this->service(documents: []);

		$this->expectException(RuntimeException::class);
		$service->reviewDate(interval: 'twaalf maanden');

	}//end testAnIntervalThatIsNotADurationIsRefused()

	/**
	 * A beleidsregel comes back, and stays on the list until somebody reviews it.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testADueDocumentStaysDueUntilSomebodyReviewsIt(): void {
		$service = $this->service(documents: []);
		$document = [
			'uuid' => 'doc-1',
			'reviewInterval' => 'P12M',
			'reviewDate' => '2026-03-01T10:00:00+00:00',
		];

		$this->assertTrue($service->isDue(document: $document, on: '2026-03-02T09:00:00+00:00'));
		$this->assertFalse($service->isDue(document: $document, on: '2026-02-01T09:00:00+00:00'));

		// An unread notification changes nothing: only a review does.
		$reviewed = ($document + []);
		$reviewed['reviewedAt'] = '2026-03-03T09:00:00+00:00';
		$this->assertFalse($service->isDue(document: $reviewed, on: '2026-03-04T09:00:00+00:00'));

	}//end testADueDocumentStaysDueUntilSomebodyReviewsIt()

	/**
	 * A review done BEFORE the date does not clear a date that came round again.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testAReviewFromBeforeTheDateDoesNotClearIt(): void {
		$service = $this->service(documents: []);

		$document = [
			'reviewDate' => '2026-03-01T10:00:00+00:00',
			'reviewedAt' => '2025-03-01T10:00:00+00:00',
		];

		$this->assertTrue($service->isDue(document: $document, on: '2026-03-02T09:00:00+00:00'));

	}//end testAReviewFromBeforeTheDateDoesNotClearIt()

	/**
	 * The due list holds what is due and nothing else.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testTheDueListHoldsOnlyWhatIsDue(): void {
		$service = $this->service(
			documents: [
				['uuid' => 'doc-1', 'reviewDate' => '2026-01-01T00:00:00+00:00'],
				['uuid' => 'doc-2', 'reviewDate' => '2099-01-01T00:00:00+00:00'],
				['uuid' => 'doc-3'],
			]
		);

		$due = $service->listDue(on: '2026-06-01T00:00:00+00:00');

		$this->assertCount(1, $due);
		$this->assertSame('doc-1', $due[0]['uuid']);

	}//end testTheDueListHoldsOnlyWhatIsDue()

	/**
	 * Reviewing records the moment and sets the next date from the interval.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testReviewingSetsTheNextDate(): void {
		$service = $this->service(
			documents: [
				[
					'uuid' => 'doc-1',
					'reviewInterval' => 'P12M',
					'reviewDate' => '2026-01-01T00:00:00+00:00',
				],
			]
		);

		$document = $service->markReviewed(uuid: 'doc-1');

		$this->assertNotSame('', (string)$document['reviewedAt']);
		$this->assertNotSame('2026-01-01T00:00:00+00:00', $document['reviewDate']);
		$this->assertCount(1, $this->written);
		$this->assertArrayNotHasKey(
			'uuid',
			$this->written[0],
			'The uuid is an argument, not a field: a hard-validating schema refuses a field it never declared.'
		);

	}//end testReviewingSetsTheNextDate()

	/**
	 * Scheduling a document computes its date from when it was generated.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testSchedulingComputesFromTheMomentItWasGenerated(): void {
		$service = $this->service(documents: []);

		$document = $service->schedule(
			document: ['generatedAt' => '2026-03-01T10:00:00+00:00'],
			interval: 'P6M'
		);

		$this->assertSame('P6M', $document['reviewInterval']);
		$this->assertStringStartsWith('2026-09-01', $document['reviewDate']);

	}//end testSchedulingComputesFromTheMomentItWasGenerated()
}//end class
