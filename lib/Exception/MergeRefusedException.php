<?php

/**
 * Merge Refused Exception
 *
 * Raised when a merge is refused before it starts: an input the caller may not
 * read, a target folder they may not write, or a request that names no inputs
 * at all.
 *
 * It carries the status the refusal deserves, so the controller answers 403 for
 * a right the caller does not have and 400 for a request that could never be
 * right, without re-deciding what the service already decided.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;
use Throwable;

/**
 * A refusal from the merge, with the status it deserves.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */
class MergeRefusedException extends RuntimeException {

	/**
	 * Constructor.
	 *
	 * @param string $message What was refused, in a sentence somebody can read.
	 * @param int $status The HTTP status this refusal deserves.
	 * @param Throwable|null $previous The underlying failure, when there was one.
	 *
	 * @return void
	 */
	public function __construct(
		string $message,
		private readonly int $status = 403,
		?Throwable $previous = null,
	) {
		parent::__construct(message: $message, code: $status, previous: $previous);

	}//end __construct()

	/**
	 * The HTTP status this refusal deserves.
	 *
	 * @return int The status code.
	 */
	public function getStatus(): int {
		return $this->status;

	}//end getStatus()
}//end class
