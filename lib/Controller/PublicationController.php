<?php

/**
 * Publication controller
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

use OCA\Filinq\Service\Publication\PublicationAccess;
use OCA\Filinq\Service\Publication\PublicationNotReadyException;
use OCA\Filinq\Service\Publication\PublicationPipelineService;
use OCA\Filinq\Service\Publication\PublicationStore;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The Woo publication pipeline over HTTP.
 *
 * Every method acts only on a publication whose document the caller can
 * open, or for an admin. The log has no route to change or remove an entry.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/woo-publicatie-pipeline/specs/woo-publicatie-pipeline/spec.md
 */
class PublicationController extends Controller {

	/**
	 * Constructor
	 *
	 * @param string                     $appName  The app name
	 * @param IRequest                   $request  The request
	 * @param PublicationPipelineService $pipeline The pipeline
	 * @param PublicationStore           $store    The records and log
	 * @param PublicationAccess          $access   Who may act
	 * @param IUserSession               $session  The caller
	 * @param LoggerInterface            $logger   Logger
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly PublicationPipelineService $pipeline,
		private readonly PublicationStore $store,
		private readonly PublicationAccess $access,
		private readonly IUserSession $session,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * The publications whose document the caller can open.
	 *
	 * @return JSONResponse {results: record[]}
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		$uid = $this->uid();
		$results = [];
		foreach ($this->store->listRecords() as $record) {
			if ($this->access->mayAct(uid: $uid, fileId: (string) ($record['documentFileRef'] ?? '')) === true) {
				$results[] = $this->pipeline->sync(record: $record);
			}
		}

		return new JSONResponse(['results' => $results, 'platformAvailable' => $this->store->platformAvailable()]);

	}//end index()

	/**
	 * Start a publication for a document the caller can open.
	 *
	 * @return JSONResponse The record, 201
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	#[NoAdminRequired]
	public function create(): JSONResponse {
		$fileId = (string) $this->request->getParam('documentFileRef', '');
		if ($this->access->mayAct(uid: $this->uid(), fileId: $fileId) === false) {
			return $this->forbidden();
		}

		return $this->run(
			fn (): array => $this->pipeline->create(
				documentFileRef: $fileId,
				subjectType: (string) $this->request->getParam('subjectType', 'document'),
				dossierRef: (string) $this->request->getParam('dossierRef', ''),
				actor: $this->uid()
			),
			Http::STATUS_CREATED
		);

	}//end create()

	/**
	 * One publication with its log.
	 *
	 * @param string $id The record
	 *
	 * @return JSONResponse The record with `log`
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	#[NoAdminRequired]
	public function show(string $id): JSONResponse {
		$record = $this->authorised(id: $id);
		if ($record === null) {
			return $this->forbidden();
		}

		$record = $this->pipeline->sync(record: $record);
		$record['log'] = $this->store->logFor(recordUuid: $id);
		$record['platformAvailable'] = $this->store->platformAvailable();

		return new JSONResponse($record);

	}//end show()

	/**
	 * Run the readiness checks again.
	 *
	 * @param string $id The record
	 *
	 * @return JSONResponse The record
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	#[NoAdminRequired]
	public function readiness(string $id): JSONResponse {
		$record = $this->authorised(id: $id);
		if ($record === null) {
			return $this->forbidden();
		}

		return $this->run(fn (): array => $this->pipeline->evaluate(record: $record, actor: $this->uid()));

	}//end readiness()

	/**
	 * Set the Woo metadata.
	 *
	 * @param string $id The record
	 *
	 * @return JSONResponse The record
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	#[NoAdminRequired]
	public function metadata(string $id): JSONResponse {
		$record = $this->authorised(id: $id);
		if ($record === null) {
			return $this->forbidden();
		}

		return $this->run(fn (): array => $this->pipeline->updateMetadata(record: $record, metadata: $this->request->getParams(), actor: $this->uid()));

	}//end metadata()

	/**
	 * Hand the publication to OpenCatalogi.
	 *
	 * @param string $id The record
	 *
	 * @return JSONResponse The record, or 409 with the reasons
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	#[NoAdminRequired]
	public function handoff(string $id): JSONResponse {
		$record = $this->authorised(id: $id);
		if ($record === null) {
			return $this->forbidden();
		}

		return $this->run(fn (): array => $this->pipeline->handoff(record: $record, actor: $this->uid()));

	}//end handoff()

	/**
	 * Withdraw the publication.
	 *
	 * @param string $id The record
	 *
	 * @return JSONResponse The record
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	#[NoAdminRequired]
	public function withdraw(string $id): JSONResponse {
		$record = $this->authorised(id: $id);
		if ($record === null) {
			return $this->forbidden();
		}

		return $this->run(fn (): array => $this->pipeline->withdraw(record: $record, reason: (string) $this->request->getParam('reason', ''), actor: $this->uid()));

	}//end withdraw()

	/**
	 * Record and pass on a destruction date.
	 *
	 * @param string $id The record
	 *
	 * @return JSONResponse The record
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	#[NoAdminRequired]
	public function destructionDate(string $id): JSONResponse {
		$record = $this->authorised(id: $id);
		if ($record === null) {
			return $this->forbidden();
		}

		return $this->run(
			fn (): array => $this->pipeline->setDestructionDate(
				record: $record,
				date: (string) $this->request->getParam('date', ''),
				source: (string) $this->request->getParam('source', ''),
				actor: $this->uid()
			)
		);

	}//end destructionDate()

	/**
	 * The record, when the caller may act on it.
	 *
	 * @param string $id The record
	 *
	 * @return array<string, mixed>|null The record, or null for "not found or not yours".
	 */
	private function authorised(string $id): ?array {
		$record = $this->store->findRecord(uuid: $id);
		if ($record === null || $this->access->mayAct(uid: $this->uid(), fileId: (string) ($record['documentFileRef'] ?? '')) === false) {
			return null;
		}

		return $record;

	}//end authorised()

	/**
	 * Run a pipeline step and answer with its record or its error.
	 *
	 * @param callable $step   The step
	 * @param int      $status The status on success
	 *
	 * @return JSONResponse The answer.
	 */
	private function run(callable $step, int $status = Http::STATUS_OK): JSONResponse {
		try {
			return new JSONResponse($step(), $status);
		} catch (PublicationNotReadyException $e) {
			return new JSONResponse(['error' => $e->getMessage(), 'reasons' => $e->getReasons()], Http::STATUS_CONFLICT);
		} catch (Throwable $e) {
			$code = (int) $e->getCode();
			if ($code < 400 || $code > 599) {
				$this->logger->error('Publication step failed: ' . $e->getMessage(), ['exception' => $e]);
				$code = Http::STATUS_INTERNAL_SERVER_ERROR;
			}

			return new JSONResponse(['error' => $e->getMessage()], $code);
		}

	}//end run()

	/**
	 * The same answer for "not found" and "not yours".
	 *
	 * @return JSONResponse 404.
	 */
	private function forbidden(): JSONResponse {
		return new JSONResponse(['error' => 'Publication not found'], Http::STATUS_NOT_FOUND);

	}//end forbidden()

	/**
	 * The caller's user id.
	 *
	 * @return string The uid, or '' without a session.
	 */
	private function uid(): string {
		return (string) $this->session->getUser()?->getUID();

	}//end uid()
}//end class
