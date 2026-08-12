<?php

/**
 * PDF/A-3 Conversion Exception
 *
 * Raised by Pdfa3ConversionService whenever a source cannot be turned
 * into a genuinely PDF/A-3-compliant file. Every raise site is a
 * deliberate "fail loud" decision — this service must never return
 * bytes that merely look like a PDF while silently skipping PDF/A-3
 * requirements (that would misrepresent the file as archival-grade
 * when it is not).
 *
 * @category  Exception
 * @package   OCA\DocuDesk\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Exception;

use RuntimeException;
use Throwable;

/**
 * Thrown by Pdfa3ConversionService. Carries a stable machine-readable
 * `reason` code (used by the controller to pick an HTTP status) and an
 * `adminHint` string suitable for direct display to an administrator —
 * mirroring the guarded-command-runner pattern used elsewhere in the
 * fleet (e.g. LibreSign/soffice availability checks) so an operator
 * always gets an actionable message instead of a stack trace.
 *
 * @category  Exception
 * @package   OCA\DocuDesk\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */
class Pdfa3ConversionException extends RuntimeException {

	/**
	 * Source file (or one of its attachments) exceeds the configured
	 * byte cap. HTTP 413.
	 */
	public const REASON_SOURCE_TOO_LARGE = 'source_too_large';

	/**
	 * An attachment to be embedded exceeds the configured byte cap.
	 * HTTP 413.
	 */
	public const REASON_ATTACHMENT_TOO_LARGE = 'attachment_too_large';

	/**
	 * Conversion exceeded the configured time budget. HTTP 504.
	 */
	public const REASON_TIME_LIMIT_EXCEEDED = 'time_limit_exceeded';

	/**
	 * The PDF/A-3 converter (mPDF/FPDI) is disabled in tenant config or
	 * its classes are not autoloadable. HTTP 503.
	 */
	public const REASON_CONVERTER_UNAVAILABLE = 'converter_unavailable';

	/**
	 * The source could not be parsed as a PDF (corrupt, encrypted, or
	 * not actually a PDF despite its declared MIME type). HTTP 422.
	 */
	public const REASON_SOURCE_UNREADABLE = 'source_unreadable';

	/**
	 * MPDF raised during rendering/attachment/metadata assembly. HTTP 500.
	 */
	public const REASON_RENDER_FAILED = 'render_failed';

	/**
	 * The assembled output failed this service's own post-conversion
	 * PDF/A-3 marker check (missing `%PDF` header or XMP
	 * `pdfaid:part`/`pdfaid:conformance` identification) — the
	 * no-silent-passthrough guardrail. HTTP 500.
	 */
	public const REASON_OUTPUT_VALIDATION_FAILED = 'output_validation_failed';

	/**
	 * Machine-readable reason code — one of the REASON_* constants.
	 *
	 * @var string
	 */
	private string $reason;

	/**
	 * Human-readable, safe-to-display admin hint describing what an
	 * operator should check or configure.
	 *
	 * @var string
	 */
	private string $adminHint;

	/**
	 * Constructor.
	 *
	 * @param string $reason One of the REASON_* constants.
	 * @param string $message Human-readable summary (safe for API responses).
	 * @param string $adminHint Actionable hint for an administrator.
	 * @param int $code HTTP-style status code.
	 * @param Throwable|null $previous Underlying cause if any.
	 */
	public function __construct(
		string $reason,
		string $message,
		string $adminHint,
		int $code = 500,
		?Throwable $previous = null,
	) {
		parent::__construct(message: $message, code: $code, previous: $previous);
		$this->reason = $reason;
		$this->adminHint = $adminHint;

	}//end __construct()

	/**
	 * Get the machine-readable reason code.
	 *
	 * @return string One of the REASON_* constants.
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	public function getReason(): string {
		return $this->reason;
	}//end getReason()

	/**
	 * Get the admin-facing hint.
	 *
	 * @return string
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	public function getAdminHint(): string {
		return $this->adminHint;
	}//end getAdminHint()
}//end class
