<?php

/**
 * Sanitization controller
 *
 * Sanitize one file the caller can read into a clean derivative beside it,
 * and read what earlier runs removed.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-sanitization/tasks.md#3-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use InvalidArgumentException;
use OCA\Filinq\Service\Sanitization\DocumentSanitizationService;
use OCA\Filinq\Service\Sanitization\SanitizationStatus;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Sanitize and sanitization status routes.
 */
class SanitizationController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string                      $appName     The app id.
	 * @param IRequest                    $request     The request.
	 * @param DocumentSanitizationService $sanitizer   The sanitization.
	 * @param SanitizationStatus          $status      What earlier runs did.
	 * @param IUserSession                $userSession The caller.
	 * @param LoggerInterface             $logger      The logger.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly DocumentSanitizationService $sanitizer,
		private readonly SanitizationStatus $status,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Sanitize a file into a derivative.
	 *
	 * @param int $fileId The file.
	 *
	 * @return JSONResponse The run, a fail-flagged skip, or 401/404/415/422/500.
	 *
	 * @spec openspec/changes/document-sanitization/tasks.md#3-2
	 */
	#[NoAdminRequired]
	public function sanitize(int $fileId): JSONResponse {
		return $this->run(action: fn (string $userId): array => $this->sanitizer->sanitize(fileId: $fileId, userId: $userId));

	}//end sanitize()

	/**
	 * What earlier runs on a file removed, and whether the file is itself a sanitized derivative.
	 *
	 * @param int $fileId The file.
	 *
	 * @return JSONResponse The status, or 401/404.
	 *
	 * @spec openspec/changes/document-sanitization/tasks.md#3-5
	 */
	#[NoAdminRequired]
	public function status(int $fileId): JSONResponse {
		return $this->run(action: fn (string $userId): array => $this->status->forFile(fileId: $fileId, userId: $userId));

	}//end status()

	/**
	 * Run as the caller; map refusals to their status with a generic body.
	 *
	 * @param callable $action The action, given the user id.
	 *
	 * @return JSONResponse The answer.
	 */
	private function run(callable $action): JSONResponse {
		$userId = (string) $this->userSession->getUser()?->getUID();
		if ($userId === '') {
			return new JSONResponse(data: ['error' => 'not_authenticated'], statusCode: Http::STATUS_UNAUTHORIZED);
		}

		try {
			return new JSONResponse(data: $action($userId));
		} catch (InvalidArgumentException $e) {
			$status = $e->getCode();
			if (in_array($status, [404, 415, 422], true) === false) {
				$status = Http::STATUS_INTERNAL_SERVER_ERROR;
			}

			return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: $status);
		} catch (Throwable $e) {
			$this->logger->error('Sanitization request failed', ['exception' => $e->getMessage()]);

			return new JSONResponse(data: ['error' => 'failed'], statusCode: Http::STATUS_INTERNAL_SERVER_ERROR);
		}

	}//end run()
}//end class
