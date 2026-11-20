<?php

/**
 * Unit tests for IntakeRepository's duplicate and overdue reads.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link https://conduction.nl
 *
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * An earlier copy that was not rejected is the duplicate; an overdue document is one its rule does not count as handled.
 *
 * @covers \OCA\Filinq\Service\IntakeRepository
 * @uses   \OCA\Filinq\Service\StoredRecordFields
 * @uses   \OCA\Filinq\Service\ReadsStoredObjectRows
 */
class IntakeRepositorySignalsTest extends TestCase {

	/**
	 * The fake OpenRegister object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objectService;

	/**
	 * Every search, as [schema, filters].
	 *
	 * @var array<int, array{0: string, 1: array<string, mixed>}>
	 */
	private array $searches = [];

	/**
	 * The repository over rows answered per schema and filters.
	 *
	 * @param callable $answer fn (string $schema, array $filters): array.
	 *
	 * @return IntakeRepository
	 */
	private function repository(callable $answer): IntakeRepository {
		$this->objectService = $this->createMock(ObjectService::class);
		$this->objectService->method('searchObjectsBySlug')->willReturnCallback(
			function (string $register, string $schema, array $filters) use ($answer): array {
				$this->searches[] = [$schema, $filters];
				return $answer($schema, $filters);
			}
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objectService);

		return new IntakeRepository($resolver, $this->createMock(LoggerInterface::class));
	}//end repository()

	/**
	 * A stored intake document row.
	 *
	 * @param string               $uuid   The uuid.
	 * @param array<string, mixed> $fields Its fields.
	 *
	 * @return array<string, mixed>
	 */
	private function row(string $uuid, array $fields): array {
		return $fields + ['@self' => ['uuid' => $uuid]];
	}//end row()

	/**
	 * The earliest copy that was not rejected is the one a newcomer duplicates.
	 *
	 * @return void
	 */
	public function testTheEarliestCopyNotRejectedIsTheDuplicate(): void {
		$repository = $this->repository(
			fn (string $schema, array $filters): array => [
				$this->row('late', ['status' => 'received', 'receivedAt' => '2026-10-07T09:00:00+00:00']),
				$this->row('rejected', ['status' => 'rejected', 'receivedAt' => '2026-10-01T09:00:00+00:00']),
				$this->row('first', ['status' => 'assigned', 'receivedAt' => '2026-10-05T09:00:00+00:00']),
			]
		);

		self::assertSame('first', $repository->findByContentHash(contentHash: 'abc')['uuid']);
		self::assertSame([['intakeDocument', ['contentHash' => 'abc']]], $this->searches);
	}//end testTheEarliestCopyNotRejectedIsTheDuplicate()

	/**
	 * Only rejected copies, no copies or no hash make no duplicate.
	 *
	 * @return void
	 */
	public function testARejectedCopyIsNoDuplicate(): void {
		$repository = $this->repository(fn (): array => [$this->row('rejected', ['status' => 'rejected'])]);
		self::assertNull($repository->findByContentHash(contentHash: 'abc'));

		$empty = $this->repository(fn (): array => []);
		self::assertNull($empty->findByContentHash(contentHash: 'abc'));

		$this->searches = [];
		self::assertNull($empty->findByContentHash(contentHash: ''));
		self::assertSame([], $this->searches);
	}//end testARejectedCopyIsNoDuplicate()

	/**
	 * Waiting and detached documents past their deadline are overdue; one due today or later is not.
	 *
	 * @return void
	 */
	public function testAWaitingDocumentPastItsDeadlineIsOverdue(): void {
		$repository = $this->repository(
			fn (string $schema, array $filters): array => match ($filters['status'] ?? null) {
				'received' => [
					$this->row('five-late', ['handlingDeadline' => '2026-10-08']),
					$this->row('today', ['handlingDeadline' => '2026-10-13']),
					$this->row('no-deadline', []),
				],
				'detached' => [$this->row('detached-late', ['handlingDeadline' => '2026-10-12'])],
				default => [],
			}
		);

		$overdue = $repository->findOverdue(today: '2026-10-13', handledWhen: []);

		self::assertSame(['five-late', 'detached-late'], array_column($overdue, 'uuid'));
	}//end testAWaitingDocumentPastItsDeadlineIsOverdue()

	/**
	 * An assigned document is handled under `assigned`, also without a rule, and open under `answered` until an answer is registered.
	 *
	 * @return void
	 */
	public function testAnAssignedDocumentIsOpenUntilItsRuleIsMet(): void {
		$repository = $this->repository(
			fn (string $schema, array $filters): array => match (true) {
				$schema === 'intakeDocument' && ($filters['status'] ?? null) === 'assigned' => [
					$this->row('assigned-done', ['handlingDeadline' => '2026-10-01', 'defaultRule' => 'r-assigned']),
					$this->row('no-rule', ['handlingDeadline' => '2026-10-01', 'defaultRule' => '']),
					$this->row('unanswered', ['handlingDeadline' => '2026-10-02', 'defaultRule' => 'r-answered']),
					$this->row('answered', ['handlingDeadline' => '2026-10-01', 'defaultRule' => 'r-answered']),
				],
				$schema === 'documentRegistration' && ($filters['direction'] ?? null) === 'inbound' => match ($filters['document']) {
					'unanswered' => [$this->row('in-1', [])],
					'answered' => [$this->row('in-2', [])],
					default => [],
				},
				$schema === 'documentRegistration' && ($filters['direction'] ?? null) === 'outbound' => (
					$filters['answers'] === 'in-2' ? [$this->row('out-1', ['answers' => 'in-2'])] : []
				),
				default => [],
			}
		);

		$overdue = $repository->findOverdue(
			today: '2026-10-13',
			handledWhen: ['r-assigned' => 'assigned', 'r-answered' => 'answered']
		);

		self::assertSame(['unanswered'], array_column($overdue, 'uuid'));
		self::assertContains(['documentRegistration', ['document' => 'answered', 'direction' => 'inbound']], $this->searches);
		self::assertContains(['documentRegistration', ['answers' => 'in-2', 'direction' => 'outbound']], $this->searches);
	}//end testAnAssignedDocumentIsOpenUntilItsRuleIsMet()

	/**
	 * Rejected documents are never read for the overdue list.
	 *
	 * @return void
	 */
	public function testRejectedDocumentsAreNeverOverdue(): void {
		$repository = $this->repository(fn (): array => []);
		$repository->findOverdue(today: '2026-10-13', handledWhen: []);

		self::assertSame(
			[['intakeDocument', ['status' => 'received']], ['intakeDocument', ['status' => 'detached']], ['intakeDocument', ['status' => 'assigned']]],
			$this->searches
		);
	}//end testRejectedDocumentsAreNeverOverdue()
}//end class
