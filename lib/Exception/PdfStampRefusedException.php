<?php

/**
 * Pdf Stamp Refused Exception
 *
 * Raised when filinq will not stamp a file: it is not a PDF, it is encrypted,
 * it is larger than the configured limit, or the stamping itself failed. Each
 * case carries its own refusal code, so a sibling app can tell the reader why
 * a paper cannot be shown instead of serving it unstamped.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;
use Throwable;

/**
 * A PDF filinq refuses to stamp, with the reason as a code.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1-2
 */
class PdfStampRefusedException extends RuntimeException {

	public const NOT_A_PDF = 'not-a-pdf';

	public const ENCRYPTED = 'encrypted';

	public const TOO_LARGE = 'too-large';

	public const FAILED = 'failed';

	/**
	 * Constructor.
	 *
	 * @param string         $refusalCode One of the class constants.
	 * @param string         $message     Why the file was refused.
	 * @param Throwable|null $previous    The underlying failure, when there was one.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly string $refusalCode,
		string $message,
		?Throwable $previous = null,
	) {
		// 400 for what the caller sent, 500 for a stamping failure: the
		// controllers map a 4xx code straight onto the HTTP status.
		$httpCode = 400;
		if ($refusalCode === self::FAILED) {
			$httpCode = 500;
		}

		parent::__construct(message: $message, code: $httpCode, previous: $previous);

	}//end __construct()

	/**
	 * The refusal code: not-a-pdf, encrypted, too-large or failed.
	 *
	 * @return string The code.
	 */
	public function getRefusalCode(): string {
		return $this->refusalCode;

	}//end getRefusalCode()
}//end class
