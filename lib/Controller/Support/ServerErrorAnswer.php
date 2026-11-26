<?php

/**
 * Server error answer
 *
 * The logged, translated 500 envelope a controller answers a caught
 * exception with.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller\Support;

use Exception;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

/**
 * Logs a caught exception and answers it as a translated 500 envelope.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 */
final class ServerErrorAnswer {

	/**
	 * OpenRegister's schema-validation refusal.
	 *
	 * @var string
	 */
	private const VALIDATION_EXCEPTION = 'OCA\\OpenRegister\\Exception\\ValidationException';

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger Records the failure
	 * @param IL10N           $l10n   Translates the answer
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly IL10N $l10n,
	) {

	}//end __construct()

	/**
	 * Log the exception after the prefix and answer it as a 500, or as a 400 when OpenRegister refused the record's values.
	 *
	 * @param string    $message   Log prefix and user-facing prefix.
	 * @param Exception $exception The caught exception.
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/specs/entity-publication-policies/spec.md
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-4.1
	 */
	public function answer(string $message, Exception $exception): JSONResponse {
		// A record OpenRegister refuses against its schema (a value outside an
		// enum, a missing required field) is the caller's mistake, not a server
		// failure: answer it as 400 with the reason. Matched by name so this
		// app keeps no hard dependency on OpenRegister's classes.
		if (is_a($exception, self::VALIDATION_EXCEPTION) === true) {
			$this->logger->info(
				$message . $exception->getMessage(),
				['exception' => $exception]
			);
			return new JSONResponse(
				['error' => $this->l10n->t($message . '%s', [$exception->getMessage()])],
				400
			);
		}

		$this->logger->error(
			$message . $exception->getMessage(),
			['exception' => $exception]
		);
		return new JSONResponse(
			['error' => $this->l10n->t($message . '%s', [$exception->getMessage()])],
			500
		);

	}//end answer()
}//end class
