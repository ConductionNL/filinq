<?php

/**
 * Unit tests for PlainRenditionRefusedException
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

use OCA\Filinq\Exception\PlainRenditionRefusedException;
use RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PlainRenditionRefusedException: the 400 code contract and the unresolved list.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[CoversClass(PlainRenditionRefusedException::class)]
class PlainRenditionRefusedExceptionTest extends TestCase {

	/**
	 * The refusal carries code 400 and an empty unresolved list by default.
	 *
	 * @return void
	 */
	public function testRefusalCarries400AndNoUnresolvedByDefault(): void {
		$e = new PlainRenditionRefusedException(message: 'Letter not ready.');

		$this->assertSame(expected: 'Letter not ready.', actual: $e->getMessage());
		$this->assertSame(expected: 400, actual: $e->getCode());
		$this->assertSame(expected: [], actual: $e->getUnresolved());
		$this->assertNull(actual: $e->getPrevious());

	}//end testRefusalCarries400AndNoUnresolvedByDefault()

	/**
	 * The unresolved items and the cause are kept as given, code still 400.
	 *
	 * @return void
	 */
	public function testRefusalKeepsUnresolvedItemsAndCause(): void {
		$cause = new RuntimeException(message: 'render failed');
		$e	 = new PlainRenditionRefusedException(message: 'Refused.', unresolved: ['{{naam}}', '{{adres}}'], previous: $cause);

		$this->assertSame(expected: 400, actual: $e->getCode());
		$this->assertSame(expected: ['{{naam}}', '{{adres}}'], actual: $e->getUnresolved());
		$this->assertSame(expected: $cause, actual: $e->getPrevious());

	}//end testRefusalKeepsUnresolvedItemsAndCause()
}//end class
