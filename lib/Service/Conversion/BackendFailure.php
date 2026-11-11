<?php

/**
 * Backend Failure
 *
 * Why one conversion backend could not convert a source: the message the
 * caller sees and the reason recorded on its attempt, and the
 * ConversionFailedException that carries them as the backend's one attempt.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Conversion
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

namespace OCA\Filinq\Service\Conversion;

use OCA\Filinq\Exception\ConversionFailedException;
use Throwable;

/**
 * The message and attempt reason of one backend failure.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/pdf-conversion/spec.md
 */
final class BackendFailure {
	/**
	 * Constructor.
	 *
	 * @param string $message The exception message.
	 * @param string $reason  The reason recorded on the backend's attempt.
	 */
	public function __construct(
		public readonly string $message,
		public readonly string $reason,
	) {

	}//end __construct()

	/**
	 * The exception for this failure, with the backend's single attempt record:
	 * available, supporting the source, failed for this reason.
	 *
	 * @param string         $backendName The backend's name, as on its attempt record.
	 * @param Throwable|null $previous    The underlying cause, if any.
	 *
	 * @return ConversionFailedException The exception to throw.
	 *
	 * @spec openspec/specs/pdf-conversion/spec.md
	 */
	public function exception(string $backendName, ?Throwable $previous = null): ConversionFailedException {
		return new ConversionFailedException(
			message: $this->message,
			attempts: [
				[
					'name' => $backendName,
					'available' => true,
					'supports' => true,
					'reason' => $this->reason,
				],
			],
			previous: $previous
		);

	}//end exception()
}//end class
