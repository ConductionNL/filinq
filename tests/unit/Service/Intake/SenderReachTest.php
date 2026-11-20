<?php

/**
 * Unit tests for SenderReach without openregister's send unit.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Intake
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/intake-acknowledge-and-stamp
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Intake;

use OCA\Filinq\Service\Intake\SenderReach;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Without openregister's notification classes nothing is reached, and the
 * container is never asked.
 *
 * @covers \OCA\Filinq\Service\Intake\SenderReach
 */
class SenderReachTest extends TestCase {

	/**
	 * Skip where openregister's notification classes are loadable.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		if (class_exists('OCA\\OpenRegister\\Notifications\\Service\\Party\\PartyMessage') === true) {
			$this->markTestSkipped('openregister is loadable here; this covers the absent peer only.');
		}
	}//end setUp()

	/**
	 * No party is notified and no address mailed without openregister.
	 *
	 * @return void
	 */
	public function testWithoutOpenRegisterNothingIsReached(): void {
		$container = $this->createMock(ContainerInterface::class);
		$container->expects($this->never())->method('get');
		$reach = new SenderReach($container);

		$this->assertSame([], $reach->notifySenderParties('doc-1', 'subject', 'body'));
		$this->assertSame(SenderReach::OUTCOME_UNAVAILABLE, $reach->mailAddress('j.devries@example.nl', 'subject', 'body'));
	}//end testWithoutOpenRegisterNothingIsReached()
}//end class
