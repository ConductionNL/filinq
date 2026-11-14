<?php

/**
 * Why a PDF was not stamped.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;
use Throwable;

/**
 * A stamp refused, with the code a calling app can show.
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1.2
 */
final class PdfStampRefusal extends RuntimeException {

	/**
	 * The file is not a PDF.
	 */
	public const NOT_A_PDF = 'not-a-pdf';

	/**
	 * The PDF is encrypted and cannot be imported.
	 */
	public const ENCRYPTED = 'encrypted';

	/**
	 * The file is larger than the configured limit.
	 */
	public const TOO_LARGE = 'too-large';

	/**
	 * Anything else: no text, an unknown placement, or a PDF that would not import.
	 */
	public const FAILED = 'failed';

	/**
	 * Constructor.
	 *
	 * @param string         $refusalCode One of the codes above.
	 * @param string         $reason      Why, in words a caller can show.
	 * @param Throwable|null $previous    The failure behind it.
	 */
	public function __construct(
		private readonly string $refusalCode,
		string $reason,
		?Throwable $previous = null,
	) {
		parent::__construct(message: $reason, code: 422, previous: $previous);
	}//end __construct()

	/**
	 * The refusal code.
	 *
	 * @return string not-a-pdf, encrypted, too-large or failed.
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1.2
	 */
	public function getRefusalCode(): string {
		return $this->refusalCode;
	}//end getRefusalCode()
}//end class
