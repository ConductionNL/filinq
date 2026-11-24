<?php

/**
 * MDTO Controller
 *
 * The MDTO panel and the overbrenging action: a record's completeness
 * verdict, its sidecar download, and its transfer to the e-depot.
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
 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-e-depot-handoff-rides-openregisters-transfer-stack-req-ddtmm-006
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\MdtoTransferService;
use OCA\Filinq\Service\ReadsCurrentUserId;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Reading the verdict is open to anyone who may read the record (OpenRegister
 * decides that). The download and the transfer hand the record's archival
 * metadata out of Filinq, so they are for the people who prepare overbrenging:
 * members of `filinq-policy-admins` and administrators.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-e-depot-handoff-rides-openregisters-transfer-stack-req-ddtmm-006
 */
class MdtoController extends Controller {

	use ReadsCurrentUserId;

	/**
	 * The group that may export and transfer, besides administrators.
	 */
	public const TRANSFER_GROUP = 'filinq-policy-admins';

	/**
	 * Constructor.
	 *
	 * @param string              $appName      The app name.
	 * @param IRequest            $request      The request.
	 * @param MdtoTransferService $transfers    Gate, sidecar and handoff.
	 * @param IUserSession        $userSession  The current user.
	 * @param IGroupManager       $groupManager Group membership.
	 * @param IL10N               $l10n         Translations.
	 * @param LoggerInterface     $logger       Logs failures.
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-e-depot-handoff-rides-openregisters-transfer-stack-req-ddtmm-006
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly MdtoTransferService $transfers,
		private readonly IUserSession $userSession,
		private readonly IGroupManager $groupManager,
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * A record's MDTO verdict, retention block, supplement and proposals.
	 *
	 * @param string $id The record UUID.
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-completeness-validation-gates-overbrenging-req-ddtmm-004
	 */
	#[NoAdminRequired]
	public function status(string $id): JSONResponse {
		try {
			$status = $this->transfers->status(uuid: $id);
		} catch (Throwable $e) {
			return $this->failure(error: $e);
		}

		$status['mayTransfer'] = $this->mayTransfer();

		return new JSONResponse(data: $status);

	}//end status()

	/**
	 * The record's sidecar as a download (`format` xml or json).
	 *
	 * @param string $id The record UUID.
	 *
	 * @return DataDownloadResponse|JSONResponse
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	#[NoAdminRequired]
	public function export(string $id): DataDownloadResponse|JSONResponse {
		if ($this->mayTransfer() === false) {
			return $this->forbidden();
		}

		$format = 'xml';
		if ((string) $this->request->getParam('format', 'xml') === 'json') {
			$format = 'json';
		}

		try {
			$sidecar = $this->transfers->export(uuid: $id, format: $format);
		} catch (Throwable $e) {
			return $this->failure(error: $e);
		}

		return new DataDownloadResponse(data: $sidecar['content'], filename: $sidecar['fileName'], contentType: $sidecar['mimeType']);

	}//end export()

	/**
	 * Transfer the record to the e-depot; a blocked transfer answers 422 with the missing fields.
	 *
	 * @param string $id The record UUID.
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-e-depot-handoff-rides-openregisters-transfer-stack-req-ddtmm-006
	 */
	#[NoAdminRequired]
	public function transfer(string $id): JSONResponse {
		if ($this->mayTransfer() === false) {
			return $this->forbidden();
		}

		try {
			$result = $this->transfers->transfer(uuid: $id);
		} catch (Throwable $e) {
			return $this->failure(error: $e);
		}

		$status = Http::STATUS_OK;
		if ($result['status'] === 'blocked') {
			$status = Http::STATUS_UNPROCESSABLE_ENTITY;
		}

		if ($result['status'] === 'failed') {
			$status = Http::STATUS_BAD_GATEWAY;
		}

		return new JSONResponse(data: $result, statusCode: $status);

	}//end transfer()

	/**
	 * Whether the current user may export and transfer.
	 *
	 * @return bool
	 */
	private function mayTransfer(): bool {
		$uid = $this->currentUserId();
		if ($uid === '') {
			return false;
		}

		return $this->groupManager->isAdmin($uid) === true || $this->groupManager->isInGroup($uid, self::TRANSFER_GROUP) === true;

	}//end mayTransfer()

	/**
	 * The refusal for someone outside the transfer group.
	 *
	 * @return JSONResponse
	 */
	private function forbidden(): JSONResponse {
		return new JSONResponse(
			data: ['error' => $this->l10n->t('Only archive staff may export or transfer a record.')],
			statusCode: Http::STATUS_FORBIDDEN
		);

	}//end forbidden()

	/**
	 * A failure: 404 for an unreadable record, 500 otherwise, never the exception text.
	 *
	 * @param Throwable $error The failure.
	 *
	 * @return JSONResponse
	 */
	private function failure(Throwable $error): JSONResponse {
		if ($error instanceof RuntimeException && $error->getCode() === Http::STATUS_NOT_FOUND) {
			return new JSONResponse(data: ['error' => $this->l10n->t('The record cannot be read.')], statusCode: Http::STATUS_NOT_FOUND);
		}

		$this->logger->error(
			message: '[MdtoController] MDTO request failed',
			context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $error->getMessage()]
		);

		return new JSONResponse(
			data: ['error' => $this->l10n->t('The MDTO metadata could not be prepared.')],
			statusCode: Http::STATUS_INTERNAL_SERVER_ERROR
		);

	}//end failure()
}//end class
