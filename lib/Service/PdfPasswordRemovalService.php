<?php

/**
 * Filinq PdfPasswordRemovalService
 *
 * Unlocks one password-protected PDF into a copy beside it, `<name> (unlocked).pdf`,
 * and checks the copy at once (intake-pdf-password-removal D1–D4, D6). The
 * original stays as it arrived. The password lives for this one call: it goes
 * to qpdf on standard input and into no log, exception, audit entry or object.
 * A final document is refused, and a server without qpdf says so.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#1-service-and-route
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\Files\File;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Remove the password of a PDF into a checked copy.
 *
 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#1-service-and-route
 */
class PdfPasswordRemovalService {

	/**
	 * The copy was written.
	 */
	public const UNLOCKED = 'unlocked';

	/**
	 * The password is not correct (422).
	 */
	public const WRONG_PASSWORD = 'wrong-password';

	/**
	 * The document is final (409).
	 */
	public const FINAL = 'final';

	/**
	 * qpdf is not installed (503).
	 */
	public const NO_TOOL = 'no-tool';

	/**
	 * The file is not a PDF, or could not be read or written (400 / 500).
	 */
	public const FAILED = 'failed';

	/**
	 * Constructor.
	 *
	 * @param FinalDocumentService      $finalDocuments Tells whether a document is final, failing closed.
	 * @param DocumentValidationService $validation     Checks the copy.
	 * @param LoggerInterface           $logger         Records a failure, never the password.
	 * @param QpdfDecryptor             $qpdf           The decryption tool.
	 */
	public function __construct(
		private readonly FinalDocumentService $finalDocuments,
		private readonly DocumentValidationService $validation,
		private readonly LoggerInterface $logger,
		private readonly QpdfDecryptor $qpdf = new QpdfDecryptor(),
	) {
	}//end __construct()

	/**
	 * Whether qpdf is installed, for the admin settings page.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#1-service-and-route
	 */
	public function isAvailable(): bool {
		return $this->qpdf->isAvailable();
	}//end isAvailable()

	/**
	 * Unlock a PDF the caller may read into a copy beside it, and check the copy.
	 *
	 * @param File   $file     The locked PDF, already proven readable by the caller.
	 * @param string $password The password the clerk typed.
	 *
	 * @return array<string, mixed> `outcome` (one of the constants), with `message` on a refusal, or the
	 *                              copy's `fileId`, `name`, `validationStatus` and `validationFindings`.
	 *
	 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#1-service-and-route
	 */
	public function unlock(File $file, string $password): array {
		$refusal = $this->finalDocuments->refusalFor(fileId: (int)$file->getId(), action: 'remove the password of this document');
		if ($refusal !== null) {
			return ['outcome' => self::FINAL, 'message' => $refusal];
		}

		if ($this->qpdf->isAvailable() === false) {
			return ['outcome' => self::NO_TOOL];
		}

		try {
			$bytes = (string)$file->getContent();
			if (str_starts_with($bytes, '%PDF') === false) {
				return ['outcome' => self::FAILED, 'message' => 'This file is not a PDF.'];
			}

			$unlocked = $this->qpdf->decrypt(pdf: $bytes, password: $password);
			if ($unlocked === null) {
				return ['outcome' => self::WRONG_PASSWORD];
			}

			$copy = $this->writeCopy(original: $file, bytes: $unlocked);
		} catch (Throwable $e) {
			// The exception never carries the password (QpdfDecryptor); the
			// context names the file and the class only.
			$this->logger->warning(
				'Filinq: a PDF password could not be removed',
				['fileId' => $file->getId(), 'exception' => $e::class]
			);
			return ['outcome' => self::FAILED, 'message' => 'The password could not be removed from this PDF.'];
		}

		return ['outcome' => self::UNLOCKED, 'fileId' => (int)$copy->getId(), 'name' => $copy->getName()]
			+ $this->validation->validate(file: $copy);
	}//end unlock()

	/**
	 * The name of the copy: `<name> (unlocked).pdf`.
	 *
	 * @param string $name The original's file name.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#1-service-and-route
	 */
	public static function copyName(string $name): string {
		$base = (string)preg_replace('/\.pdf$/i', '', $name);
		if ($base === '') {
			$base = 'document';
		}

		return $base . ' (unlocked).pdf';
	}//end copyName()

	/**
	 * Write the unlocked bytes beside the original, never over it.
	 *
	 * @param File   $original The locked original.
	 * @param string $bytes    The unlocked PDF.
	 *
	 * @return File The copy.
	 */
	private function writeCopy(File $original, string $bytes): File {
		// The parent of a file is its folder.
		$folder = $original->getParent();
		$name   = $folder->getNonExistingName(self::copyName(name: $original->getName()));

		return $folder->newFile($name, $bytes);
	}//end writeCopy()
}//end class
