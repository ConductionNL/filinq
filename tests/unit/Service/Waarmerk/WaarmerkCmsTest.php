<?php

/**
 * Unit tests for WaarmerkCms.
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

use OCA\Filinq\Service\Waarmerk\WaarmerkCms;
use PHPUnit\Framework\TestCase;

/**
 * Detached CMS sign/verify round trip with a runtime-generated certificate.
 *
 * @covers \OCA\Filinq\Service\Waarmerk\WaarmerkCms
 */
class WaarmerkCmsTest extends TestCase {

	/**
	 * The recorded fingerprint of a certificate.
	 *
	 * @param string $pem The certificate.
	 *
	 * @return string
	 */
	private function fingerprint(string $pem): string {
		return implode(':', str_split(strtoupper(openssl_x509_fingerprint($pem, 'sha256')), 2));
	}//end fingerprint()

	/**
	 * A signature verifies against its own hash and certificate, and names the signer.
	 *
	 * @return void
	 */
	public function testRoundTrip(): void {
		$pair = WaarmerkKeyPair::make();
		$cms = new WaarmerkCms();
		$hash = hash('sha256', 'sealed bytes');

		$signature = $cms->sign(hash: $hash, certificatePem: $pair['cert'], keyPem: $pair['key']);

		$this->assertNotFalse(base64_decode($signature, true));
		$signer = $cms->verify(hash: $hash, cmsBase64: $signature, fingerprint: $this->fingerprint($pair['cert']));
		$this->assertNotNull($signer);
		$this->assertSame(openssl_x509_fingerprint($pair['cert'], 'sha256'), openssl_x509_fingerprint($signer, 'sha256'));
	}//end testRoundTrip()

	/**
	 * One flipped hash character, another certificate, a stripped or broken CMS: never verified.
	 *
	 * @return void
	 */
	public function testFailsClosed(): void {
		$pair = WaarmerkKeyPair::make();
		$other = WaarmerkKeyPair::make(organisation: 'Gemeente Elders');
		$cms = new WaarmerkCms();
		$hash = hash('sha256', 'sealed bytes');
		$signature = $cms->sign(hash: $hash, certificatePem: $pair['cert'], keyPem: $pair['key']);
		$fingerprint = $this->fingerprint($pair['cert']);

		$flipped = (($hash[0] === 'a') ? 'b' : 'a') . substr($hash, 1);
		$this->assertNull($cms->verify(hash: $flipped, cmsBase64: $signature, fingerprint: $fingerprint));
		$this->assertNull($cms->verify(hash: $hash, cmsBase64: $signature, fingerprint: $this->fingerprint($other['cert'])));
		$this->assertNull($cms->verify(hash: $hash, cmsBase64: '', fingerprint: $fingerprint));
		$this->assertNull($cms->verify(hash: $hash, cmsBase64: 'not base64 !!', fingerprint: $fingerprint));
		$this->assertNull($cms->verify(hash: $hash, cmsBase64: base64_encode('garbage'), fingerprint: $fingerprint));
		$this->assertNull($cms->verify(hash: '', cmsBase64: $signature, fingerprint: $fingerprint));

		// A signature by another key over the same hash does not pass for this certificate.
		$forged = $cms->sign(hash: $hash, certificatePem: $other['cert'], keyPem: $other['key']);
		$this->assertNull($cms->verify(hash: $hash, cmsBase64: $forged, fingerprint: $fingerprint));
	}//end testFailsClosed()
}//end class
