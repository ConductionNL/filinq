<?php

/**
 * Waarmerk Refused Exception
 *
 * A seal, verification setup or revocation that was refused, with the HTTP
 * status the controller answers it with.
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
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;
use Throwable;

/**
 * A refused waarmerk operation and the HTTP status it maps to.
 *
 * 503 means the organisation key could not be resolved: sealing fails closed
 * and no artifact is produced.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
 */
class WaarmerkRefusedException extends RuntimeException {

	/**
	 * Constructor.
	 *
	 * @param string $message Why the operation was refused.
	 * @param int $status The HTTP status to answer with.
	 * @param array<string, mixed> $details Extra response fields (e.g. the open signers).
	 * @param Throwable|null $previous The cause.
	 *
	 * @return void
	 */
	public function __construct(
		string $message,
		private readonly int $status = 422,
		private readonly array $details = [],
		?Throwable $previous = null,
	) {
		parent::__construct(message: $message, code: $status, previous: $previous);

	}//end __construct()

	/**
	 * The HTTP status.
	 *
	 * @return int
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
	 */
	public function getStatus(): int {
		return $this->status;

	}//end getStatus()

	/**
	 * Extra response fields.
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
	 */
	public function getDetails(): array {
		return $this->details;

	}//end getDetails()
}//end class
