<?php

/**
 * Unit tests for StepUpRequiredException
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

use OCA\Filinq\Exception\StepUpRequiredException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for StepUpRequiredException: the step-up refusal message, 403 code and response payload.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[CoversClass(StepUpRequiredException::class)]
class StepUpRequiredExceptionTest extends TestCase {

	/**
	 * The message composes the reason and required assurance, and the code is 403.
	 *
	 * @return void
	 */
	public function testMessageNamesReasonAndRequiredAssuranceWith403(): void {
		$e = new StepUpRequiredException(reason: 'insufficient', requiredAssurance: 'eIDAS-hoog', heldAssurance: 'eIDAS-laag', provider: 'digid');

		$this->assertSame(expected: 'Identity evidence insufficient: this act needs assurance eIDAS-hoog', actual: $e->getMessage());
		$this->assertSame(expected: 403, actual: $e->getCode());
		$this->assertNull(actual: $e->getPrevious());

	}//end testMessageNamesReasonAndRequiredAssuranceWith403()

	/**
	 * stepUp() returns the required flag and every constructor field, in a fixed key order.
	 *
	 * @return void
	 */
	public function testStepUpPayloadCarriesEveryFieldInOrder(): void {
		$e = new StepUpRequiredException(reason: 'expired', requiredAssurance: 'substantial', heldAssurance: 'low', provider: 'eherkenning');

		$this->assertSame(
			expected: [
				'required' => true,
				'reason' => 'expired',
				'requiredAssurance' => 'substantial',
				'heldAssurance' => 'low',
				'provider' => 'eherkenning',
			],
			actual: $e->stepUp()
		);

	}//end testStepUpPayloadCarriesEveryFieldInOrder()
}//end class
