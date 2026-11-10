<?php

/**
 * Backend Failure
 *
 * Why one conversion backend could not convert a source: the message the
 * caller sees and the reason recorded on its attempt.
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
}//end class
