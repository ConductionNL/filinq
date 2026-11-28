<?php

/**
 * Unit tests for WaarmerkCertificate.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Waarmerk
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Waarmerk;

use DateTimeImmutable;
use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Service\Waarmerk\WaarmerkCertificate;
use PHPUnit\Framework\TestCase;

/**
 * Parsing the public organisation certificate.
 *
 * @covers \OCA\Filinq\Service\Waarmerk\WaarmerkCertificate
 * @uses   \OCA\Filinq\Exception\WaarmerkRefusedException
 */
class WaarmerkCertificateTest extends TestCase {

	/**
	 * Subject, organisation, fingerprint and validity are read from the PEM.
	 *
	 * @return void
	 */
	public function testParsesThePublicFacts(): void {
		$pair = WaarmerkKeyPair::make();
		$certificate = WaarmerkCertificate::fromPem(pem: $pair['cert']);

		$this->assertSame('Gemeente Demostad', $certificate->organisation);
		$this->assertStringContainsString('O=Gemeente Demostad', $certificate->subject);
		$this->assertStringContainsString('C=NL', $certificate->subject);
		$expected = implode(':', str_split(strtoupper(openssl_x509_fingerprint($pair['cert'], 'sha256')), 2));
		$this->assertSame($expected, $certificate->fingerprint);
		$this->assertGreaterThan($certificate->validFrom, $certificate->validTo);

		$summary = $certificate->summary(now: new DateTimeImmutable());
		$this->assertFalse($summary['expired']);
		$this->assertSame($expected, $summary['fingerprint']);
		$this->assertArrayNotHasKey('pem', $summary);
	}//end testParsesThePublicFacts()

	/**
	 * After validTo, and before validFrom, the certificate counts as expired.
	 *
	 * @return void
	 */
	public function testExpiry(): void {
		$certificate = WaarmerkCertificate::fromPem(pem: WaarmerkKeyPair::make()['cert']);

		$this->assertFalse($certificate->isExpiredAt(at: new DateTimeImmutable('@' . ($certificate->validTo - 10))));
		$this->assertTrue($certificate->isExpiredAt(at: new DateTimeImmutable('@' . ($certificate->validTo + 1))));
		$this->assertTrue($certificate->isExpiredAt(at: new DateTimeImmutable('@' . ($certificate->validFrom - 1))));
	}//end testExpiry()

	/**
	 * A pasted private key is refused, never parsed or stored.
	 *
	 * @return void
	 */
	public function testAPrivateKeyIsRefused(): void {
		$pair = WaarmerkKeyPair::make();
		$this->expectException(WaarmerkRefusedException::class);
		$this->expectExceptionMessage('must not contain a private key');
		WaarmerkCertificate::fromPem(pem: $pair['cert'] . $pair['key']);
	}//end testAPrivateKeyIsRefused()

	/**
	 * Text that is no certificate is refused with 400.
	 *
	 * @return void
	 */
	public function testGarbageIsRefused(): void {
		try {
			WaarmerkCertificate::fromPem(pem: "-----BEGIN CERTIFICATE-----\nnope\n-----END CERTIFICATE-----");
			$this->fail('expected a refusal');
		} catch (WaarmerkRefusedException $e) {
			$this->assertSame(400, $e->getStatus());
		}
	}//end testGarbageIsRefused()
}//end class
