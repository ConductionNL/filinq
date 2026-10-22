<?php

/**
 * A wizard save or a wizard run that was refused
 *
 * Carries the HTTP status (404, 409, 422, 423) and, for 422, the error per
 * question, so the controller can answer with both.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Wizard
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Wizard;

use OCA\Filinq\Exception\ErrorDetailsInterface;
use RuntimeException;

/**
 * Refusal with per-question errors.
 */
class WizardRefused extends RuntimeException implements ErrorDetailsInterface {

	/**
	 * Constructor.
	 *
	 * @param string                $message The message.
	 * @param int                   $code    The HTTP status.
	 * @param array<string, string> $errors  Errors by question key.
	 *
	 * @return void
	 */
	public function __construct(string $message, int $code, private readonly array $errors=[]) {
		parent::__construct(message: $message, code: $code);

	}//end __construct()

	/**
	 * The errors by question key.
	 *
	 * @return array<string, string> The errors.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
	 */
	public function getErrors(): array {
		return $this->errors;

	}//end getErrors()

	/**
	 * The error body names which wizard questions were unanswered or answered wrongly.
	 *
	 * @return array<string, array<string, string>> `errors` by question key, or nothing.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-4
	 */
	public function getErrorDetails(): array {
		if ($this->errors === []) {
			return [];
		}

		return ['errors' => $this->errors];

	}//end getErrorDetails()
}//end class
