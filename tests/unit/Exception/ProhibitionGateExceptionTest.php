<?php

/**
 * Unit tests for ProhibitionGateException
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

use OCA\Filinq\Exception\ProhibitionGateException;
use RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ProhibitionGateException: its defaults and the gate findings it carries.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[CoversClass(ProhibitionGateException::class)]
class ProhibitionGateExceptionTest extends TestCase {

	/**
	 * Without arguments the gate refusal carries its default message and no findings.
	 *
	 * @return void
	 */
	public function testDefaultsCarryNoFindings(): void {
		$e = new ProhibitionGateException();

		$this->assertSame(expected: 'Anonymisation prohibition gate blocked the request.', actual: $e->getMessage());
		$this->assertSame(expected: 0, actual: $e->getCode());
		$this->assertSame(expected: [], actual: $e->getMissingProhibitionMatches());
		$this->assertSame(expected: [], actual: $e->getRejectedOverrides());
		$this->assertNull(actual: $e->getBackendUnavailable());

	}//end testDefaultsCarryNoFindings()

	/**
	 * The findings, message, code and cause are kept as given.
	 *
	 * @return void
	 */
	public function testFindingsMessageCodeAndCauseAreKept(): void {
		$cause = new RuntimeException(message: 'detector down');
		$e	 = new ProhibitionGateException(
			missingMatches: [['term' => 'BSN']],
			rejectedOverrides: [['uuid' => 'o-1']],
			backendUnavailable: 'presidio',
			message: 'Blocked.',
			code: 409,
			previous: $cause
		);

		$this->assertSame(expected: [['term' => 'BSN']], actual: $e->getMissingProhibitionMatches());
		$this->assertSame(expected: [['uuid' => 'o-1']], actual: $e->getRejectedOverrides());
		$this->assertSame(expected: 'presidio', actual: $e->getBackendUnavailable());
		$this->assertSame(expected: 'Blocked.', actual: $e->getMessage());
		$this->assertSame(expected: 409, actual: $e->getCode());
		$this->assertSame(expected: $cause, actual: $e->getPrevious());

	}//end testFindingsMessageCodeAndCauseAreKept()
}//end class
