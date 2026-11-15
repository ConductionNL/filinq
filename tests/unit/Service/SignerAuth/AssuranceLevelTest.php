<?php

/**
 * Unit tests for AssuranceLevel
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SignerAuth;

use DateTimeImmutable;
use InvalidArgumentException;
use OCA\Filinq\Service\SignerAuth\AssuranceLevel;
use OCA\Filinq\Service\SignerAuth\IdentityEvidence;
use PHPUnit\Framework\TestCase;

/**
 * REQ-DDSIR-002: the scale, the floors, and a request that cannot undercut its floor.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AssuranceLevelTest extends TestCase {

	/**
	 * SES, AdES and QES carry the floors low, substantial and high.
	 *
	 * @return void
	 */
	public function testEachSignatureLevelHasItsFloor(): void {
		$this->assertSame('low', (new AssuranceLevel())->floorFor(signatureLevel: 'SES'));
		$this->assertSame('substantial', (new AssuranceLevel())->floorFor(signatureLevel: 'AdES'));
		$this->assertSame('high', (new AssuranceLevel())->floorFor(signatureLevel: 'QES'));
		$this->assertSame('low', (new AssuranceLevel())->floorFor(signatureLevel: 'unknown'));

	}//end testEachSignatureLevelHasItsFloor()

	/**
	 * A QES request that asks for low is held at high; an SES request may ask for more.
	 *
	 * @return void
	 */
	public function testARequestCannotUndercutItsFloor(): void {
		$this->assertSame('high', (new AssuranceLevel())->requiredFor(request: ['signatureLevel' => 'QES', 'requiredAssurance' => 'low']));
		$this->assertSame('substantial', (new AssuranceLevel())->requiredFor(request: ['signatureLevel' => 'SES', 'requiredAssurance' => 'substantial']));
		$this->assertSame('substantial', (new AssuranceLevel())->requiredFor(request: ['signatureLevel' => 'AdES']));
		$this->assertSame('low', (new AssuranceLevel())->requiredFor(request: []), 'A request from before the rails reads as low');
		$this->assertSame('low', (new AssuranceLevel())->requiredFor(request: ['requiredAssurance' => 'extreme']));

	}//end testARequestCannotUndercutItsFloor()

	/**
	 * Anything undeclared degrades to low, and comparisons follow the scale.
	 *
	 * @return void
	 */
	public function testUndeclaredLevelsDegradeToLow(): void {
		$this->assertSame('low', (new AssuranceLevel())->normalise(value: 'HIGH'));
		$this->assertSame('low', (new AssuranceLevel())->normalise(value: null));
		$this->assertTrue((new AssuranceLevel())->meets(held: 'high', required: 'substantial'));
		$this->assertFalse((new AssuranceLevel())->meets(held: 'low', required: 'substantial'));
		$this->assertFalse((new AssuranceLevel())->meets(held: 'bogus', required: 'substantial'));
		$this->assertSame('high', (new AssuranceLevel())->strongest(levels: ['low', 'high', 'substantial']));
		$this->assertSame('low', (new AssuranceLevel())->weakest(levels: ['high', 'low']));
		$this->assertSame('low', (new AssuranceLevel())->weakest(levels: []));

	}//end testUndeclaredLevelsDegradeToLow()

	/**
	 * Evidence round-trips through its stored form and knows when it is stale.
	 *
	 * @return void
	 */
	public function testEvidenceRoundTripsAndAges(): void {
		$at = new DateTimeImmutable('2026-09-28T10:00:00+00:00');
		$evidence = new IdentityEvidence(
			provider: 'oidc-broker',
			means: 'digid',
			assurance: 'nonsense',
			subjectPseudonym: 'ps',
			authenticatedAt: $at,
			evidenceHash: str_repeat('a', 64)
		);

		$this->assertSame('low', $evidence->assurance);
		$this->assertEquals($evidence->toArray(), IdentityEvidence::fromArray(data: $evidence->toArray())->toArray());
		$this->assertTrue($evidence->isFreshAt(now: $at->modify('+15 minutes'), maxAgeSeconds: 900));
		$this->assertFalse($evidence->isFreshAt(now: $at->modify('+16 minutes'), maxAgeSeconds: 900));
		$this->assertFalse($evidence->isFreshAt(now: $at->modify('-5 minutes'), maxAgeSeconds: 900));

		$this->expectException(InvalidArgumentException::class);
		IdentityEvidence::fromArray(data: ['provider' => '']);

	}//end testEvidenceRoundTripsAndAges()
}//end class
