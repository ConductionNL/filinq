<?php

/**
 * Unit tests for SigningRetentionCheck.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Service\Signing\SigningRetentionCheck;
use PHPUnit\Framework\TestCase;

/**
 * The signing register's retention against the Archiefwet 1995 minimum.
 *
 * @covers \OCA\Filinq\Service\Signing\SigningRetentionCheck
 */
class SigningRetentionCheckTest extends TestCase {

	/**
	 * The shipped P10Y annotation passes and reports where it came from.
	 *
	 * @return void
	 */
	public function testTenYearAnnotationPasses(): void {
		$result = (new SigningRetentionCheck())->evaluate(
			schemaSlug: 'signingRequest',
			archive: [],
			configuration: ['x-openregister-archival' => ['retention' => ['default' => 'P10Y']]]
		);

		self::assertTrue($result['passed']);
		self::assertSame(3652, $result['days']);
		self::assertSame('P10Y', $result['duration']);
		self::assertSame('schema.x-openregister-archival.retention.default', $result['source']);
	}//end testTenYearAnnotationPasses()

	/**
	 * A retention below 3650 days fails, naming the signing register and Archiefwet 1995.
	 *
	 * @return void
	 */
	public function testShortRetentionFailsNamingTheRegisterAndTheLaw(): void {
		$result = (new SigningRetentionCheck())->evaluate(
			schemaSlug: 'signingRequest',
			archive: [],
			configuration: ['x-openregister-archival' => ['retention' => ['default' => 'P5Y']]]
		);

		self::assertFalse($result['passed']);
		self::assertSame(1826, $result['days']);
		self::assertStringContainsString('signing register', $result['message']);
		self::assertStringContainsString('Archiefwet 1995', $result['message']);
		self::assertStringContainsString('3650', $result['message']);
	}//end testShortRetentionFailsNamingTheRegisterAndTheLaw()

	/**
	 * The boundary: 3649 days fails, 3650 days passes.
	 *
	 * @return void
	 */
	public function testBoundaryIsInclusiveAtThreeThousandSixHundredFiftyDays(): void {
		$check = new SigningRetentionCheck();

		self::assertFalse($check->evaluate(schemaSlug: 's', archive: ['defaultBewaartermijn' => 'P3649D'], configuration: null)['passed']);
		self::assertTrue($check->evaluate(schemaSlug: 's', archive: ['defaultBewaartermijn' => 'P3650D'], configuration: null)['passed']);
	}//end testBoundaryIsInclusiveAtThreeThousandSixHundredFiftyDays()

	/**
	 * The archive override wins over a compliant annotation, in OpenRegister's order.
	 *
	 * @return void
	 */
	public function testArchiveOverrideTakesPrecedenceOverTheAnnotation(): void {
		$result = (new SigningRetentionCheck())->evaluate(
			schemaSlug: 'signingRequest',
			archive: ['bewaartermijnOverride' => 'P1Y', 'defaultBewaartermijn' => 'P20Y'],
			configuration: ['x-openregister-archival' => ['retention' => ['default' => 'P10Y']]]
		);

		self::assertFalse($result['passed']);
		self::assertSame('schema.archive.bewaartermijnOverride', $result['source']);
		self::assertSame('P1Y', $result['duration']);
	}//end testArchiveOverrideTakesPrecedenceOverTheAnnotation()

	/**
	 * An unparseable duration is skipped and the next candidate decides.
	 *
	 * @return void
	 */
	public function testUnparseableDurationFallsThroughToTheNextCandidate(): void {
		$result = (new SigningRetentionCheck())->evaluate(
			schemaSlug: 'signingRequest',
			archive: ['bewaartermijnOverride' => 'ten years', 'defaultBewaartermijn' => 'P2Y'],
			configuration: null
		);

		self::assertFalse($result['passed']);
		self::assertSame('schema.archive.defaultBewaartermijn', $result['source']);
	}//end testUnparseableDurationFallsThroughToTheNextCandidate()

	/**
	 * No duration anywhere means indefinite retention, which passes.
	 *
	 * @return void
	 */
	public function testNoRetentionMeansIndefiniteAndPasses(): void {
		$result = (new SigningRetentionCheck())->evaluate(schemaSlug: 'signerRecord', archive: [], configuration: []);

		self::assertTrue($result['passed']);
		self::assertSame(SigningRetentionCheck::SOURCE_INDEFINITE, $result['source']);
		self::assertNull($result['days']);
	}//end testNoRetentionMeansIndefiniteAndPasses()
}//end class
