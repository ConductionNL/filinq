<?php

/**
 * Unit tests for PolicyRejectedException
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Exception
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

namespace OCA\Filinq\Tests\Unit\Exception;

use OCA\Filinq\Exception\PolicyRejectedException;
use RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PolicyRejectedException: its default message and code, and the rule identity.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[CoversClass(PolicyRejectedException::class)]
class PolicyRejectedExceptionTest extends TestCase {

	/**
	 * Without a message the default names the prohibition, with code 0.
	 *
	 * @return void
	 */
	public function testDefaultsNameThePolicyProhibition(): void {
		$e = new PolicyRejectedException(ruleUuid: 'rule-1', ruleName: 'No personal data');

		$this->assertSame(expected: 'Publication prohibited by policy rule', actual: $e->getMessage());
		$this->assertSame(expected: 0, actual: $e->getCode());
		$this->assertSame(expected: 'rule-1', actual: $e->getRuleUuid());
		$this->assertSame(expected: 'No personal data', actual: $e->getRuleName());
		$this->assertNull(actual: $e->getPrevious());

	}//end testDefaultsNameThePolicyProhibition()

	/**
	 * An explicit message, code and cause are passed to the parent.
	 *
	 * @return void
	 */
	public function testExplicitMessageCodeAndCauseAreKept(): void {
		$cause = new RuntimeException(message: 'evaluator');
		$e	 = new PolicyRejectedException(ruleUuid: 'rule-2', ruleName: 'Embargo', message: 'Under embargo.', code: 422, previous: $cause);

		$this->assertSame(expected: 'Under embargo.', actual: $e->getMessage());
		$this->assertSame(expected: 422, actual: $e->getCode());
		$this->assertSame(expected: $cause, actual: $e->getPrevious());

	}//end testExplicitMessageCodeAndCauseAreKept()
}//end class
