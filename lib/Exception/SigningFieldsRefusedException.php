<?php

/**
 * Signing Fields Refused Exception
 *
 * A signing request or a sign act was refused because of its signer-filled
 * fields: a check this server cannot run, an empty required field, or a value
 * that fails its check.
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
 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * A refusal with a message per field, keyed by the field's placement position.
 *
 * The messages never contain what was typed, so the exception can be logged
 * as it is (signing-field-validation D8).
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.2
 */
class SigningFieldsRefusedException extends RuntimeException {

	/**
	 * Constructor
	 *
	 * @param array<int, string> $fieldErrors The message per placement position.
	 * @param int                $code        400 for a request that cannot be created, 422 for a sign act.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.2
	 */
	public function __construct(
		private readonly array $fieldErrors,
		int $code = 422,
	) {
		parent::__construct(
			message: 'Signing fields refused at position(s) ' . implode(', ', array_keys($fieldErrors)),
			code: $code
		);

	}//end __construct()

	/**
	 * The message per placement position.
	 *
	 * @return array<int, string> The field errors.
	 *
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.2
	 */
	public function fieldErrors(): array {
		return $this->fieldErrors;

	}//end fieldErrors()
}//end class
