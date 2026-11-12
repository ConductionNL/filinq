<?php

/**
 * Translated error answer
 *
 * The error answer that logs the exception text and answers only the
 * translated message, under the exception's HTTP error code.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller\Support;

use Exception;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

/**
 * Logs a caught exception and answers its translated message — never the
 * exception text — under its 400-599 code, else 500.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */
final class TranslatedErrorAnswer {

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
	 * Build an error JSON response with logging — never echoes exception text.
	 *
	 * @param string    $message   The log message prefix and the answered message.
	 * @param Exception $exception The exception.
	 *
	 * @return JSONResponse The error response.
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function answer(string $message, Exception $exception): JSONResponse {
		$this->logger->error($message . ': ' . $exception->getMessage(), ['exception' => $exception]);

		$statusCode = Http::STATUS_INTERNAL_SERVER_ERROR;
		if ($exception->getCode() >= 400 && $exception->getCode() < 600) {
			$statusCode = $exception->getCode();
		}

		return new JSONResponse(
			['error' => $this->l10n->t($message)],
			$statusCode
		);

	}//end answer()
}//end class
