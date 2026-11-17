<?php

/**
 * Document review controller.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Exception\DocumentReviewRefusedException;
use OCA\Filinq\Kernel\Contracts\AnonymizeRequestServiceInterface;
use OCA\Filinq\Service\Review\DocumentReviewService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Reads, sets and clears the per-document checked mark of the review workbench.
 *
 * Every method signs in the session's user and checks the file against the
 * caller's own file tree before anything is read or written.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
 *
 * @spec openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#requirement-per-document-checked-gate-req-ddarw-007
 */
class DocumentReviewController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string                           $appName     The app name.
	 * @param IRequest                         $request     The request.
	 * @param DocumentReviewService            $reviews     The checked mark.
	 * @param IUserSession                     $userSession The current session.
	 * @param AnonymizeRequestServiceInterface $access      The per-file access check.
	 * @param IL10N                            $l10n        The translator.
	 * @param LoggerInterface                  $logger      The logger.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly DocumentReviewService $reviews,
		private readonly IUserSession $userSession,
		private readonly AnonymizeRequestServiceInterface $access,
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Where one document's review stands.
	 *
	 * @param int $fileId The document.
	 *
	 * @return JSONResponse The status: checked, stale, extracted, counts, the mark and the gate mode.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
	 *
	 * @no-admin-idor-exempt AnonymizeRequestService::verifyFileAccess() refuses a file outside the
	 * caller's user folder before the mark is read.
	 */
	#[NoAdminRequired]
	public function show(int $fileId): JSONResponse {
		$refusal = $this->refusal(fileId: $fileId);
		if ($refusal !== null) {
			return $refusal;
		}

		try {
			return new JSONResponse(
				data: $this->reviews->status(fileId: $fileId) + ['gateMode' => $this->reviews->gateMode()]
			);
		} catch (Throwable $e) {
			return $this->failed(message: 'The review state could not be read', fileId: $fileId, exception: $e);
		}

	}//end show()

	/**
	 * Mark one document as reviewed by the signed-in user.
	 *
	 * The reviewer is the session, never the payload: a `checkedBy` in the body is ignored.
	 *
	 * @param int    $fileId The document.
	 * @param string $note   The reviewer's note.
	 * @param bool   $byEye  The reviewer confirms every page was read by eye (needed without a text layer).
	 * @param array<int|string> $decided The relation ids of the uncertain findings the reviewer decided.
	 *
	 * @return JSONResponse The status after the check, or the refusal.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.2
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
	 *
	 * @no-admin-idor-exempt AnonymizeRequestService::verifyFileAccess() refuses a file outside the
	 * caller's user folder before anything is written, and the mark is saved with OpenRegister RBAC on
	 * (documentReview create: filinq-woo-officers, filinq-policy-admins).
	 *
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag) The confirmation is the reviewer's own yes or no.
	 */
	#[NoAdminRequired]
	public function check(int $fileId, string $note = '', bool $byEye = false, array $decided = []): JSONResponse {
		$refusal = $this->refusal(fileId: $fileId);
		if ($refusal !== null) {
			return $refusal;
		}

		try {
			$status = $this->reviews->check(
				fileId: $fileId,
				userId: (string)$this->userSession->getUser()?->getUID(),
				note: trim($note),
				byEye: $byEye,
				decided: array_values(array_filter($decided, 'is_numeric'))
			);
		} catch (DocumentReviewRefusedException $e) {
			return new JSONResponse(
				data: [
					'error' => $this->refusalMessage(reason: $e->getReason()),
					'reason' => $e->getReason(),
				] + $e->getDetails(),
				statusCode: Http::STATUS_CONFLICT
			);
		} catch (Throwable $e) {
			return $this->failed(message: 'The review mark could not be recorded', fileId: $fileId, exception: $e);
		}

		return new JSONResponse(data: $status, statusCode: Http::STATUS_CREATED);

	}//end check()

	/**
	 * Clear one document's reviewed mark.
	 *
	 * @param int $fileId The document.
	 *
	 * @return JSONResponse The status after the uncheck, with `removed`; 403 when the schema
	 *                      does not let the caller remove the mark.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
	 *
	 * @no-admin-idor-exempt AnonymizeRequestService::verifyFileAccess() refuses a file outside the
	 * caller's user folder before the mark is removed.
	 */
	#[NoAdminRequired]
	public function uncheck(int $fileId): JSONResponse {
		$refusal = $this->refusal(fileId: $fileId);
		if ($refusal !== null) {
			return $refusal;
		}

		try {
			$removed = $this->reviews->uncheck(fileId: $fileId);
			return new JSONResponse(data: $this->reviews->status(fileId: $fileId) + ['removed' => $removed]);
		} catch (Throwable $e) {
			if ($e->getCode() === Http::STATUS_FORBIDDEN) {
				// The schema's delete rule refused this caller: a refusal, not a failure.
				return new JSONResponse(
					data: ['error' => $this->l10n->t('You may not remove the reviewed mark of this document.')],
					statusCode: Http::STATUS_FORBIDDEN
				);
			}

			return $this->failed(message: 'The review mark could not be removed', fileId: $fileId, exception: $e);
		}

	}//end uncheck()

	/**
	 * The reviewer's message for a refused check.
	 *
	 * @param string $reason The refusal's reason.
	 *
	 * @return string The translated message.
	 */
	private function refusalMessage(string $reason): string {
		return match ($reason) {
			'no_text_layer' => $this->l10n->t(
				'Detection could not read the pages of this document. Confirm that you read every page by eye before marking it reviewed.'
			),
			'uncertain_undecided' => $this->l10n->t('Decide on every uncertain finding before marking this document reviewed.'),
			default => $this->l10n->t('This document has no extracted findings yet; run detection before marking it reviewed.'),
		};

	}//end refusalMessage()

	/**
	 * The 401 or 404 that stops a request, or null to go on.
	 *
	 * @param int $fileId The document.
	 *
	 * @return JSONResponse|null The refusal.
	 */
	private function refusal(int $fileId): ?JSONResponse {
		if ($this->userSession->getUser() === null) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Not authenticated')],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		$denied = $this->access->verifyFileAccess(fileId: $fileId);
		if ($denied !== null) {
			return new JSONResponse(data: $denied['body'], statusCode: $denied['status']);
		}

		return null;

	}//end refusal()

	/**
	 * Log a failure and answer 500.
	 *
	 * @param string    $message   What failed.
	 * @param int       $fileId    The document.
	 * @param Throwable $exception The failure.
	 *
	 * @return JSONResponse The response.
	 */
	private function failed(string $message, int $fileId, Throwable $exception): JSONResponse {
		$this->logger->error($message, ['fileId' => $fileId, 'exception' => $exception]);

		return new JSONResponse(
			data: ['error' => $this->l10n->t('The review state could not be saved or read. Try again.')],
			statusCode: Http::STATUS_INTERNAL_SERVER_ERROR
		);

	}//end failed()
}//end class
