<?php

/**
 * Contract of Pdfa3ConversionService
 *
 * The public surface of the Pdfa3ConversionService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
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

namespace OCA\Filinq\Kernel\Contracts;

use OCP\Files\File;
use OCA\Filinq\Exception\Pdfa3ConversionException;

/**
 * Contract of Pdfa3ConversionService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface Pdfa3ConversionServiceInterface {
	/**
	 * Convert an existing PDF file to PDF/A-3b.
	 *
	 * Imports every page of the source PDF as-is (page content is
	 * copied, not re-rendered) into a fresh PDF/A-3 container carrying
	 * the supplied metadata and attachments.
	 *
	 * @param File $source The existing PDF to convert.
	 * @param array<string,mixed> $metadata MDTO/archival metadata (title,
	 *                                      author, subject, keywords,
	 *                                      plus any archival fields —
	 *                                      identifier, caseReference,
	 *                                      archiefvormer,
	 *                                      aggregatieniveau, etc.).
	 * @param array<int,array<string,mixed>> $attachments Files to embed. Each entry:
	 *                                                    {name, mime, content, description?,
	 *                                                    AFRelationship?}.
	 * @param array<string,mixed> $options {format?, orientation?}.
	 *
	 * @return array{content:string,checksumSha256:string,pages:int,conformance:string,verified:string} The PDF;
	 *         verified is the veraPDF verdict: true, false or skipped.
	 *
	 * @throws Pdfa3ConversionException On any guardrail violation or conversion failure.
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	public function convertExistingPdf(File $source, array $metadata = [], array $attachments = [], array $options = []): array;

	/**
	 * Convert freshly rendered HTML directly to PDF/A-3b.
	 *
	 * Unlike convertExistingPdf(), mPDF renders every element itself,
	 * so font embedding and colour-space compliance are fully
	 * guaranteed by mPDF's own PDF/A auto-correction — this is the
	 * strongest-guarantee path and the one filinq's own generation
	 * flow (PdfController::renderPdfA) uses.
	 *
	 * @param string $html Rendered HTML document body.
	 * @param array<string,mixed> $metadata MDTO/archival metadata; see convertExistingPdf().
	 * @param array<int,array<string,mixed>> $attachments Files to embed; see convertExistingPdf().
	 * @param array<string,mixed> $options {format?, orientation?, margin?}.
	 *
	 * @return array{content:string,checksumSha256:string,pages:int,conformance:string,verified:string} The PDF;
	 *         verified is the veraPDF verdict: true, false or skipped.
	 *
	 * @throws Pdfa3ConversionException On any guardrail violation or conversion failure.
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	public function convertHtml(string $html, array $metadata = [], array $attachments = [], array $options = []): array;
}//end interface
