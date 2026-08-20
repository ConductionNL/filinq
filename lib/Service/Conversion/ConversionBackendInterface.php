<?php

/**
 * Conversion Backend Interface
 *
 * Contract that every PDF-conversion backend in the cascade implements.
 * See openspec/changes/anonymise-output-as-pdf-by-default/design.md (D3).
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Conversion;

use OCA\DocuDesk\Exception\ConversionFailedException;
use OCP\Files\File;

/**
 * Each PDF-conversion backend implements canHandle, isAvailable, convert,
 * and name. The cascade (PdfConversionService) walks an ordered list of
 * backends, calls isAvailable() first (cheap check), then canHandle()
 * for the source's MIME/extension, then convert() — returning on the
 * first success and aggregating failures into a ConversionFailedException
 * when nothing succeeds.
 *
 * Backends are isolated; adding a new one is "drop a new class in this
 * directory and register it in DI".
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 */
interface ConversionBackendInterface {
	/**
	 * Short identifier used in diagnostic surfaces and the 422 body's
	 * `conversionAttempts[].backend` field.
	 *
	 * Stable across versions — operators read these strings in error
	 * responses.
	 *
	 * @return string Backend identifier (lowercase, snake_case).
	 */
	public function name(): string;

	/**
	 * Whether this backend is usable in the current install. Cheap check:
	 * tenant config flag, binary on PATH, app installed and configured.
	 * Called per-conversion-attempt; expected to be O(1) after any
	 * one-time setup.
	 *
	 * @return bool True when the backend can be invoked.
	 */
	public function isAvailable(): bool;

	/**
	 * Whether this backend can process the given MIME type / extension.
	 * Cheap predicate; no I/O.
	 *
	 * @param string $mimeType MIME type reported by Nextcloud (e.g. application/pdf).
	 * @param string $extension Lowercased file extension WITHOUT the dot (e.g. docx).
	 *
	 * @return bool True when this backend claims the input format.
	 */
	public function canHandle(string $mimeType, string $extension): bool;

	/**
	 * Convert the source file to a PDF (PDF/A-3b when feasible) and
	 * write the result beside the source in Nextcloud Files. The
	 * source file is NOT deleted by the backend — replace/delete
	 * orchestration belongs to the caller.
	 *
	 * @param File $source Source file node.
	 *
	 * @return File The newly written PDF file node.
	 *
	 * @throws ConversionFailedException When this backend genuinely
	 *                                   cannot convert this input
	 *                                   (use the typed exception
	 *                                   rather than a generic one so
	 *                                   the cascade can aggregate
	 *                                   attempts cleanly).
	 */
	public function convert(File $source): File;
}//end interface
