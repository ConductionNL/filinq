<?php

/**
 * Unit tests for the pseudonymisation routes
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-5.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;
use OCA\Filinq\Controller\PseudonymisationController;
use OCA\Filinq\Service\Pseudonymisation\PseudonymRestoreAudit;
use OCA\Filinq\Tests\Unit\Service\Pseudonymisation\PseudonymDoubles;
use OCP\AppFramework\Http;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

/**
 * The HTTP answers of the restore route, over the real service.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PseudonymisationControllerTest extends TestCase {
	use PseudonymDoubles;

	/**
	 * The controller for one signed-in user over a reversible run.
	 *
	 * @param string $userId The caller.
	 * @param array<int, array{0: string, 1: string}> $written Receives files written.
	 *
	 * @return PseudonymisationController The controller.
	 */
	private function controllerFor(string $userId, array &$written): PseudonymisationController {
		$container = $this->container();
		$this->seedReversibleRun(container: $container, pairs: $this->twoPeople());
		$service = $this->restoreService(
			gate: $this->gate(allowedGroups: '["privacy-officers"]', membership: ['petra' => ['privacy-officers'], 'bob' => []]),
			copy: $this->anonymisedCopy(mimeType: 'text/plain', content: '[PERSOON: 1]', written: $written),
			container: $container
		);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($userId);
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new PseudonymisationController('filinq', $this->createMock(IRequest::class), $service, $session, $l10n);

	}//end controllerFor()

	/**
	 * A non-member gets a neutral 403: no reason, no hint whether the list is empty.
	 *
	 * @return void
	 */
	public function testANonMemberGetsANeutral403(): void {
		$written = [];
		$response = $this->controllerFor(userId: 'bob', written: $written)->restore(linkId: 'link-1');

		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
		$this->assertSame(['error' => 'You are not allowed to restore names.'], $response->getData());
		$this->assertSame([], $written);

	}//end testANonMemberGetsANeutral403()

	/**
	 * A failed audit write refuses the restore with 503 and writes nothing.
	 *
	 * @return void
	 */
	public function testFailedAuditRefusesRestore(): void {
		$this->failingAuditActions = [PseudonymRestoreAudit::ACTION_GRANTED];
		$written = [];
		$response = $this->controllerFor(userId: 'petra', written: $written)->restore(linkId: 'link-1');

		$this->assertSame(Http::STATUS_SERVICE_UNAVAILABLE, $response->getStatus());
		$this->assertSame('audit_unavailable', $response->getData()['reason']);
		$this->assertSame([], $written);

	}//end testFailedAuditRefusesRestore()

	/**
	 * A permitted user gets the restored copy.
	 *
	 * @return void
	 */
	public function testAPermittedUserGetsTheCopy(): void {
		$written = [];
		$response = $this->controllerFor(userId: 'petra', written: $written)->restore(linkId: 'link-1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame('copy', $response->getData()['mode']);
		$this->assertSame([['besluit_restored.txt', 'Jan Jansen']], $written);

	}//end testAPermittedUserGetsTheCopy()
}//end class
