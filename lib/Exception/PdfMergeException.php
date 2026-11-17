<?php

/**
 * PDF Merge Exception
 *
 * Raised by PdfService::mergePdfs() when one input stream cannot be read as
 * a PDF. It names that stream's index, so the caller can put a placeholder
 * page in its place and merge again.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/pdf-generation/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;
use Throwable;

/**
 * One input of a PDF merge could not be read.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/pdf-generation/spec.md
 */
class PdfMergeException extends RuntimeException {

	/**
	 * Constructor.
	 *
	 * @param int            $streamIndex The index of the stream that could not be read.
	 * @param Throwable|null $previous    The parser's own failure.
	 */
	public function __construct(
		private readonly int $streamIndex,
		?Throwable $previous = null,
	) {
		parent::__construct(message: 'PDF stream ' . $streamIndex . ' could not be read as a PDF.', code: 422, previous: $previous);

	}//end __construct()

	/**
	 * The index, in the merge input, of the stream that could not be read.
	 *
	 * @return int
	 *
	 * @spec openspec/specs/pdf-generation/spec.md
	 */
	public function getStreamIndex(): int {
		return $this->streamIndex;

	}//end getStreamIndex()
}//end class
