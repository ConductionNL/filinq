<?php

/**
 * Contract of DocumentValidationService
 *
 * The public surface of the DocumentValidationService service, so callers in other
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

/**
 * Contract of DocumentValidationService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface DocumentValidationServiceInterface {
	/**
	 * Check identifiers.
	 */
	public const CHECK_FORMAT_NOT_ALLOWED = 'format-not-allowed';

	/**
	 * CHECK_FILE_UNREADABLE
	 */
	public const CHECK_FILE_UNREADABLE = 'file-unreadable';

	/**
	 * CHECK_TEXT_LAYER_MISSING
	 */
	public const CHECK_TEXT_LAYER_MISSING = 'text-layer-missing';

	/**
	 * Archival: veraPDF finds the PDF does not meet its PDF/A level.
	 */
	public const CHECK_PDFA_CONFORMANCE = 'pdfa-conformance-failed';

	/**
	 * Archival: veraPDF finds fonts that are used but not embedded.
	 */
	public const CHECK_PDFA_FONTS = 'pdfa-font-not-embedded';

	/**
	 * Archival: an archival check is on but veraPDF cannot answer.
	 */
	public const CHECK_ARCHIVAL_UNAVAILABLE = 'archival-validator-unavailable';

	/**
	 * Accessibility: the PDF has no structure tags.
	 */
	public const CHECK_PDF_NOT_TAGGED = 'pdf-not-tagged';

	/**
	 * Accessibility: the PDF catalog names no language.
	 */
	public const CHECK_PDF_LANGUAGE_MISSING = 'pdf-language-missing';

	/**
	 * Accessibility: the PDF has no title.
	 */
	public const CHECK_PDF_TITLE_MISSING = 'pdf-title-missing';

	/**
	 * Accessibility: a tagged PDF does not say it follows PDF/UA.
	 */
	public const CHECK_PDFUA_IDENTIFIER_MISSING = 'pdfua-identifier-missing';

	/**
	 * The category of the content and metadata checks.
	 */
	public const CATEGORY_DOCUMENT = 'document';

	/**
	 * Severity values.
	 */
	public const SEVERITY_OFF = 'off';

	/**
	 * SEVERITY_BLOCKING
	 */
	public const SEVERITY_BLOCKING = 'blocking';

	/**
	 * Verdict values.
	 */
	public const STATUS_PASSED = 'passed';

	/**
	 * STATUS_FAILED
	 */
	public const STATUS_FAILED = 'failed';

	/**
	 * Validate a file + its document record against the resolved profile.
	 *
	 * @param File $file The file to validate.
	 * @param array<string, mixed> $record The document record (metadata).
	 * @param string|null $documentType Optional document-type hint.
	 *
	 * @return array{validationStatus:string, validationFindings:array<int, array<string,mixed>>}
	 *
	 * @spec openspec/specs/document-validation-checks/spec.md
	 */
	public function validate(File $file, array $record = [], ?string $documentType = null): array;

	/**
	 * Resolve the validation profile for a document type, falling back to default.
	 *
	 * @param string $documentType The document type.
	 *
	 * @return array{allowedMimes:array<int,string>, requiredFields:array<int,string>, severities:array<string,string>}
	 *
	 * @spec openspec/specs/document-validation-checks/spec.md
	 */
	public function resolveProfile(string $documentType): array;

	/**
	 * Aggregate findings into a verdict.
	 *
	 * @param array<int, array<string, mixed>> $findings The findings.
	 *
	 * @return string The verdict (passed|warnings|failed).
	 *
	 * @spec openspec/specs/document-validation-checks/spec.md
	 */
	public function aggregate(array $findings): string;
}//end interface
