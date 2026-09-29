<?php

/**
 * PDF Conversion Service
 *
 * Implements the file-to-PDF conversion cascade specified by the
 * `anonymise-output-as-pdf-by-default` change. Backends in priority
 * order: Office app (Collabora/OnlyOffice) → LibreOffice headless →
 * PhpWord+mPDF → mPDF direct → EML extractor. First success wins;
 * total failure throws ConversionFailedException with per-backend
 * attempt records suitable for the documented HTTP 422 body.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
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

namespace OCA\Filinq\Service;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\ConversionBackendInterface;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCP\Files\File;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Walks a cascade of conversion backends, returning the first success
 * and aggregating failures.
 *
 * The backend list is injected (ordered); DI registers concrete
 * backends in design D2 priority order. The walk is:
 *
 *   for each backend in order:
 *     if not isAvailable() → record attempt {available: false} and continue
 *     if not canHandle()   → record attempt {supports: false} and continue
 *     try convert()
 *       success → return file
 *       failure → record attempt {available: true, supports: true, reason: <exception message>}
 *   if no success → throw ConversionFailedException(attempts)
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-conversion-routes-through-the-nextcloud-conversion-broker
 */
class PdfConversionService {
	/**
	 * Constructor.
	 *
	 * @param array<int, ConversionBackendInterface> $backends Ordered list of backends; first success wins.
	 * @param LoggerInterface $logger Logger for per-attempt diagnostics.
	 */
	public function __construct(
		private readonly array $backends,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Convert the source file to PDF via the backend cascade.
	 *
	 * @param File $source Source file (any supported input format).
	 * @param array<string, mixed> $opts Per-call options: `accessible` (tagged PDF/UA
	 *                                   through LibreOffice only), `pdfa` (keep PDF/A with it).
	 *
	 * @return File The newly written PDF file node.
	 *
	 * @throws ConversionFailedException When no backend in the cascade succeeded.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-conversion-routes-through-the-nextcloud-conversion-broker
	 * @spec openspec/changes/pdfua-accessible-output/tasks.md#task-1.1
	 */
	public function convertToPdf(File $source, array $opts = []): File {
		return $this->convertToPdfReporting(source: $source, opts: $opts)['file'];
	}//end convertToPdf()

	/**
	 * Convert to PDF and report WHICH backend claimed the conversion.
	 *
	 * Same cascade, same failure. The difference is that the caller learns
	 * whether an office app produced the PDF or the mPDF fallback did -- two
	 * results with visibly different fidelity that are otherwise
	 * indistinguishable to anyone reading a log after the fact.
	 *
	 * @param File $source Source file (any supported input format).
	 * @param array<string, mixed> $opts Per-call options, see {@see convertToPdf()}.
	 *
	 * @return array{file: File, backend: string} The PDF and the backend that produced it.
	 *
	 * @throws ConversionFailedException When no backend in the cascade succeeded.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-conversion-routes-through-the-nextcloud-conversion-broker
	 */
	public function convertToPdfReporting(File $source, array $opts = []): array {
		if (($opts['accessible'] ?? false) === true) {
			return $this->convertTagged(source: $source, pdfa: (($opts['pdfa'] ?? false) === true));
		}

		$mimeType = (string)$source->getMimeType();
		$ext = $this->extractExtension(name: $source->getName());

		$attempts = [];

		foreach ($this->backends as $backend) {
			if ($backend instanceof ConversionBackendInterface === false) {
				// Defensive — DI should only register interface
				// implementations; skip anything else without crashing
				// the cascade.
				continue;
			}

			$backendName = $backend->name();

			$available = $backend->isAvailable();
			if ($available === false) {
				$attempts[] = [
					'name' => $backendName,
					'available' => false,
					'supports' => false,
					'reason' => 'backend disabled or prerequisites not present',
				];
				continue;
			}

			$supports = $backend->canHandle($mimeType, $ext);
			if ($supports === false) {
				$extLabel = $ext;
				if ($ext === '') {
					$extLabel = '(none)';
				}

				$attempts[] = [
					'name' => $backendName,
					'available' => true,
					'supports' => false,
					'reason' => sprintf(
						'backend does not support MIME %s / extension %s',
						$mimeType,
						$extLabel
					),
				];
				continue;
			}

			try {
				$result = $backend->convert($source);
				$this->logger->info(
					'[PdfConversionService] Conversion succeeded',
					[
						'backend' => $backendName,
						'source' => $source->getPath(),
						'output' => $result->getPath(),
					]
				);
				return [
					'file' => $result,
					'backend' => $backendName,
				];
			} catch (Throwable $e) {
				$attempts[] = [
					'name' => $backendName,
					'available' => true,
					'supports' => true,
					'reason' => $e->getMessage(),
				];
				$this->logger->warning(
					'[PdfConversionService] Backend failed; falling through',
					[
						'backend' => $backendName,
						'source' => $source->getPath(),
						'exception' => get_class($e),
						'message' => $e->getMessage(),
					]
				);
				continue;
			}//end try
		}//end foreach

		// No backend succeeded — emit a structured failure.
		throw new ConversionFailedException(
			message: 'Conversion to PDF failed; no backend in the cascade succeeded.',
			attempts: $attempts
		);

	}//end convertToPdfReporting()

	/**
	 * Tagged (PDF/UA) conversion: only LibreOffice can tag, so every other
	 * backend is recorded as unable and never asked. Without LibreOffice the
	 * conversion fails; it never falls back to an untagged PDF.
	 *
	 * @param File $source The source file.
	 * @param bool $pdfa   Whether to keep PDF/A as well.
	 *
	 * @return array{file: File, backend: string} The PDF beside the source.
	 *
	 * @throws ConversionFailedException When no tagged PDF can be made.
	 *
	 * @spec openspec/changes/pdfua-accessible-output/tasks.md#task-1.1
	 */
	private function convertTagged(File $source, bool $pdfa): array {
		$attempts = [];
		foreach ($this->backends as $backend) {
			if ($backend instanceof LibreOfficeHeadlessBackend === false) {
				if ($backend instanceof ConversionBackendInterface === true) {
					$attempts[] = ['name' => $backend->name(), 'available' => $backend->isAvailable(), 'supports' => false, 'reason' => 'cannot write tagged PDF'];
				}

				continue;
			}

			try {
				$bytes = $backend->convertTagged(
					bytes: (string) $source->getContent(),
					extension: $this->extractExtension(name: $source->getName()),
					pdfa: $pdfa
				);
			} catch (ConversionFailedException $e) {
				$attempts = array_merge($attempts, $e->getAttempts());
				continue;
			}

			$name = basename($source->getName());
			$stem = $name;
			if (strrpos($name, '.') !== false) {
				$stem = substr($name, 0, (int) strrpos($name, '.'));
			}

			$parent = $source->getParent();
			if ($parent->nodeExists($stem . '.pdf') === true) {
				$parent->get($stem . '.pdf')->delete();
			}

			return ['file' => $parent->newFile($stem . '.pdf', $bytes), 'backend' => $backend->name()];
		}//end foreach

		throw new ConversionFailedException(
			message: 'Accessible PDF output needs LibreOffice, which is not available; no untagged PDF is made instead.',
			attempts: $attempts,
			code: 503
		);

	}//end convertTagged()

	/**
	 * Return the lowercased extension of $name without the leading dot.
	 *
	 * @param string $name File name, with or without an extension.
	 *
	 * @return string Lowercased extension, or an empty string when the name
	 *                carries no dot.
	 */
	private function extractExtension(string $name): string {
		$dotPos = strrpos($name, '.');
		if ($dotPos === false) {
			return '';
		}

		return strtolower(substr($name, ($dotPos + 1)));
	}//end extractExtension()
}//end class
