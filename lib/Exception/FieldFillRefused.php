<?php

/**
 * Field fill refused
 *
 * A request for field suggestions, or a decision on them, that filinq refuses, with the HTTP status to answer.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * A refused field-fill request; the code is the HTTP status.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 */
class FieldFillRefused extends RuntimeException {

	/**
	 * Constructor.
	 *
	 * @param string $message The message, in English; the controller translates it.
	 * @param int    $code    The HTTP status.
	 *
	 * @return void
	 */
	public function __construct(string $message, int $code) {
		parent::__construct(message: $message, code: $code);

	}//end __construct()
}//end class
