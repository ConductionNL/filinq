<?php

/**
 * Bridge Controller
 *
 * The API of the ZGW document bridge: sources with their health, staged
 * documents, dossier pick-up, and release and retry of the write-back.
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
 * @spec openspec/changes/zgw-document-bridge
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use InvalidArgumentException;
use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Service\BridgeDossierPickUp;
use OCA\Filinq\Service\BridgeService;
use OCA\Filinq\Service\BridgeSourceBadges;
use OCA\Filinq\Settings\FilinqAdmin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The bridge endpoints under `api/bridge/*`.
 *
 * The sources and their health are admin settings. Everything about a
 * document is read and written with the caller's own OpenRegister access:
 * the listing is RBAC-scoped, a document the caller cannot read answers 404,
 * a save the caller may not make is refused by OpenRegister, and the dossier
 * folder and the staged files are resolved under the caller's own view.
 *
 * @spec openspec/changes/zgw-document-bridge
 */
class BridgeController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param IRequest            $request The request.
	 * @param BridgeService       $bridge  The bridge register.
	 * @param BridgeDossierPickUp $pickUp  Attaches staged documents to a dossier.
	 * @param LoggerInterface     $logger  Logs an unexpected failure.
	 * @param BridgeSourceBadges|null $badges The source badge per file, for a listing page.
	 *
	 * @return void
	 */
	public function __construct(
		IRequest $request,
		private readonly BridgeService $bridge,
		private readonly BridgeDossierPickUp $pickUp,
		private readonly LoggerInterface $logger,
		private readonly ?BridgeSourceBadges $badges = null,
	) {
		parent::__construct(appName: AppIdentity::APP_ID, request: $request);

	}//end __construct()

	/**
	 * GET /api/bridge/sources
	 *
	 * @return JSONResponse `{results: [...source, health]}`.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function sources(): JSONResponse {
		return $this->answer(work: fn (): array => ['results' => $this->bridge->sources()]);

	}//end sources()

	/**
	 * GET /api/bridge/external-documents?sourceId=
	 *
	 * @return JSONResponse `{results: [...]}`, the staged documents the caller may read.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	#[NoAdminRequired]
	public function externalDocuments(): JSONResponse {
		$sourceId = trim((string)$this->request->getParam('sourceId', ''));

		return $this->answer(work: fn (): array => ['results' => $this->bridge->stagedDocuments(sourceId: $sourceId)]);

	}//end externalDocuments()

	/**
	 * GET /api/bridge/badges?fileIds=1,2,3
	 *
	 * One lookup per listing page: the case-system vendor of each listed file
	 * that came in through the bridge, for the source badge.
	 *
	 * @return JSONResponse `{badges: {fileId: vendor}}`, only for files the caller can open.
	 *
	 * @spec openspec/changes/zgw-document-bridge/tasks.md#3-2
	 */
	#[NoAdminRequired]
	public function badges(): JSONResponse {
		$ids = $this->request->getParam('fileIds', '');
		if (is_array($ids) === false) {
			$ids = explode(',', (string)$ids);
		}

		return $this->answer(
			work: fn (): array => ['badges' => (object)($this->badges?->forFiles(fileIds: $ids) ?? [])]
		);

	}//end badges()

	/**
	 * POST /api/bridge/dossiers/{dossierId}/attach with `documentIds`.
	 *
	 * @param string $dossierId The dossier object's uuid.
	 *
	 * @return JSONResponse `{results: [...]}` the attached documents, 400 for a refusal, 404 for a missing dossier or document.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	#[NoAdminRequired]
	public function attach(string $dossierId): JSONResponse {
		$documentIds = $this->request->getParam('documentIds', []);
		if (is_array($documentIds) === false) {
			$documentIds = [$documentIds];
		}

		return $this->answer(
			work: fn (): array => ['results' => $this->pickUp->attach(dossierUuid: $dossierId, documentIds: array_values($documentIds))]
		);

	}//end attach()

	/**
	 * POST /api/bridge/external-documents/{id}/release
	 *
	 * @param string $id The external document's uuid.
	 *
	 * @return JSONResponse The saved document, 400 for a refusal, 404 when not found.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	#[NoAdminRequired]
	public function release(string $id): JSONResponse {
		return $this->answer(work: fn (): array => $this->bridge->release(id: $id));

	}//end release()

	/**
	 * POST /api/bridge/external-documents/{id}/retry
	 *
	 * @param string $id The external document's uuid.
	 *
	 * @return JSONResponse The saved document, 400 for a refusal, 404 when not found.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	#[NoAdminRequired]
	public function retry(string $id): JSONResponse {
		return $this->answer(work: fn (): array => $this->bridge->retry(id: $id));

	}//end retry()

	/**
	 * Run the work: its result, a refusal as 400, a missing object as 404, anything else as 500 without details.
	 *
	 * @param callable $work Returns the response body.
	 *
	 * @return JSONResponse
	 */
	private function answer(callable $work): JSONResponse {
		try {
			return new JSONResponse(data: $work());
		} catch (InvalidArgumentException $e) {
			return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: Http::STATUS_BAD_REQUEST);
		} catch (Throwable $e) {
			if ($e->getCode() === Http::STATUS_NOT_FOUND) {
				return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: Http::STATUS_NOT_FOUND);
			}

			$this->logger->error(message: '[BridgeController] request failed', context: ['exception' => $e->getMessage()]);

			return new JSONResponse(data: ['error' => 'The bridge request failed.'], statusCode: Http::STATUS_INTERNAL_SERVER_ERROR);
		}

	}//end answer()
}//end class
