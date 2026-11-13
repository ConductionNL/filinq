<?php

/**
 * Unit tests for SigningCancellationNotSupportedException
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

use OCA\Filinq\Exception\SigningCancellationNotSupportedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SigningCancellationNotSupportedException: the composed refusal message with and without a reason.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[CoversClass(SigningCancellationNotSupportedException::class)]
class SigningCancellationNotSupportedExceptionTest extends TestCase {

	/**
	 * Without a reason the message names the provider and ends after the instruction.
	 *
	 * @return void
	 */
	public function testMessageNamesProviderWithoutTrailingReason(): void {
		$e = new SigningCancellationNotSupportedException(provider: 'ValidSign');

		$this->assertSame(
			expected: 'The "ValidSign" signing provider cannot withdraw a signing request. '
				. 'The request is still live and its signatories can still sign. '
				. 'Withdraw it directly with the provider.',
			actual: $e->getMessage()
		);
		$this->assertSame(expected: 0, actual: $e->getCode());

	}//end testMessageNamesProviderWithoutTrailingReason()

	/**
	 * A non-empty reason is appended after one space.
	 *
	 * @return void
	 */
	public function testReasonIsAppendedAfterASingleSpace(): void {
		$e = new SigningCancellationNotSupportedException(provider: 'Docusign', reason: 'The envelope API has no void call.');

		$this->assertStringStartsWith(prefix: 'The "Docusign" signing provider cannot withdraw', string: $e->getMessage());
		$this->assertStringEndsWith(
			suffix: 'Withdraw it directly with the provider. The envelope API has no void call.',
			string: $e->getMessage()
		);

	}//end testReasonIsAppendedAfterASingleSpace()
}//end class
