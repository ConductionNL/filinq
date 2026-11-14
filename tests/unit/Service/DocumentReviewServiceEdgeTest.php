<?php

/**
 * Edge-case tests for DocumentReviewService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentReviewService;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Invalid intervals and dates, unreadable registers and failed writes: what the
 * review service refuses, and what it treats as due or not due.
 *
 * @covers \OCA\Filinq\Service\DocumentReviewService
 * @uses   \OCA\Filinq\Service\ReadsStoredObjectRows
 */
class DocumentReviewServiceEdgeTest extends TestCase {

	/**
	 * The fake OpenRegister object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objectService;

	/**
	 * The service's logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * The service under test.
	 *
	 * @var DocumentReviewService
	 */
	private DocumentReviewService $service;

	/**
	 * Set up the service over a mocked object service.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->objectService = $this->createMock(ObjectService::class);
		$this->logger        = $this->createMock(LoggerInterface::class);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objectService);

		$this->service = new DocumentReviewService($resolver, $this->logger);

	}//end setUp()

	/**
	 * An empty interval is refused.
	 *
	 * @return void
	 */
	public function testAnEmptyIntervalIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('A review interval is a duration such as P12M.');

		$this->service->reviewDate('   ');

	}//end testAnEmptyIntervalIsRefused()

	/**
	 * A release moment that is not a date is refused, naming it.
	 *
	 * @return void
	 */
	public function testAReleaseMomentThatIsNotADateIsRefused(): void {
		try {
			$this->service->reviewDate('P1M', 'not a date');
			$this->fail('An unparseable release moment was accepted.');
		} catch (RuntimeException $e) {
			$this->assertSame('The release moment is not a date: "not a date".', $e->getMessage());
			$this->assertInstanceOf(Exception::class, $e->getPrevious());
		}

	}//end testAReleaseMomentThatIsNotADateIsRefused()

	/**
	 * The review date is the release moment plus the interval, in ATOM format.
	 *
	 * @return void
	 */
	public function testReviewDateAddsTheIntervalToTheReleaseMoment(): void {
		$this->assertSame(
			'2027-03-15T10:00:00+00:00',
			$this->service->reviewDate(' P1Y2M ', '2026-01-15T10:00:00+00:00')
		);

	}//end testReviewDateAddsTheIntervalToTheReleaseMoment()

	/**
	 * Unreadable review dates are never due; unreadable review moments do not clear it.
	 *
	 * @return void
	 */
	public function testUnparseableDatesInIsDue(): void {
		$day = '2026-06-01T00:00:00+00:00';

		$this->assertFalse($this->service->isDue(['reviewDate' => 'garbage'], $day));
		$this->assertFalse($this->service->isDue(['reviewDate' => '2026-01-01T00:00:00+00:00'], 'garbage'));
		$this->assertTrue(
			$this->service->isDue(['reviewDate' => '2026-01-01T00:00:00+00:00', 'reviewedAt' => 'garbage'], $day)
		);
		$this->assertFalse(
			$this->service->isDue(['reviewDate' => '2026-01-01T00:00:00+00:00', 'reviewedAt' => '2026-02-01T00:00:00+00:00'], $day)
		);

	}//end testUnparseableDatesInIsDue()

	/**
	 * A register that cannot be read is logged and yields nothing due.
	 *
	 * @return void
	 */
	public function testAnUnreadableRegisterYieldsNothingDue(): void {
		$this->objectService->method('searchObjectsBySlug')->willThrowException(new Exception('down'));
		$this->logger->expects($this->once())
			->method('warning')
			->with(
				'[DocumentReviewService] could not read the generated documents',
				$this->callback(static fn (array $context): bool => $context['error'] === 'down')
			);

		$this->assertSame([], $this->service->listDue('2026-06-01T00:00:00+00:00'));

	}//end testAnUnreadableRegisterYieldsNothingDue()

	/**
	 * A count instead of rows yields nothing due.
	 *
	 * @return void
	 */
	public function testANonArrayResultYieldsNothingDue(): void {
		$this->objectService->expects($this->once())
			->method('searchObjectsBySlug')
			->with('filinq', 'generatedDocument', [])
			->willReturn(5);

		$this->assertSame([], $this->service->listDue());

	}//end testANonArrayResultYieldsNothingDue()

	/**
	 * A failed read is reported as such.
	 *
	 * @return void
	 */
	public function testMarkReviewedReportsAFailedRead(): void {
		$this->objectService->method('find')->willThrowException(new Exception('timeout'));
		$this->objectService->expects($this->never())->method('saveObject');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Could not read that document: timeout');

		$this->service->markReviewed('doc-1');

	}//end testMarkReviewedReportsAFailedRead()

	/**
	 * An unknown document is refused.
	 *
	 * @return void
	 */
	public function testMarkReviewedRefusesAnUnknownDocument(): void {
		$this->objectService->method('find')->willReturn(null);
		$this->objectService->expects($this->never())->method('saveObject');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('There is no generated document with that id.');

		$this->service->markReviewed('doc-1');

	}//end testMarkReviewedRefusesAnUnknownDocument()

	/**
	 * A failed write is reported as such.
	 *
	 * @return void
	 */
	public function testMarkReviewedReportsAFailedWrite(): void {
		$this->objectService->method('find')->willReturn(['uuid' => 'doc-1']);
		$this->objectService->method('saveObject')->willThrowException(new Exception('refused'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Could not record the review: refused');

		$this->service->markReviewed('doc-1');

	}//end testMarkReviewedReportsAFailedWrite()

	/**
	 * Without an interval the review date is kept and the uuid comes back on the result.
	 *
	 * @return void
	 */
	public function testMarkReviewedWithoutIntervalKeepsTheReviewDate(): void {
		$this->objectService->method('find')->willReturn(['uuid' => 'doc-1', 'reviewDate' => '2026-01-01T00:00:00+00:00']);
		$this->objectService->expects($this->once())
			->method('saveObject')
			->with(
				$this->callback(static fn (array $object): bool => isset($object['uuid']) === false
					&& $object['reviewDate'] === '2026-01-01T00:00:00+00:00'),
				'filinq',
				'generatedDocument',
				'doc-1'
			);

		$document = $this->service->markReviewed('doc-1');

		$this->assertSame('doc-1', $document['uuid']);
		$this->assertSame('2026-01-01T00:00:00+00:00', $document['reviewDate']);
		$this->assertNotSame('', $document['reviewedAt']);

	}//end testMarkReviewedWithoutIntervalKeepsTheReviewDate()
}//end class
