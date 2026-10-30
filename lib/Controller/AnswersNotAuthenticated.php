<?php

/**
 * AnswersNotAuthenticated — the 401 answer for a request without a session user.
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

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;

/**
 * Builds the 401 JSON answer the controllers return when there is no session user.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude response helper — carries no product behaviour of its own
 */
trait AnswersNotAuthenticated {
	/**
	 * A 401 response carrying the message under `error`.
	 *
	 * @param string $message The message, translated by the caller when it should be.
	 *
	 * @return JSONResponse The 401 response.
	 */
	private function notAuthenticated(string $message): JSONResponse {
		return new JSONResponse(data: ['error' => $message], statusCode: Http::STATUS_UNAUTHORIZED);
	}//end notAuthenticated()
}//end trait
