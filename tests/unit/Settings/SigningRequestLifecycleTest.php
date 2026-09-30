<?php

/**
 * Unit tests for the signingRequest lifecycle declaration
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/specs/document-signing/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Settings;

use OCA\Filinq\Service\SigningService;
use PHPUnit\Framework\TestCase;
use ReflectionClassConstant;

/**
 * The register's declared signingRequest lifecycle allows what the signing
 * service does. OpenRegister refuses an update that moves `status` along a
 * transition the schema does not declare, so a step the service allows and
 * the register lacks fails live while every unit test passes.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class SigningRequestLifecycleTest extends TestCase {

	/**
	 * The declared from -> to pairs of the signingRequest lifecycle.
	 *
	 * @return array<int, string> Pairs as "FROM->TO".
	 */
	private function declaredPairs(): array {
		$parsed = json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json'), true);
		$this->assertIsArray($parsed);
		$transitions = $parsed['components']['schemas']['signingRequest']['x-openregister-lifecycle']['transitions'];
		$pairs = [];
		foreach ($transitions as $transition) {
			foreach ((array) $transition['from'] as $from) {
				$pairs[] = $from . '->' . $transition['to'];
			}
		}

		return $pairs;

	}//end declaredPairs()

	/**
	 * Both declines are declared: before signing started and while under way.
	 *
	 * @return void
	 */
	public function testADeclineIsDeclaredFromPendingAndFromInProgress(): void {
		$pairs = $this->declaredPairs();

		$this->assertContains('PENDING->DECLINED', $pairs);
		$this->assertContains('IN_PROGRESS->DECLINED', $pairs);

	}//end testADeclineIsDeclaredFromPendingAndFromInProgress()

	/**
	 * Every status move SigningService allows is a declared transition.
	 *
	 * @return void
	 */
	public function testEveryServiceTransitionIsDeclared(): void {
		$machine = (new ReflectionClassConstant(SigningService::class, 'STATUS_TRANSITIONS'))->getValue();
		$declared = $this->declaredPairs();
		$missing = [];
		foreach ($machine as $from => $targets) {
			foreach ($targets as $to) {
				if (in_array($from . '->' . $to, $declared, true) === false) {
					$missing[] = $from . '->' . $to;
				}
			}
		}

		$this->assertSame([], $missing, 'transitions the service makes but the register does not declare');

	}//end testEveryServiceTransitionIsDeclared()
}//end class
