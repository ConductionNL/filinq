<?php

/**
 * Waarmerk CMS
 *
 * Detached CMS (PKCS#7) signatures over a sealed artifact's hash.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Waarmerk
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Waarmerk;

use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Kernel\Contracts\WaarmerkCmsInterface;

/**
 * Signs and verifies the sha256 of a sealed artifact as detached CMS.
 *
 * The signed content is the lowercase hex sha256; the CMS carries the
 * signer certificate, whose fingerprint verification compares with the one
 * recorded on the seal.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Waarmerk
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
 */
class WaarmerkCms implements WaarmerkCmsInterface {

	private const SIGN_FLAGS = (OPENSSL_CMS_DETACHED | OPENSSL_CMS_BINARY);

	/**
	 * Chain trust is not asserted (no CA is configured); the signature itself and the recorded fingerprint are checked.
	 */
	private const VERIFY_FLAGS = (OPENSSL_CMS_DETACHED | OPENSSL_CMS_BINARY | OPENSSL_CMS_NOVERIFY);

	/**
	 * Sign a hash.
	 *
	 * @param string $hash The lowercase hex sha256.
	 * @param string $certificatePem The organisation certificate.
	 * @param string $keyPem The private key, in memory only.
	 *
	 * @return string The detached CMS, base64 DER.
	 *
	 * @throws WaarmerkRefusedException 503 when OpenSSL cannot sign.
	 *
	 * @SuppressWarnings(PHPMD.ErrorControlOperator) OpenSSL reports failure through the return value,
	 * which is checked; the PHP warning it would also raise adds nothing, and a temp-file unlink is
	 * best-effort cleanup.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	public function sign(string $hash, string $certificatePem, string $keyPem): string {
		$content = $this->tempFile(content: $hash);
		$output = $this->tempFile(content: '');
		try {
			$signed = @openssl_cms_sign($content, $output, $certificatePem, $keyPem, null, self::SIGN_FLAGS, OPENSSL_ENCODING_DER);
			$der = (string) file_get_contents($output);
		} finally {
			$this->drainErrors();
			@unlink($content);
			@unlink($output);
		}

		if ($signed !== true || $der === '') {
			throw new WaarmerkRefusedException(message: 'The waarmerk signature could not be made', status: 503);
		}

		return base64_encode($der);

	}//end sign()

	/**
	 * The signer of a detached CMS over a hash, when it is valid and by the recorded certificate.
	 *
	 * Fail-closed: any OpenSSL error, a missing signer or another signer
	 * certificate answers null.
	 *
	 * @param string $hash The lowercase hex sha256 the seal recorded.
	 * @param string $cmsBase64 The detached CMS, base64 DER.
	 * @param string $fingerprint The recorded certificate fingerprint (colon hex).
	 *
	 * @return string|null The signer certificate (PEM) when the signature is valid, else null.
	 *
	 * @SuppressWarnings(PHPMD.ErrorControlOperator) OpenSSL reports failure through the return value,
	 * which is checked; the PHP warning it would also raise adds nothing, and a temp-file unlink is
	 * best-effort cleanup.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-waarmerk-is-publicly-verifiable-fail-closed-req-ddwmk-004
	 */
	public function verify(string $hash, string $cmsBase64, string $fingerprint): ?string {
		$der = base64_decode($cmsBase64, true);
		if ($der === false || $der === '' || $hash === '') {
			return null;
		}

		$content = $this->tempFile(content: $hash);
		$signature = $this->tempFile(content: $der);
		$signers = $this->tempFile(content: '');
		try {
			$valid = @openssl_cms_verify($content, self::VERIFY_FLAGS, $signers, [], null, null, null, $signature, OPENSSL_ENCODING_DER);
			$signerPem = (string) file_get_contents($signers);
		} finally {
			$this->drainErrors();
			@unlink($content);
			@unlink($signature);
			@unlink($signers);
		}

		if ($valid !== true || $signerPem === '') {
			return null;
		}

		$signer = @openssl_x509_fingerprint($signerPem, 'sha256');
		if (is_string($signer) === false) {
			return null;
		}

		if (hash_equals(strtoupper(str_replace(':', '', $fingerprint)), strtoupper($signer)) === false) {
			return null;
		}

		return $signerPem;

	}//end verify()

	/**
	 * A temporary file holding some bytes.
	 *
	 * @param string $content The bytes.
	 *
	 * @return string The path.
	 */
	private function tempFile(string $content): string {
		$path = tempnam(sys_get_temp_dir(), 'filinq-waarmerk-');
		if ($path === false) {
			throw new WaarmerkRefusedException(message: 'No temporary file for the waarmerk signature', status: 503);
		}

		file_put_contents($path, $content);
		return $path;

	}//end tempFile()

	/**
	 * Empty OpenSSL's error queue so a later call does not read a stale error.
	 *
	 * @return void
	 */
	private function drainErrors(): void {
		while (openssl_error_string() !== false) {
			continue;
		}

	}//end drainErrors()
}//end class
