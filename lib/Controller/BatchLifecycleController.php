<?php

/**
 * Batch Lifecycle Controller: cancel and resume endpoints of an anonymization batch.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-cooperative-cancellation-req-ddras-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\BatchLifecycleService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * `POST api/anonymization/batch/{batchId}/cancel` and `.../resume`.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-cooperative-cancellation-req-ddras-004
 */
class BatchLifecycleController extends Controller {
	use AnswersNotAuthenticated;

	/**
	 * Constructor.
	 *
	 * @param string                $appName     The app id.
	 * @param IRequest              $request     The request.
	 * @param BatchLifecycleService $lifecycle   Cancels and resumes batches.
	 * @param IUserSession          $userSession The session.
	 * @param IL10N                 $l10n        Translations.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly BatchLifecycleService $lifecycle,
		private readonly IUserSession $userSession,
		private readonly IL10N $l10n,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Cancel a batch between files.
	 *
	 * @param string $batchId The batch id.
	 *
	 * @return JSONResponse 202 with the new batch status, 401, 404 or 409.
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-cooperative-cancellation-req-ddras-004
	 *
	 * @no-admin-idor-exempt ownership is enforced one layer down. BatchStateService::getBatch() returns
	 * null unless the batch's userId is the session user (admins excepted), and null answers 404.
	 */
	#[NoAdminRequired]
	public function cancel(string $batchId): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return $this->notAuthenticated(message: $this->l10n->t('Not authenticated'));
		}

		return $this->respond(result: $this->lifecycle->cancel(batchId: $batchId));

	}//end cancel()

	/**
	 * Resume an errored or cancelled batch, or a completed batch with failed files.
	 *
	 * @param string $batchId The batch id.
	 *
	 * @return JSONResponse 202 with the new batch status and the re-queued count, 401, 404 or 409.
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/redaction-at-scale/tasks.md#5.6
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-idempotent-resume-req-ddras-005
	 *
	 * @no-admin-idor-exempt ownership is enforced one layer down. BatchStateService::getBatch() returns
	 * null unless the batch's userId is the session user (admins excepted), and null answers 404.
	 */
	#[NoAdminRequired]
	public function resume(string $batchId): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return $this->notAuthenticated(message: $this->l10n->t('Not authenticated'));
		}

		return $this->respond(result: $this->lifecycle->resume(batchId: $batchId));

	}//end resume()

	/**
	 * Turn a lifecycle result into a response, with its refusal reason in words.
	 *
	 * @param array<string, mixed> $result The lifecycle result with its `status`.
	 *
	 * @return JSONResponse The response.
	 */
	private function respond(array $result): JSONResponse {
		$status = (int)$result['status'];
		$reason = ($result['reason'] ?? null);
		unset($result['status'], $result['reason']);
		if ($reason === 'not-found') {
			$result['error'] = $this->l10n->t('Batch not found');
		} else if ($reason === 'not-cancellable') {
			$result['error'] = $this->l10n->t('This batch can no longer be cancelled');
		} else if ($reason === 'not-resumable') {
			$result['error'] = $this->l10n->t('Only an errored or cancelled batch can be resumed');
		}

		return new JSONResponse(data: $result, statusCode: $status);

	}//end respond()
}//end class
