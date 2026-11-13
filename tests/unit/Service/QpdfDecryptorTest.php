<?php

/**
 * Unit tests for QpdfDecryptor against the qpdf binary, with a PDF locked at test time.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#1-service-and-route
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\QpdfDecryptor;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\QpdfDecryptor
 */
class QpdfDecryptorTest extends TestCase {

	/**
	 * Without the binary the tool is unavailable and decrypting throws, without the password.
	 *
	 * @return void
	 */
	public function testAMissingBinaryIsUnavailableAndRefuses(): void {
		$qpdf = new QpdfDecryptor('/nonexistent/qpdf-' . bin2hex(random_bytes(4)));

		$this->assertFalse($qpdf->isAvailable());
		$this->assertNull($qpdf->version());
		try {
			$qpdf->decrypt('%PDF-1.4', 'geheim-wachtwoord');
			$this->fail('decrypting without qpdf must throw');
		} catch (RuntimeException $e) {
			$this->assertStringNotContainsString('geheim-wachtwoord', $e->getMessage());
		}
	}//end testAMissingBinaryIsUnavailableAndRefuses()

	/**
	 * With qpdf installed: the right password unlocks, a wrong one answers null.
	 * On a host without qpdf this asserts the tool says it is absent instead.
	 *
	 * @return void
	 */
	public function testTheRightPasswordUnlocksAndAWrongOneDoesNot(): void {
		$qpdf = new QpdfDecryptor();
		$locked = $this->lockedPdf(password: 'juist-wachtwoord');
		if ($locked === null) {
			$this->assertFalse($qpdf->isAvailable(), 'no locked fixture could be made, so qpdf is absent');
			return;
		}

		$this->assertTrue($qpdf->isAvailable());
		$this->assertStringStartsWith('qpdf', strtolower((string)$qpdf->version()));
		$this->assertStringContainsString('/Encrypt', $locked);

		$unlocked = $qpdf->decrypt($locked, 'juist-wachtwoord');
		$this->assertIsString($unlocked);
		$this->assertStringStartsWith('%PDF', $unlocked);
		$this->assertStringNotContainsString('/Encrypt', $unlocked);

		$this->assertNull($qpdf->decrypt($locked, 'fout-wachtwoord'));
	}//end testTheRightPasswordUnlocksAndAWrongOneDoesNot()

	/**
	 * A PDF with a user password, made with qpdf at test time; null without qpdf.
	 *
	 * @param string $password The user password.
	 *
	 * @return string|null The locked PDF.
	 */
	private function lockedPdf(string $password): ?string {
		$dir = sys_get_temp_dir() . '/filinq-qpdf-test-' . bin2hex(random_bytes(4));
		@mkdir($dir);
		$plain = $dir . '/plain.pdf';
		$locked = $dir . '/locked.pdf';
		exec('qpdf --empty ' . escapeshellarg($plain) . ' 2>/dev/null', $out, $code);
		if ($code !== 0) {
			return null;
		}

		exec('qpdf --encrypt ' . escapeshellarg($password) . ' owner-pw 256 -- ' . escapeshellarg($plain) . ' ' . escapeshellarg($locked) . ' 2>/dev/null', $out, $code);
		$bytes = ($code === 0 ? (string)file_get_contents($locked) : null);
		@unlink($plain);
		@unlink($locked);
		@rmdir($dir);
		return $bytes;
	}//end lockedPdf()
}//end class
