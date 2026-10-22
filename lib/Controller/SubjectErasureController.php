<?php

/**
 * Subject erasure: place a request, preview it, exclude, run, and read the certificate.
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
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Exception\SubjectErasureRefusedException;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureRun;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureService;
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
 * Every route is open to signed-in users at the framework level and checks the
 * erasure authority first, in the services (admins plus
 * `subject_erasure_groups`, fail closed), recording every denial.
 */
class SubjectErasureController extends Controller {

	/**
	 * HTTP status per refusal.
	 *
	 * @var array<string, int>
	 */
	private const STATUS = [
		SubjectErasureRefusedException::REASON_NOT_ALLOWED => Http::STATUS_FORBIDDEN,
		SubjectErasureRefusedException::REASON_CONFIG_UNREADABLE => Http::STATUS_FORBIDDEN,
		SubjectErasureRefusedException::REASON_NOT_FOUND => Http::STATUS_NOT_FOUND,
		SubjectErasureRefusedException::REASON_INVALID => Http::STATUS_BAD_REQUEST,
		SubjectErasureRefusedException::REASON_WRONG_STATE => Http::STATUS_CONFLICT,
		SubjectErasureRefusedException::REASON_CATALOGUE_UNAVAILABLE => Http::STATUS_SERVICE_UNAVAILABLE,
		SubjectErasureRefusedException::REASON_AUDIT_UNAVAILABLE => Http::STATUS_SERVICE_UNAVAILABLE,
	];

	/**
	 * Constructor.
	 *
	 * @param string                $appName     The app name.
	 * @param IRequest              $request     The request.
	 * @param SubjectErasureService $requests    The request lifecycle.
	 * @param SubjectErasureRun     $runs        The run and the certificate.
	 * @param IUserSession          $userSession The signed-in user.
	 * @param IL10N                 $l10n        Translations.
	 * @param LoggerInterface       $logger      For failures that are not refusals.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly SubjectErasureService $requests,
		private readonly SubjectErasureRun $runs,
		private readonly IUserSession $userSession,
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Every request.
	 *
	 * @return JSONResponse The requests, or a refusal.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.1
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		return $this->answer(action: fn (): array => ['results' => $this->requests->list(userId: $this->userId())]);

	}//end index()

	/**
	 * One request.
	 *
	 * @param string $id The request uuid.
	 *
	 * @return JSONResponse The request, or a refusal.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.1
	 */
	#[NoAdminRequired]
	public function show(string $id): JSONResponse {
		return $this->answer(action: fn (): array => $this->requests->get(uuid: $id, userId: $this->userId()));

	}//end show()

	/**
	 * Place a request.
	 *
	 * @return JSONResponse The request, or a refusal.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-1.1
	 */
	#[NoAdminRequired]
	public function create(): JSONResponse {
		return $this->answer(
			action: fn (): array => $this->requests->create(input: $this->request->getParams(), userId: $this->userId()),
			okStatus: Http::STATUS_CREATED
		);

	}//end create()

	/**
	 * Build the preview.
	 *
	 * @param string $id The request uuid.
	 *
	 * @return JSONResponse The preview, or a refusal.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.1
	 */
	#[NoAdminRequired]
	public function preview(string $id): JSONResponse {
		return $this->answer(action: fn (): array => $this->requests->preview(uuid: $id, userId: $this->userId()));

	}//end preview()

	/**
	 * Record the exclusions.
	 *
	 * @param string $id The request uuid.
	 *
	 * @return JSONResponse The request, or a refusal.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.2
	 */
	#[NoAdminRequired]
	public function exclude(string $id): JSONResponse {
		return $this->answer(
			action: fn (): array => $this->requests->exclude(
				uuid: $id,
				exclusions: (array) $this->request->getParam('exclusions', []),
				userId: $this->userId()
			)
		);

	}//end exclude()

	/**
	 * Run or resume the erasure.
	 *
	 * @param string $id The request uuid.
	 *
	 * @return JSONResponse The request and the certificate, or a refusal.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-3.1
	 */
	#[NoAdminRequired]
	public function run(string $id): JSONResponse {
		return $this->answer(action: fn (): array => $this->runs->run(uuid: $id, userId: $this->userId()));

	}//end run()

	/**
	 * The certificate of a completed request.
	 *
	 * @param string $id The request uuid.
	 *
	 * @return JSONResponse The certificate, or a refusal.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-4.2
	 */
	#[NoAdminRequired]
	public function certificate(string $id): JSONResponse {
		return $this->answer(action: fn (): array => $this->runs->certificate(uuid: $id, userId: $this->userId()));

	}//end certificate()

	/**
	 * Run an action and answer with its result or its refusal.
	 *
	 * @param callable $action   Returns the body.
	 * @param int      $okStatus The status on success.
	 *
	 * @return JSONResponse The response.
	 */
	private function answer(callable $action, int $okStatus = Http::STATUS_OK): JSONResponse {
		try {
			return new JSONResponse($action(), $okStatus);
		} catch (SubjectErasureRefusedException $refusal) {
			return $this->refused(refusal: $refusal);
		} catch (Throwable $e) {
			$this->logger->error(message: '[SubjectErasureController] an erasure step failed', context: ['error' => $e->getMessage()]);
			return new JSONResponse(
				['error' => $this->l10n->t('The erasure step failed. Nothing further was changed. Try again later.')],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

	}//end answer()

	/**
	 * The response for a refusal.
	 *
	 * @param SubjectErasureRefusedException $refusal The refusal.
	 *
	 * @return JSONResponse The response.
	 */
	private function refused(SubjectErasureRefusedException $refusal): JSONResponse {
		$reason = $refusal->getReason();
		$messages = [
			SubjectErasureRefusedException::REASON_NOT_ALLOWED => $this->l10n->t('You are not allowed to handle erasure requests.'),
			SubjectErasureRefusedException::REASON_CONFIG_UNREADABLE => $this->l10n->t('You are not allowed to handle erasure requests.'),
			SubjectErasureRefusedException::REASON_NOT_FOUND => $this->l10n->t('Not found'),
			SubjectErasureRefusedException::REASON_INVALID => $this->l10n->t('Fill in the person and the legal ground. Every exclusion needs a reason.'),
			SubjectErasureRefusedException::REASON_WRONG_STATE => $this->l10n->t(
				'This step is not possible now. Build the preview first; a completed request cannot run again.'
			),
			SubjectErasureRefusedException::REASON_CATALOGUE_UNAVAILABLE => $this->l10n->t(
				'The entity catalogue cannot be read, so nobody can say where the person appears. Nothing was changed.'
			),
			SubjectErasureRefusedException::REASON_AUDIT_UNAVAILABLE => $this->l10n->t('The audit trail could not record this step, so it was not done.'),
		];

		return new JSONResponse(
			['error' => ($messages[$reason] ?? $this->l10n->t('The erasure step failed. Nothing further was changed. Try again later.')), 'reason' => $reason],
			(self::STATUS[$reason] ?? Http::STATUS_INTERNAL_SERVER_ERROR)
		);

	}//end refused()

	/**
	 * The signed-in user's id, '' when nobody is.
	 *
	 * @return string The user id.
	 */
	private function userId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return '';
		}

		return $user->getUID();

	}//end userId()
}//end class
