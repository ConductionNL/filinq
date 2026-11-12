<?php

/**
 * Handler answer
 *
 * The answer of a domain call: its result, or its failure as a bad request.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec exclude response plumbing shared by two controllers; no behaviour of its own
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller\Support;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use Throwable;

/**
 * Runs a domain call and answers its result as a 200, any failure as a 400
 * carrying the failure's message.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude response plumbing shared by two controllers; no behaviour of its own
 */
final class HandlerAnswer {

	/**
	 * Run the domain call and answer it.
	 *
	 * @param callable():array<mixed> $handler The domain call.
	 *
	 * @return JSONResponse The result (200) or the failure's message (400).
	 *
	 * @spec exclude response plumbing shared by two controllers; no behaviour of its own
	 */
	public function answer(callable $handler): JSONResponse {
		try {
			return new JSONResponse(data: $handler(), statusCode: Http::STATUS_OK);
		} catch (Throwable $e) {
			return new JSONResponse(
				data: ['error' => $e->getMessage()],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

	}//end answer()
}//end class
