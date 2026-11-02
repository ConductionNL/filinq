<?php

/**
 * Unit tests for PortalSignatureAssurance
 *
 * A signature made through the portal records an assurance level that never
 * exceeds the portal session's trust and is never QES.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/portal-signing-surface/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Service\Signing\PortalSignatureAssurance;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the portal signature assurance cap.
 */
class PortalSignatureAssuranceTest extends TestCase {

	/**
	 * The cases: requested level, session trust, recorded level.
	 *
	 * @return array<string, array{0: string, 1: string, 2: string}>
	 */
	public static function cases(): array {
		return [
			'substantial session, SES request'       => ['SES', 'substantial', 'SES'],
			'substantial session, AES request'       => ['AES', 'substantial', 'AES'],
			'high session, AES request'              => ['AES', 'high', 'AES'],
			'low session, AES request is capped'     => ['AES', 'low', 'SES'],
			'QES request is never recorded as QES'   => ['QES', 'high', 'AES'],
			'QES request on a low session'           => ['QES', 'low', 'SES'],
			'unknown trust is the lowest'            => ['AES', 'totally-trusted', 'SES'],
			'unknown level is the lowest'            => ['XYZ', 'high', 'SES'],
		];

	}//end cases()

	/**
	 * The recorded level is the lower of the request and the session cap.
	 *
	 * @param string $requested The level the request asked for.
	 * @param string $trust     The verified portal session trust.
	 * @param string $expected  The level that must be recorded.
	 *
	 * @return void
	 */
	#[DataProvider('cases')]
	public function testLevelNeverExceedsTheSessionOrClaimsQes(string $requested, string $trust, string $expected): void {
		$level = (new PortalSignatureAssurance())->levelFor(requestedLevel: $requested, trust: $trust);

		$this->assertSame($expected, $level);
		$this->assertNotSame('QES', $level);

	}//end testLevelNeverExceedsTheSessionOrClaimsQes()
}//end class
