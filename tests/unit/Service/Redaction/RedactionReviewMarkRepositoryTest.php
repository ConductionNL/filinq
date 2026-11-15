<?php

/**
 * Unit tests for RedactionReviewMarkRepository: the mark that a document's
 * redactions were checked, which gates its output.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Redaction
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Redaction;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Redaction\RedactionReviewMarkRepository;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Reading the latest mark by slug, failing closed, and recording a mark.
 *
 * @covers \OCA\Filinq\Service\Redaction\RedactionReviewMarkRepository
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 */
class RedactionReviewMarkRepositoryTest extends TestCase {

	/**
	 * The object service double.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * The logger double.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * The repository over the doubles.
	 *
	 * @return RedactionReviewMarkRepository
	 */
	private function repository(): RedactionReviewMarkRepository {
		$this->objects = $this->createMock(originalClassName: ObjectService::class);
		$this->logger  = $this->createMock(originalClassName: LoggerInterface::class);

		$container = $this->createMock(originalClassName: ContainerInterface::class);
		$container->method('get')->willReturn($this->objects);
		$apps = $this->createMock(originalClassName: IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		return new RedactionReviewMarkRepository(
			objectResolver: new DocumentObjectServiceResolver(container: $container, appManager: $apps),
			logger: $this->logger
		);
	}//end repository()

	/**
	 * The mark is looked up by slug for the document, and the most recently
	 * checked one wins, with its metadata kept.
	 *
	 * @return void
	 */
	public function testTheLatestMarkForTheDocumentIsFound(): void {
		$repository = $this->repository();
		$this->objects->expects($this->once())
			->method('searchObjectsBySlug')
			->with('filinq', 'redactionReviewMark', ['document' => 'doc-1'])
			->willReturn(
				[
					['document' => 'doc-1', 'checkedAt' => '2026-10-01T10:00:00+00:00', '@self' => ['id' => 'm-1']],
					'not a row',
					['document' => 'doc-1', 'checkedAt' => '2026-10-03T10:00:00+00:00', '@self' => ['id' => 'm-3']],
					['document' => 'doc-1', 'checkedAt' => '2026-10-02T10:00:00+00:00', '@self' => ['id' => 'm-2']],
				]
			);

		$this->assertSame(
			expected: ['document' => 'doc-1', 'checkedAt' => '2026-10-03T10:00:00+00:00', '@self' => ['id' => 'm-3'], 'uuid' => 'm-3'],
			actual: $repository->findFor(document: 'doc-1')
		);
	}//end testTheLatestMarkForTheDocumentIsFound()

	/**
	 * With equal check times the first row found is kept.
	 *
	 * @return void
	 */
	public function testAnEqualCheckTimeKeepsTheFirstMark(): void {
		$repository = $this->repository();
		$this->objects->method('searchObjectsBySlug')->willReturn(
			[
				['checkedAt' => '2026-10-01', 'uuid' => 'first'],
				['checkedAt' => '2026-10-01', 'uuid' => 'second'],
			]
		);

		$this->assertSame(expected: 'first', actual: $repository->findFor(document: 'doc-1')['uuid']);
	}//end testAnEqualCheckTimeKeepsTheFirstMark()

	/**
	 * A blank document asks nothing; no rows, or a count instead of rows,
	 * means no mark.
	 *
	 * @return void
	 */
	public function testNoMarkWithoutRows(): void {
		$repository = $this->repository();
		$this->objects->expects($this->exactly(count: 3))
			->method('searchObjectsBySlug')
			->willReturnOnConsecutiveCalls([], 0, ['not a row']);

		$this->assertNull(actual: $repository->findFor(document: ' '));
		$this->assertNull(actual: $repository->findFor(document: 'doc-1'));
		$this->assertNull(actual: $repository->findFor(document: 'doc-1'));
		$this->assertNull(actual: $repository->findFor(document: 'doc-1'));
	}//end testNoMarkWithoutRows()

	/**
	 * A failed read means no mark, so output is refused, and the failure is
	 * logged with the document and the cause.
	 *
	 * @return void
	 */
	public function testAFailedReadFailsClosedAndIsLogged(): void {
		$repository = $this->repository();
		$this->objects->method('searchObjectsBySlug')->willThrowException(new RuntimeException(message: 'db down'));
		$this->logger->expects($this->once())
			->method('warning')
			->with(
				'[RedactionReviewMarkRepository] could not read the review mark, so output is refused',
				$this->callback(callback: static fn (array $context): bool => $context['document'] === 'doc-1' && $context['error'] === 'db down')
			);

		$this->assertNull(actual: $repository->findFor(document: 'doc-1'));
	}//end testAFailedReadFailsClosedAndIsLogged()

	/**
	 * A mark is saved in Filinq's register and comes back with its metadata
	 * and uuid.
	 *
	 * @return void
	 */
	public function testAMarkIsSaved(): void {
		$repository = $this->repository();
		$mark       = ['document' => 'doc-1', 'checkedBy' => 'alice', 'checkedAt' => '2026-10-03T10:00:00+00:00'];
		$this->objects->expects($this->once())
			->method('saveObject')
			->with($mark, 'filinq', 'redactionReviewMark')
			->willReturn(['object' => $mark, '@self' => ['id' => 'm-9']]);

		$this->assertSame(expected: $mark + ['uuid' => 'm-9'], actual: $repository->save(mark: $mark));
	}//end testAMarkIsSaved()

	/**
	 * A failed save says the document stays unapproved and keeps the cause.
	 *
	 * @return void
	 */
	public function testAFailedSaveLeavesTheDocumentUnapproved(): void {
		$repository = $this->repository();
		$cause      = new RuntimeException(message: 'quota');
		$this->objects->method('saveObject')->willThrowException($cause);

		try {
			$repository->save(mark: ['document' => 'doc-1']);
			$this->fail(message: 'a failed save must throw');
		} catch (RuntimeException $e) {
			$this->assertSame(expected: 'The check could not be recorded, so the document stays unapproved: quota', actual: $e->getMessage());
			$this->assertSame(expected: $cause, actual: $e->getPrevious());
		}
	}//end testAFailedSaveLeavesTheDocumentUnapproved()
}//end class
