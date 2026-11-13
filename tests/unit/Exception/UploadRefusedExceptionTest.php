<?php

/**
 * Unit tests for UploadRefusedException
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

use OCA\Filinq\Exception\UploadRefusedException;
use RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UploadRefusedException: the 400 code contract and the refusal context.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[CoversClass(UploadRefusedException::class)]
class UploadRefusedExceptionTest extends TestCase {

	/**
	 * The refusal always carries code 400, with empty context by default.
	 *
	 * @return void
	 */
	public function testRefusalAlwaysCarries400SoControllersAnswerClientError(): void {
		$e = new UploadRefusedException(message: 'File type not allowed.');

		$this->assertSame(expected: 'File type not allowed.', actual: $e->getMessage());
		$this->assertSame(expected: 400, actual: $e->getCode());
		$this->assertSame(expected: '', actual: $e->getDetectedType());
		$this->assertSame(expected: '', actual: $e->getPolicy());
		$this->assertNull(actual: $e->getPrevious());

	}//end testRefusalAlwaysCarries400SoControllersAnswerClientError()

	/**
	 * The detected type, policy and cause are kept as given.
	 *
	 * @return void
	 */
	public function testRefusalKeepsDetectedTypePolicyAndCause(): void {
		$cause = new RuntimeException(message: 'magic bytes mismatch');
		$e	 = new UploadRefusedException(message: 'Refused.', detectedType: 'application/x-msdownload', policy: 'archive-safe', previous: $cause);

		$this->assertSame(expected: 400, actual: $e->getCode());
		$this->assertSame(expected: 'application/x-msdownload', actual: $e->getDetectedType());
		$this->assertSame(expected: 'archive-safe', actual: $e->getPolicy());
		$this->assertSame(expected: $cause, actual: $e->getPrevious());

	}//end testRefusalKeepsDetectedTypePolicyAndCause()
}//end class
