<?php

/**
 * Legal hold cases: the hold register, placing, extending, retrying and releasing.
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
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.4
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Exception\LegalHoldRefusedException;
use OCA\Filinq\Service\LegalHold\LegalHoldCaseService;
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
 * Every route is open to signed-in users at the framework level and checks
 * hold authority in LegalHoldCaseService (admins plus
 * `legal_hold_authority_groups`, fail closed). The one exception is `status`,
 * which answers anyone who can read the record, without naming the matter.
 */
class LegalHoldCaseController extends Controller {

	/**
	 * HTTP status per refusal.
	 *
	 * @var array<string, int>
	 */
	private const STATUS = [
		LegalHoldRefusedException::REASON_NOT_ALLOWED => Http::STATUS_FORBIDDEN,
		LegalHoldRefusedException::REASON_CONFIG_UNREADABLE => Http::STATUS_FORBIDDEN,
		LegalHoldRefusedException::REASON_NOT_FOUND => Http::STATUS_NOT_FOUND,
		LegalHoldRefusedException::REASON_INVALID => Http::STATUS_BAD_REQUEST,
		LegalHoldRefusedException::REASON_RELEASED => Http::STATUS_CONFLICT,
	];

	/**
	 * Constructor.
	 *
	 * @param string               $appName     The app name.
	 * @param IRequest             $request     The request.
	 * @param LegalHoldCaseService $holds       The hold cases.
	 * @param IUserSession         $userSession The signed-in user.
	 * @param IL10N                $l10n        Translations.
	 * @param LoggerInterface      $logger      For failures that are not refusals.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly LegalHoldCaseService $holds,
		private readonly IUserSession $userSession,
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * The hold register, filtered by status, matter type and custodian.
	 *
	 * @return JSONResponse The cases, or a refusal.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		return $this->answer(
			fn (): array => [
				'results' => $this->holds->list(
					filters: [
						'status' => (string) $this->request->getParam('status', ''),
						'holdType' => (string) $this->request->getParam('holdType', ''),
						'custodian' => (string) $this->request->getParam('custodian', ''),
					],
					userId: $this->userId()
				),
			]
		);

	}//end index()

	/**
	 * One case.
	 *
	 * @param string $id The case uuid.
	 *
	 * @return JSONResponse The case, or a refusal.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
	 */
	#[NoAdminRequired]
	public function show(string $id): JSONResponse {
		return $this->answer(fn (): array => $this->holds->get(uuid: $id, userId: $this->userId()));

	}//end show()

	/**
	 * Open a case and freeze its records.
	 *
	 * @return JSONResponse The case with its placement per record, or a refusal.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.4
	 */
	#[NoAdminRequired]
	public function create(): JSONResponse {
		return $this->answer(fn (): array => $this->holds->place(input: $this->request->getParams(), userId: $this->userId()), Http::STATUS_CREATED);

	}//end create()

	/**
	 * Add records to an active case; only the added ones are frozen.
	 *
	 * @param string $id The case uuid.
	 *
	 * @return JSONResponse The case, or a refusal.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.4
	 */
	#[NoAdminRequired]
	public function addScope(string $id): JSONResponse {
		return $this->answer(fn (): array => $this->holds->addScope(uuid: $id, input: $this->request->getParams(), userId: $this->userId()));

	}//end addScope()

	/**
	 * Retry the records whose freeze or unfreeze failed.
	 *
	 * @param string $id The case uuid.
	 *
	 * @return JSONResponse The case, or a refusal.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.4
	 */
	#[NoAdminRequired]
	public function retry(string $id): JSONResponse {
		return $this->answer(fn (): array => $this->holds->retry(uuid: $id, userId: $this->userId()));

	}//end retry()

	/**
	 * Release a case. The reason is mandatory.
	 *
	 * @param string $id The case uuid.
	 *
	 * @return JSONResponse The released case, or a refusal.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.4
	 */
	#[NoAdminRequired]
	public function release(string $id): JSONResponse {
		return $this->answer(
			fn (): array => $this->holds->release(
				uuid: $id,
				releaseReason: (string) $this->request->getParam('releaseReason', ''),
				userId: $this->userId()
			)
		);

	}//end release()

	/**
	 * Whether a record is held. The matter is named only to hold authority.
	 *
	 * @param string $objectId The record uuid.
	 *
	 * @return JSONResponse {held, cases}, or 404 when the caller cannot read the record.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.2
	 */
	#[NoAdminRequired]
	public function status(string $objectId): JSONResponse {
		return $this->answer(fn (): array => $this->holds->statusFor(ref: $objectId, userId: $this->userId()));

	}//end status()

	/**
	 * Run an action and answer with its result or its refusal.
	 *
	 * @param callable $action     Returns the body.
	 * @param int      $okStatus   The status on success.
	 *
	 * @return JSONResponse The response.
	 */
	private function answer(callable $action, int $okStatus = Http::STATUS_OK): JSONResponse {
		try {
			return new JSONResponse($action(), $okStatus);
		} catch (LegalHoldRefusedException $refusal) {
			return $this->refused(refusal: $refusal);
		} catch (Throwable $e) {
			$this->logger->error(message: '[LegalHoldCaseController] a hold action failed', context: ['error' => $e->getMessage()]);
			return new JSONResponse(['error' => $this->l10n->t('The legal hold could not be saved. Try again later.')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

	}//end answer()

	/**
	 * The response for a refusal.
	 *
	 * @param LegalHoldRefusedException $refusal The refusal.
	 *
	 * @return JSONResponse The response.
	 */
	private function refused(LegalHoldRefusedException $refusal): JSONResponse {
		$reason = $refusal->getReason();
		$messages = [
			LegalHoldRefusedException::REASON_NOT_ALLOWED => $this->l10n->t('You are not allowed to place or release legal holds.'),
			LegalHoldRefusedException::REASON_CONFIG_UNREADABLE => $this->l10n->t('You are not allowed to place or release legal holds.'),
			LegalHoldRefusedException::REASON_NOT_FOUND => $this->l10n->t('Not found'),
			LegalHoldRefusedException::REASON_INVALID => $this->l10n->t('Fill in a name, a matter type, a reason and at least one document or dossier. A release needs a reason.'),
			LegalHoldRefusedException::REASON_RELEASED => $this->l10n->t('This legal hold is released. A released hold is final: open a new one.'),
		];

		return new JSONResponse(
			['error' => ($messages[$reason] ?? $this->l10n->t('The legal hold could not be saved. Try again later.')), 'reason' => $reason],
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
