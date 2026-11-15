<?php

/**
 * Unit tests for Pdfa3ConversionException
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

use OCA\Filinq\Exception\Pdfa3ConversionException;
use RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Pdfa3ConversionException: its default 500 code, reason and admin hint.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[CoversClass(Pdfa3ConversionException::class)]
class Pdfa3ConversionExceptionTest extends TestCase {

	/**
	 * Without a code the failure is a 500, keeping reason and admin hint.
	 *
	 * @return void
	 */
	public function testDefaultCodeIs500WithReasonAndHint(): void {
		$e = new Pdfa3ConversionException(
			reason: Pdfa3ConversionException::REASON_CONVERTER_UNAVAILABLE,
			message: 'Conversion unavailable.',
			adminHint: 'Install the converter.'
		);

		$this->assertSame(expected: 'converter_unavailable', actual: $e->getReason());
		$this->assertSame(expected: 'Conversion unavailable.', actual: $e->getMessage());
		$this->assertSame(expected: 'Install the converter.', actual: $e->getAdminHint());
		$this->assertSame(expected: 500, actual: $e->getCode());
		$this->assertNull(actual: $e->getPrevious());

	}//end testDefaultCodeIs500WithReasonAndHint()

	/**
	 * An explicit code and cause are passed to the parent.
	 *
	 * @return void
	 */
	public function testExplicitCodeAndCauseAreKept(): void {
		$cause = new RuntimeException(message: 'too big');
		$e	 = new Pdfa3ConversionException(
			reason: Pdfa3ConversionException::REASON_SOURCE_TOO_LARGE,
			message: 'Source too large.',
			adminHint: 'Raise the size limit.',
			code: 413,
			previous: $cause
		);

		$this->assertSame(expected: 'source_too_large', actual: $e->getReason());
		$this->assertSame(expected: 413, actual: $e->getCode());
		$this->assertSame(expected: $cause, actual: $e->getPrevious());

	}//end testExplicitCodeAndCauseAreKept()
}//end class
