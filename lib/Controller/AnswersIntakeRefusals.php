<?php

/**
 * Answers Intake Refusals
 *
 * The answers the intake actions give when a request is refused: the bare
 * 400 for a request value of the wrong type, and the status a service
 * refusal carries.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
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

namespace OCA\Filinq\Controller;

use OCA\Filinq\Exception\IntakeRefusedException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use Throwable;

/**
 * Builds the refusals of the intake actions.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
trait AnswersIntakeRefusals {

	/**
	 * The empty 400 the dispatcher answers a typed argument it cannot pass with.
	 *
	 * @return Response
	 *
	 * @spec exclude response helper, carries no product behaviour of its own.
	 */
	private function badRequest(): Response {
		return new Response(Http::STATUS_BAD_REQUEST);

	}//end badRequest()

	/**
	 * Answer one failure with the status the service gave it.
	 *
	 * @param Throwable $error The failure.
	 *
	 * @return JSONResponse The refusal.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	private function failure(Throwable $error): JSONResponse {
		$status = Http::STATUS_INTERNAL_SERVER_ERROR;
		if ($error instanceof IntakeRefusedException) {
			$status = $error->getStatus();
		}

		return new JSONResponse(data: ['error' => $error->getMessage()], statusCode: $status);

	}//end failure()
}//end trait
