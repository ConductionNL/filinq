<?php

/**
 * Case System File Status Controller
 *
 * Tells the My Documents listing which of its files are in the case system.
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
 *
 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Kernel\Contracts\AnonymizeRequestServiceInterface;
use OCA\Filinq\Service\CaseSystemDeliveredFiles;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * The batched "In the case system" lookup behind the My Documents badge.
 *
 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
 */
class CaseSystemFileStatusController extends Controller {
	use AnswersNotAuthenticated;

	/**
	 * The most files one lookup may ask about: one page of the listing.
	 */
	private const MAX_FILE_IDS = 100;

	/**
	 * Constructor.
	 *
	 * @param string                           $appName     The app name.
	 * @param IRequest                         $request     The request.
	 * @param CaseSystemDeliveredFiles         $deliveries  Reads the written-back deliveries.
	 * @param AnonymizeRequestServiceInterface $access      Checks the caller can open each file.
	 * @param IUserSession                     $userSession The session.
	 * @param IL10N                            $l10n        Translations.
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly CaseSystemDeliveredFiles $deliveries,
		private readonly AnonymizeRequestServiceInterface $access,
		private readonly IUserSession $userSession,
		private readonly IL10N $l10n,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Which of the listed files the caller can open are in the case system.
	 *
	 * A file the caller cannot open is left out, the same as one never
	 * delivered, so the answer says nothing about files outside their reach.
	 *
	 * @param string $fileIds Comma separated file ids.
	 *
	 * @return JSONResponse `{delivered: [fileId, ...]}`, 400 for too many ids, 401 without a session.
	 *
	 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
	 *
	 * @no-admin-idor-exempt every requested id passes AnonymizeRequestService::verifyFileAccess() (the
	 * caller's user folder) before it is looked up; ids the caller cannot read are dropped.
	 */
	#[NoAdminRequired]
	public function index(string $fileIds = ''): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return $this->notAuthenticated(message: $this->l10n->t('Not authenticated'));
		}

		$ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $fileIds)))));
		if (count($ids) > self::MAX_FILE_IDS) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Ask about at most %s documents at a time.', [self::MAX_FILE_IDS])],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		$readable = array_values(
			array_filter(
				$ids,
				fn (int $id): bool => $this->access->verifyFileAccess(fileId: $id) === null
			)
		);

		return new JSONResponse(data: ['delivered' => $this->deliveries->deliveredFileIds(fileIds: $readable)]);

	}//end index()
}//end class
