<?php

/**
 * Unit tests for PublicationNotReadyException
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

use OCA\Filinq\Exception\PublicationNotReadyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PublicationNotReadyException: the fixed 409 refusal and its reasons.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[CoversClass(PublicationNotReadyException::class)]
class PublicationNotReadyExceptionTest extends TestCase {

	/**
	 * The refusal has a fixed message, code 409 and the reasons as given.
	 *
	 * @return void
	 */
	public function testRefusalHasFixedMessage409AndReasons(): void {
		$reasons = [['field' => 'title', 'reason' => 'missing'], ['field' => 'woo', 'reason' => 'unclassified']];
		$e	   = new PublicationNotReadyException(reasons: $reasons);

		$this->assertSame(expected: 'The publication is not ready to hand off', actual: $e->getMessage());
		$this->assertSame(expected: 409, actual: $e->getCode());
		$this->assertSame(expected: $reasons, actual: $e->getReasons());

	}//end testRefusalHasFixedMessage409AndReasons()
}//end class
