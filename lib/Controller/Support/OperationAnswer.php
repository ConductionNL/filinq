<?php

/**
 * Operation Answer
 *
 * Runs a controller operation and maps its outcome to a JSON response, the
 * same way for every endpoint that shares it.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller\Support;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * A null outcome is a 404, a 400 or 409 failure carries its message, any
 * other failure is logged and answers a 500 that hides its message.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md
 */
final class OperationAnswer {

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger   Logger for unexpected failures
	 * @param string          $failure  The error text of an unexpected failure
	 * @param string          $notFound The error text of a null outcome
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly string $failure,
		private readonly string $notFound,
	) {

	}//end __construct()

	/**
	 * Run an operation and map its outcome: null is 404; 400 and 409 carry their message.
	 *
	 * @param callable():(array|null) $operation The operation
	 * @param int                     $status    Success status
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	public function answer(callable $operation, int $status = Http::STATUS_OK): JSONResponse {
		try {
			$result = $operation();
		} catch (Throwable $e) {
			return $this->failed(e: $e);
		}

		if ($result === null) {
			return new JSONResponse(data: ['error' => $this->notFound], statusCode: Http::STATUS_NOT_FOUND);
		}

		return new JSONResponse(data: $result, statusCode: $status);

	}//end answer()

	/**
	 * Answer a failure: a 400 or 409 carries its message, anything else is logged and hidden.
	 *
	 * @param Throwable $e The failure
	 *
	 * @return JSONResponse
	 */
	private function failed(Throwable $e): JSONResponse {
		$code = (int) $e->getCode();
		if (in_array($code, [Http::STATUS_BAD_REQUEST, Http::STATUS_CONFLICT], true) === true) {
			return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: $code);
		}

		$this->logger->error($this->failure . ': ' . $e->getMessage(), ['exception' => $e]);
		return new JSONResponse(data: ['error' => $this->failure], statusCode: Http::STATUS_INTERNAL_SERVER_ERROR);

	}//end failed()
}//end class
