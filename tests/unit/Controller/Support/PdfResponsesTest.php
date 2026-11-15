<?php

/**
 * Unit tests for PdfResponses.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller\Support
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller\Support;


use OCA\Filinq\Controller\Support\PdfResponses;
use OCA\Filinq\Exception\Pdfa3ConversionException;
use PHPUnit\Framework\TestCase;

/**
 * The PDF/A-3 download headers, the typed refusal and the status clamp.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Controller\Support\PdfResponses
 * @uses   \OCA\Filinq\Exception\Pdfa3ConversionException
 */
class PdfResponsesTest extends TestCase {

	/**
	 * The download carries the composition headers.
	 *
	 * @return void
	 */
	public function testTheDownloadCarriesTheCompositionHeaders(): void {
		$response = (new PdfResponses())->pdfa3Download(
			result: ['content' => '%PDF-1.7', 'checksumSha256' => 'abc123', 'pages' => 3, 'conformance' => 'PDF/A-3b', 'verified' => true],
			filename: 'besluit.pdf'
		);

		$this->assertSame(200, $response->getStatus());
		$headers = $response->getHeaders();
		$this->assertSame('abc123', $headers['X-Docudesk-Pdfa3-Checksum-Sha256']);
		$this->assertSame('3', $headers['X-Docudesk-Pdfa3-Pages']);
		$this->assertSame('PDF/A-3b', $headers['X-Docudesk-Pdfa3-Conformance']);
		$this->assertSame('1', $headers['X-Docudesk-Pdfa3-Verified']);

	}//end testTheDownloadCarriesTheCompositionHeaders()

	/**
	 * Without a verification outcome the header says skipped.
	 *
	 * @return void
	 */
	public function testAnUnverifiedDownloadSaysSkipped(): void {
		$response = (new PdfResponses())->pdfa3Download(
			result: ['content' => 'x', 'checksumSha256' => 'c', 'pages' => 1, 'conformance' => 'PDF/A-3b'],
			filename: 'a.pdf'
		);

		$this->assertSame('skipped', $response->getHeaders()['X-Docudesk-Pdfa3-Verified']);

	}//end testAnUnverifiedDownloadSaysSkipped()

	/**
	 * A refusal answers its message, reason and admin hint under its code.
	 *
	 * @return void
	 */
	public function testARefusalCarriesItsReasonAndHint(): void {
		$refusal = new Pdfa3ConversionException(
			reason: Pdfa3ConversionException::REASON_SOURCE_TOO_LARGE,
			message: 'Source too large',
			adminHint: 'Raise the size cap',
			code: 413
		);

		$response = (new PdfResponses())->pdfa3Refusal(exception: $refusal);

		$this->assertSame(413, $response->getStatus());
		$this->assertSame(
			['error' => 'Source too large', 'reason' => 'source_too_large', 'adminHint' => 'Raise the size cap'],
			$response->getData()
		);

	}//end testARefusalCarriesItsReasonAndHint()

	/**
	 * A refusal with a non-HTTP code answers 500.
	 *
	 * @return void
	 */
	public function testARefusalOutsideTheHttpRangeIsA500(): void {
		$refusal = new Pdfa3ConversionException(reason: 'render_failed', message: 'm', adminHint: 'h', code: 7);

		$this->assertSame(500, (new PdfResponses())->pdfa3Refusal(exception: $refusal)->getStatus());

	}//end testARefusalOutsideTheHttpRangeIsA500()

	/**
	 * Codes in 400-599 pass, everything else becomes 500.
	 *
	 * @return void
	 */
	public function testClampStatusCode(): void {
		$responses = new PdfResponses();

		foreach ([-1 => 500, 0 => 500, 302 => 500, 399 => 500, 400 => 400, 504 => 504, 599 => 599, 600 => 500] as $code => $status) {
			$this->assertSame($status, $responses->clampStatusCode(code: $code), 'code '.$code);
		}

	}//end testClampStatusCode()
}//end class
