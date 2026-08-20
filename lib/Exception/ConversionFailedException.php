<?php

/**
 * Conversion Failed Exception
 *
 * Raised by PdfConversionService when no backend in the cascade was
 * able to convert the source file to PDF. Carries the per-backend
 * attempt records so the anonymise endpoint can emit the documented
 * HTTP 422 body (design D5).
 *
 * @category  Exception
 * @package   OCA\DocuDesk\Exception
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

namespace OCA\DocuDesk\Exception;

use RuntimeException;
use Throwable;

/**
 * Thrown when PdfConversionService::convertToPdf cannot complete the
 * conversion via any registered backend. The aggregated `attempts`
 * array surfaces which backends were tried and why each failed —
 * consumed by the controller layer to build the 422 response.
 *
 * @category  Exception
 * @package   OCA\DocuDesk\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 */
class ConversionFailedException extends RuntimeException {

	/**
	 * Per-backend attempt records. Each entry has the shape
	 * `{name: string, available: bool, supports: bool, reason: string}`
	 * documented in design.md D5.
	 *
	 * @var array<int, array{name:string,available:bool,supports:bool,reason:string}>
	 */
	private array $attempts;

	/**
	 * Constructor.
	 *
	 * All arguments are optional so callers and tests may construct the
	 * exception with only the parts they care about. The default code is
	 * 422 (Unprocessable Entity), matching the documented anonymise-endpoint
	 * response (design D5).
	 *
	 * @param string $message Human-readable summary.
	 * @param array<int, array{name:string,available:bool,supports:bool,reason:string}> $attempts Per-backend records.
	 * @param int $code HTTP-style status code (default 422).
	 * @param Throwable|null $previous Underlying cause if any.
	 */
	public function __construct(
		string $message = 'PDF conversion failed: no backend in the cascade could convert the source file.',
		array $attempts = [],
		int $code = 422,
		?Throwable $previous = null,
	) {
		parent::__construct(message: $message, code: $code, previous: $previous);
		$this->attempts = $attempts;

	}//end __construct()

	/**
	 * Get the per-backend attempt records. Shape per D5: each entry
	 * carries the backend name, whether it was available, whether it
	 * supported the input, and a short human-readable reason.
	 *
	 * @return array<int, array{name:string,available:bool,supports:bool,reason:string}>
	 */
	public function getAttempts(): array {
		return $this->attempts;
	}//end getAttempts()
}//end class
