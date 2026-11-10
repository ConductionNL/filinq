<?php

/**
 * Backend Failures
 *
 * Builds the ConversionFailedException a conversion backend throws: one
 * attempt record naming the backend as available and supporting the source,
 * with the reason it failed.
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
 * The failures of one conversion backend.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/pdf-conversion/spec.md
 */
final class BackendFailures {
	/**
	 * Constructor.
	 *
	 * @param string $backendName The backend's name, as on its attempt record.
	 */
	public function __construct(
		private readonly string $backendName,
	) {

	}//end __construct()

	/**
	 * The exception for one failure, with the backend's single attempt record.
	 *
	 * @param BackendFailure $failure  The message and the attempt reason.
	 * @param Throwable|null $previous The underlying cause, if any.
	 *
	 * @return ConversionFailedException The exception to throw.
	 *
	 * @spec openspec/specs/pdf-conversion/spec.md
	 */
	public function failed(BackendFailure $failure, ?Throwable $previous = null): ConversionFailedException {
		return new ConversionFailedException(
			message: $failure->message,
			attempts: [
				[
					'name' => $this->backendName,
					'available' => true,
					'supports' => true,
					'reason' => $failure->reason,
				],
			],
			previous: $previous
		);

	}//end failed()
}//end class
