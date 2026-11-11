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
	 * The source types {@see getCapabilities()} asks every backend about.
	 */
	private const PROBED_INPUTS = [
		'html' => 'text/html',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'odt' => 'application/vnd.oasis.opendocument.text',
	];

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
	 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-1.1
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
			$tagged = new TaggedPdfConversion(backends: $this->backends, source: $source, pdfa: (($opts['pdfa'] ?? false) === true));
			return $tagged->run();
		}

		return (new PdfConversionCascade(backends: $this->backends, logger: $this->logger, source: $source))->run();

	}//end convertToPdfReporting()

	/**
	 * What each backend could do right now, without converting anything.
	 *
	 * One entry per backend in cascade order, in the shape a
	 * {@see ConversionFailedException} reports its attempts in (`name`,
	 * `available`, `supports`, `reason`), where `supports` answers for HTML,
	 * the intermediate every generated document starts as. `inputs` lists
	 * which of the probed source types the backend takes. A probe that throws
	 * reports the backend unavailable with the exception's message; this
	 * method never throws.
	 *
	 * @return array<int, array{name: string, available: bool, supports: bool, inputs: string[], reason: string|null}>
	 *
	 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.2
	 */
	public function getCapabilities(): array {
		$report = [];
		foreach ($this->backends as $backend) {
			if ($backend instanceof ConversionBackendInterface === true) {
				$report[] = $this->capabilityOf(backend: $backend);
			}
		}

		return $report;

	}//end getCapabilities()

	/**
	 * What one backend could do right now; a probe that throws reports it unavailable.
	 *
	 * @param ConversionBackendInterface $backend The backend.
	 *
	 * @return array{name: string, available: bool, supports: bool, inputs: string[], reason: string|null}
	 */
	private function capabilityOf(ConversionBackendInterface $backend): array {
		$entry = ['name' => $backend->name(), 'available' => false, 'supports' => false, 'inputs' => [], 'reason' => null];
		try {
			$entry['available'] = $backend->isAvailable();
			$this->probeInputs(backend: $backend, entry: $entry);
			$entry['supports'] = in_array('html', $entry['inputs'], true);
			if ($entry['available'] === false) {
				$entry['reason'] = 'backend disabled or prerequisites not present';
			}
		} catch (Throwable $e) {
			$entry['available'] = false;
			$entry['reason'] = 'availability probe failed: ' . $e->getMessage();
		}

		return $entry;

	}//end capabilityOf()

	/**
	 * Add to the entry each probed source type the backend takes, in probe order.
	 *
	 * The entry is filled as the probes run, so a probe that throws leaves
	 * the types found before it in place.
	 *
	 * @param ConversionBackendInterface                                                                   $backend The backend.
	 * @param array{name: string, available: bool, supports: bool, inputs: string[], reason: string|null} $entry   The capability entry.
	 *
	 * @return void
	 */
	private function probeInputs(ConversionBackendInterface $backend, array &$entry): void {
		foreach (self::PROBED_INPUTS as $ext => $mime) {
			if ($backend->canHandle($mime, $ext) === true) {
				$entry['inputs'][] = $ext;
			}
		}

	}//end probeInputs()
}//end class
