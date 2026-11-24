<?php

/**
 * Unit tests for WaitingWorkService
 *
 * The Waiting for me list: a pending signature, an intake document routed
 * to the person's group, and a document they own that is due for review,
 * each saying why it waits (specify-capability-rows REQ-SCR-006).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#2-documents
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Kernel\Contracts\DocumentReviewServiceInterface;
use OCA\Filinq\Kernel\Contracts\IntakeRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\SigningFolderServiceInterface;
use OCA\Filinq\Service\WaitingWorkService;
use OCP\IGroupManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\WaitingWorkService
 */
#[CoversClass(WaitingWorkService::class)]
class WaitingWorkServiceTest extends TestCase {

	/**
	 * @var SigningFolderServiceInterface&MockObject
	 */
	private SigningFolderServiceInterface&MockObject $folder;

	/**
	 * @var IntakeRepositoryInterface&MockObject
	 */
	private IntakeRepositoryInterface&MockObject $intake;

	/**
	 * @var DocumentReviewServiceInterface&MockObject
	 */
	private DocumentReviewServiceInterface&MockObject $reviews;

	/**
	 * @var IGroupManager&MockObject
	 */
	private IGroupManager&MockObject $groups;

	/**
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * @var WaitingWorkService
	 */
	private WaitingWorkService $service;

	/**
	 * Set up the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->folder  = $this->createMock(SigningFolderServiceInterface::class);
		$this->intake  = $this->createMock(IntakeRepositoryInterface::class);
		$this->reviews = $this->createMock(DocumentReviewServiceInterface::class);
		$this->groups  = $this->createMock(IGroupManager::class);
		$this->logger  = $this->createMock(LoggerInterface::class);
		$this->service = new WaitingWorkService(
			signingFolder: $this->folder,
			intake: $this->intake,
			reviews: $this->reviews,
			groups: $this->groups,
			logger: $this->logger
		);

	}//end setUp()

	/**
	 * Three kinds of waiting work, each with its reason, signatures first.
	 *
	 * @return void
	 */
	public function testThreeKindsOfWaitingWorkAreListedWithTheirReason(): void {
		$this->folder->expects($this->once())->method('folder')->with(userId: 'bea', limit: 200)->willReturn(
			[
				'entries' => [
					['requestId' => 'req-1', 'documentName' => 'Besluit', 'requestedAt' => '2026-11-01T09:00:00+00:00'],
					['requestId' => 'req-2', 'documentName' => 'Huurcontract', 'requestedAt' => '', 'role' => 'accept'],
				],
			]
		);
		$this->intake->expects($this->once())->method('findByStatus')->with(status: 'assigned')->willReturn(
			[
				[
					'uuid' => 'in-1', 'fileName' => 'Brief.pdf', 'routing' => 'post', 'assignedAt' => '2026-11-02T09:00:00+00:00',
					'acceptance' => ['required' => true, 'acceptedBy' => '', 'acceptedAt' => ''],
				],
			]
		);
		$this->groups->method('isInGroup')->with('bea', 'post')->willReturn(true);
		$this->reviews->method('listDue')->willReturn(
			[['uuid' => 'gen-1', 'templateName' => 'Beleidsregel', 'owner' => 'bea', 'reviewDate' => '2026-10-01']]
		);

		$items = $this->service->listFor(userId: 'bea');

		$this->assertSame(
			[
				['kind' => 'signature', 'id' => 'req-1', 'name' => 'Besluit', 'reason' => 'Waiting for your signature', 'since' => '2026-11-01T09:00:00+00:00'],
				['kind' => 'signature', 'id' => 'req-2', 'name' => 'Huurcontract', 'reason' => 'Waiting for your acceptance', 'since' => ''],
				['kind' => 'intake', 'id' => 'in-1', 'name' => 'Brief.pdf', 'reason' => 'Routed to your group, waiting for acceptance', 'since' => '2026-11-02T09:00:00+00:00'],
				['kind' => 'review', 'id' => 'gen-1', 'name' => 'Beleidsregel', 'reason' => 'Due for review', 'since' => '2026-10-01'],
			],
			$items
		);

	}//end testThreeKindsOfWaitingWorkAreListedWithTheirReason()

	/**
	 * Only the person's own work: an intake document of another group, one
	 * already accepted, one that needs no acceptance, and someone else's
	 * review are all left out.
	 *
	 * @return void
	 */
	public function testOnlyThePersonsOwnWorkIsListed(): void {
		$this->folder->method('folder')->willReturn(['entries' => []]);
		$this->intake->method('findByStatus')->willReturn(
			[
				['uuid' => 'other-group', 'routing' => 'juristen', 'acceptance' => ['required' => true, 'acceptedBy' => '']],
				['uuid' => 'accepted', 'routing' => 'post', 'acceptance' => ['required' => true, 'acceptedBy' => 'carl']],
				['uuid' => 'no-acceptance', 'routing' => 'post', 'acceptance' => ['required' => false]],
				['uuid' => 'not-routed', 'routing' => '', 'acceptance' => ['required' => true, 'acceptedBy' => '']],
			]
		);
		$this->groups->method('isInGroup')->willReturnCallback(
			static fn (string $user, string $group): bool => ($group === 'post')
		);
		$this->reviews->method('listDue')->willReturn([['uuid' => 'gen-2', 'owner' => 'carl'], ['uuid' => 'gen-3']]);

		$this->assertSame([], $this->service->listFor(userId: 'bea'));

	}//end testOnlyThePersonsOwnWorkIsListed()

	/**
	 * A source that cannot be read is left out and logged; the others still show.
	 *
	 * @return void
	 */
	public function testAnUnreadableSourceLeavesTheOthers(): void {
		$this->folder->method('folder')->willThrowException(new RuntimeException('down'));
		$this->intake->method('findByStatus')->willThrowException(new RuntimeException('down'));
		$this->reviews->method('listDue')->willReturn([['uuid' => 'gen-1', 'owner' => 'bea', 'templateName' => 'Nota']]);
		$this->logger->expects($this->exactly(2))->method('warning');

		$items = $this->service->listFor(userId: 'bea');

		$this->assertSame(['review'], array_column($items, 'kind'));

	}//end testAnUnreadableSourceLeavesTheOthers()

	/**
	 * Without a person nothing is read.
	 *
	 * @return void
	 */
	public function testNobodyIsRefused(): void {
		$this->folder->expects($this->never())->method('folder');
		$this->expectException(RuntimeException::class);

		$this->service->listFor(userId: '');

	}//end testNobodyIsRefused()
}//end class
