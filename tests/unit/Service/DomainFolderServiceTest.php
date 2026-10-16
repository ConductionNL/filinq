<?php

/**
 * A domain's folder, and the reconciliation that must not lie about itself.
 *
 * 🔴 THE HARD REQUIREMENT IS NOT THE CORRECTING, IT IS THE NOT CLAIMING.
 * REQ-CDF-02's third scenario is a mount that REFUSES a permission change: the
 * job must report the folder, the permission and the reason, and must not claim
 * success. A reconciler that swallows a refusal and reports "reconciled" is
 * worse than none, because somebody reads that line and stops looking while a
 * group that should have lost access still has it.
 *
 * 🔑 AND A FAILED REVOKE IS THE ONE THAT MATTERS. A failed grant leaves somebody
 * without access, and they report it within the hour. A failed revoke leaves
 * somebody WITH access nobody meant them to have, and nobody reports that ever.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DomainFolderGateway;
use OCA\Filinq\Service\DomainFolderService;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * `DomainFolderService`.
 *
 * @covers \OCA\Filinq\Service\DomainFolderService
 */
class DomainFolderServiceTest extends TestCase {

	/**
	 * A domain with the given groups.
	 *
	 * @param array<int, string> $groups The declared groups.
	 * @param string             $pinned A pin reason, or ''.
	 *
	 * @return array<string, mixed> The domain.
	 */
	private function domain(array $groups, string $pinned = ''): array {
		$domain = ['id' => 'zaak-1', 'groups' => $groups];

		if ($pinned !== '') {
			$domain[DomainFolderService::PIN_KEY] = $pinned;
		}

		return $domain;
	}//end domain()

	/**
	 * A service whose gateway behaves as told.
	 *
	 * @param DomainFolderGateway $gateway The gateway double.
	 *
	 * @return DomainFolderService The service.
	 */
	private function service(DomainFolderGateway $gateway): DomainFolderService {
		return new DomainFolderService(
			$this->createMock(IRootFolder::class),
			$gateway,
			new NullLogger()
		);
	}//end service()

	/**
	 * A gateway reporting the given groups and accepting every change.
	 *
	 * @param array<int, string> $observed What currently has access.
	 *
	 * @return DomainFolderGateway The double.
	 */
	private function willingGateway(array $observed): DomainFolderGateway {
		$gateway = $this->createMock(DomainFolderGateway::class);
		$gateway->method('groupsWithAccess')->willReturn($observed);

		return $gateway;
	}//end willingGateway()

	/**
	 * A folder already matching the domain is left alone and says so.
	 *
	 * @return void
	 */
	public function testAFolderInStepIsLeftAlone(): void {
		$outcome = $this->service($this->willingGateway(['groep-a', 'groep-b']))
			->reconcile(domain: $this->domain(['groep-a', 'groep-b']), owner: 'beheerder');

		$this->assertSame(DomainFolderService::STATE_IN_STEP, $outcome['state']);
		$this->assertSame([], $outcome['granted']);
		$this->assertSame([], $outcome['revoked']);
	}//end testAFolderInStepIsLeftAlone()

	/**
	 * A group added to the domain gains access, and it is reported.
	 *
	 * @return void
	 */
	public function testAGroupAddedToTheDomainGainsAccess(): void {
		$gateway = $this->willingGateway(['groep-a']);
		$gateway->expects($this->once())->method('grant')
			->with($this->anything(), $this->anything(), $this->equalTo('groep-b'));

		$outcome = $this->service($gateway)
			->reconcile(domain: $this->domain(['groep-a', 'groep-b']), owner: 'beheerder');

		$this->assertSame(DomainFolderService::STATE_CORRECTED, $outcome['state']);
		$this->assertSame(['groep-b'], $outcome['granted']);
	}//end testAGroupAddedToTheDomainGainsAccess()

	/**
	 * A member who leaves loses access, which is the scenario's whole point.
	 *
	 * @return void
	 */
	public function testAGroupRemovedFromTheDomainLosesAccess(): void {
		$gateway = $this->willingGateway(['groep-a', 'groep-oud']);
		$gateway->expects($this->once())->method('revoke')
			->with($this->anything(), $this->anything(), $this->equalTo('groep-oud'));

		$outcome = $this->service($gateway)
			->reconcile(domain: $this->domain(['groep-a']), owner: 'beheerder');

		$this->assertSame(DomainFolderService::STATE_CORRECTED, $outcome['state']);
		$this->assertSame(['groep-oud'], $outcome['revoked']);
	}//end testAGroupRemovedFromTheDomainLosesAccess()

	/**
	 * 🔴 A MOUNT THAT REFUSES IS REPORTED, AND SUCCESS IS NOT CLAIMED.
	 *
	 * The requirement's own third scenario. It names the folder, the permission
	 * and the reason.
	 *
	 * @return void
	 */
	public function testAMountThatRefusesIsReportedAndNotClaimedAsSuccess(): void {
		$gateway = $this->willingGateway(['groep-a', 'groep-oud']);
		$gateway->method('revoke')->willThrowException(new NotPermittedException('mount is read only'));

		$outcome = $this->service($gateway)
			->reconcile(domain: $this->domain(['groep-a']), owner: 'beheerder');

		$this->assertSame(DomainFolderService::STATE_REFUSED, $outcome['state']);
		$this->assertSame('groep-oud', $outcome['refused'][0]['group']);
		$this->assertSame('revoke', $outcome['refused'][0]['action']);
		$this->assertStringContainsString('read only', $outcome['refused'][0]['reason']);
		$this->assertStringContainsString('zaak-1', $outcome['path']);
	}//end testAMountThatRefusesIsReportedAndNotClaimedAsSuccess()

	/**
	 * 🔴 A PARTLY RECONCILED FOLDER IS REFUSED, NOT CORRECTED.
	 *
	 * This is the assertion that stops the report lying. One grant landing and
	 * one revoke refusing is NOT a corrected folder: somebody still has access
	 * nobody meant them to have, and reporting "corrected" is the line a person
	 * reads before they stop looking.
	 *
	 * @return void
	 */
	public function testAPartlyReconciledFolderIsRefusedRatherThanCorrected(): void {
		$gateway = $this->willingGateway(['groep-oud']);
		$gateway->method('revoke')->willThrowException(new RuntimeException('mount refused'));

		$outcome = $this->service($gateway)
			->reconcile(domain: $this->domain(['groep-nieuw']), owner: 'beheerder');

		$this->assertSame(
			DomainFolderService::STATE_REFUSED,
			$outcome['state'],
			'A folder where one change landed and another was refused is not reconciled.'
		);
		$this->assertSame(['groep-nieuw'], $outcome['granted'], 'What did land is still reported.');
		$this->assertNotSame([], $outcome['refused']);
	}//end testAPartlyReconciledFolderIsRefusedRatherThanCorrected()

	/**
	 * A gateway that cannot even be read is a refusal, not an empty folder.
	 *
	 * Reading "no groups have access" from a failed read would revoke nothing
	 * and grant everything, which is the widest possible wrong answer.
	 *
	 * @return void
	 */
	public function testAFolderThatCannotBeReadIsRefusedNotTreatedAsEmpty(): void {
		$gateway = $this->createMock(DomainFolderGateway::class);
		$gateway->method('groupsWithAccess')->willThrowException(new RuntimeException('storage down'));
		$gateway->expects($this->never())->method('grant');
		$gateway->expects($this->never())->method('revoke');

		$outcome = $this->service($gateway)
			->reconcile(domain: $this->domain(['groep-a']), owner: 'beheerder');

		$this->assertSame(DomainFolderService::STATE_REFUSED, $outcome['state']);
	}//end testAFolderThatCannotBeReadIsRefusedNotTreatedAsEmpty()

	/**
	 * 🔑 A PINNED FOLDER IS REPORTED AS PINNED, WITH THE REASON.
	 *
	 * Omitting it would leave a gap in the report, and the whole point of
	 * pinning is that the next person reads why.
	 *
	 * @return void
	 */
	public function testAPinnedFolderIsReportedWithItsReason(): void {
		$gateway = $this->createMock(DomainFolderGateway::class);
		$gateway->expects($this->never())->method('groupsWithAccess');

		$outcome = $this->service($gateway)->reconcile(
			domain: $this->domain(['groep-a'], 'migratie loopt tot 1 oktober'),
			owner: 'beheerder'
		);

		$this->assertSame(DomainFolderService::STATE_PINNED, $outcome['state']);
		$this->assertSame('migratie loopt tot 1 oktober', $outcome['pinnedReason']);
	}//end testAPinnedFolderIsReportedWithItsReason()

	/**
	 * A pin with no reason is not a pin.
	 *
	 * An empty reason is somebody setting the key and not saying why, which is
	 * the state the requirement's "recorded reason" exists to prevent.
	 *
	 * @return void
	 */
	public function testAPinWithNoReasonDoesNotStopReconciliation(): void {
		$outcome = $this->service($this->willingGateway(['groep-a']))->reconcile(
			domain: $this->domain(['groep-a'], '   '),
			owner: 'beheerder'
		);

		$this->assertSame(DomainFolderService::STATE_IN_STEP, $outcome['state']);
	}//end testAPinWithNoReasonDoesNotStopReconciliation()

	/**
	 * A folder that cannot be created is reported, not thrown.
	 *
	 * The caller is a domain create; failing that write because the storage
	 * refused would lose the domain over something its author cannot act on.
	 *
	 * @return void
	 */
	public function testAFolderThatCannotBeCreatedIsReportedNotThrown(): void {
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willThrowException(new NotPermittedException('no storage'));

		$service = new DomainFolderService(
			$root,
			$this->createMock(DomainFolderGateway::class),
			new NullLogger()
		);

		$outcome = $service->ensureFolder(domain: $this->domain([]), owner: 'beheerder');

		$this->assertFalse($outcome['created']);
		$this->assertStringContainsString('no storage', (string)$outcome['error']);
	}//end testAFolderThatCannotBeCreatedIsReportedNotThrown()

	/**
	 * An existing folder is not made again.
	 *
	 * @return void
	 */
	public function testAnExistingFolderIsNotMadeAgain(): void {
		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('nodeExists')->willReturn(true);
		$userFolder->expects($this->never())->method('newFolder');

		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($userFolder);

		$service = new DomainFolderService(
			$root,
			$this->createMock(DomainFolderGateway::class),
			new NullLogger()
		);

		$this->assertFalse($service->ensureFolder(domain: $this->domain([]), owner: 'beheerder')['created']);
	}//end testAnExistingFolderIsNotMadeAgain()
}//end class
