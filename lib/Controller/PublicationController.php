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

use OCA\Filinq\Controller\Support\PublicationFlow;
use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Kernel\Contracts\DownloadAgreementGateInterface;
use OCA\Filinq\Kernel\Contracts\OpenCatalogiPlatformInterface;
use OCA\Filinq\Kernel\Contracts\PublicationAccessInterface;
use OCA\Filinq\Service\Publication\DiwooMissingFields;
use OCA\Filinq\Service\Audit\DocumentActionAudit;
use OCA\Filinq\Exception\PublicationNotReadyException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
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
 * @spec openspec/specs/woo-publicatie-pipeline/spec.md
 */
class PublicationController extends Controller {

	/**
	 * Constructor
	 *
	 * @param IRequest                   $request  The request
	 * @param PublicationFlow $flow The publication pipeline and its store.
	 * @param OpenCatalogiPlatformInterface       $platform Whether OpenCatalogi is there, its categories
	 * @param PublicationAccessInterface          $access   Who may act
	 * @param IUserSession               $session  The caller
	 * @param LoggerInterface            $logger   Logger
	 * @param DownloadAgreementGateInterface|null $agreements The download conditions (REQ-RWB-04)
	 * @param DocumentActionAudit|null            $documentAudit Writes `downloaded` into the served copy's trail (REQ-SCR-007)
	 *
	 * @return void
	 */
	public function __construct(
		IRequest $request,
		private readonly PublicationFlow $flow,
		private readonly OpenCatalogiPlatformInterface $platform,
		private readonly PublicationAccessInterface $access,
		private readonly IUserSession $session,
		private readonly LoggerInterface $logger,
		private readonly ?DownloadAgreementGateInterface $agreements = null,
		private readonly ?DocumentActionAudit $documentAudit = null,
	) {
		parent::__construct(appName: AppIdentity::APP_ID, request: $request);

	}//end __construct()

	/**
	 * The publications whose document the caller can open.
	 *
	 * @return JSONResponse {results: record[]}
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		$uid = $this->uid();
		$onlyIncomplete = filter_var($this->request->getParam('incomplete', false), FILTER_VALIDATE_BOOLEAN);
		$missing = new DiwooMissingFields();
		$results = [];
		foreach ($this->flow->store->listRecords() as $record) {
			if ($this->access->maySee(uid: $uid, fileId: (string) ($record['documentFileRef'] ?? '')) === false) {
				continue;
			}

			$record = $missing->annotate(record: $this->flow->pipeline->sync(record: $record));
			if ($onlyIncomplete === false || $record[DiwooMissingFields::FIELD] !== []) {
				$results[] = $record;
			}
		}

		return new JSONResponse(['results' => $results, 'platformAvailable' => $this->platform->available()]);

	}//end index()

	/**
	 * The publications the caller can see that must be published actively,
	 * each saying whether it is published and what still blocks it.
	 *
	 * @return JSONResponse {results: [{record, published, blockers}], categories: string[]}
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/woo-publicatie-pipeline/spec.md#requirement-documents-that-must-be-published-actively-are-listed-with-what-they-still-lack-req-scr-008
	 */
	#[NoAdminRequired]
	public function active(): JSONResponse {
		if ($this->flow->active === null) {
			return new JSONResponse(['results' => [], 'categories' => []]);
		}

		$uid = $this->uid();
		$records = [];
		foreach ($this->flow->store->listRecords() as $record) {
			if ($this->access->maySee(uid: $uid, fileId: (string) ($record['documentFileRef'] ?? '')) === true) {
				$records[] = $this->flow->pipeline->sync(record: $record);
			}
		}

		return new JSONResponse(
			[
				'results' => $this->flow->active->entries(records: $records),
				'categories' => $this->flow->active->categories(),
			]
		);

	}//end active()

	/**
	 * Download the published copy of a publication, once the caller accepted
	 * the download agreement in force for it.
	 *
	 * The agreement is checked on the published copy and on its source
	 * document; either one carrying terms the caller has not accepted in their
	 * current version answers 409 with the terms to show, and no file is served.
	 *
	 * @param string $id The record
	 *
	 * @return Response The file, 404 when not visible or not published, 409 with the agreement to accept
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.3
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-3.3
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function download(string $id): Response {
		$record = $this->readable(id: $id);
		$copy = $this->publishedCopy(record: $record);
		if ($record === null || $copy === '') {
			return $this->forbidden();
		}

		if ($this->agreements === null) {
			return new JSONResponse(['error' => 'Downloads are not available'], Http::STATUS_SERVICE_UNAVAILABLE);
		}

		$uid = $this->uid();
		$unaccepted = $this->unacceptedAgreement(agreements: $this->agreements, documents: [$copy, (string) ($record['documentFileRef'] ?? '')], uid: $uid);
		if ($unaccepted !== null) {
			return $unaccepted;
		}

		try {
			$file = $this->platform->readCopy(fileId: $copy, actor: $uid);
			$response = new DataDownloadResponse($file->getContent(), $file->getName(), $file->getMimeType());

			// The bytes are served: the download enters the copy's trail (REQ-SCR-007).
			$this->documentAudit?->record(fileId: (int) $copy, action: 'downloaded', context: ['publication' => $id]);

			return $response;
		} catch (Throwable $e) {
			$this->logger->warning('[PublicationController] the published copy could not be read', ['record' => $id, 'error' => $e->getMessage()]);

			return $this->forbidden();
		}

	}//end download()

	/**
	 * The published copy of a record that has been handed off or published, or '' when there is none.
	 *
	 * @param array<string, mixed>|null $record The record, or null when not visible
	 *
	 * @return string The published copy's file reference, or ''
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-3.3
	 */
	private function publishedCopy(?array $record): string {
		if ($record === null || in_array($record['status'] ?? '', ['handed_off', 'published'], true) === false) {
			return '';
		}

		return (string) ($record['redactedFileRef'] ?? '');

	}//end publishedCopy()

	/**
	 * The 409 answer for the first document whose download agreement the caller has not accepted, or null.
	 *
	 * @param DownloadAgreementGateInterface $agreements The download conditions
	 * @param list<string>                   $documents  The published copy and its source document
	 * @param string                         $uid        The caller
	 *
	 * @return JSONResponse|null 409 with the agreement to accept, or null when every agreement is accepted
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-3.3
	 */
	private function unacceptedAgreement(DownloadAgreementGateInterface $agreements, array $documents, string $uid): ?JSONResponse {
		foreach (array_unique($documents) as $document) {
			if ($document === '') {
				continue;
			}

			$decision = $agreements->check(document: $document, person: $uid);
			if (($decision['mayDownload'] ?? false) !== true) {
				return new JSONResponse($decision + ['document' => $document], Http::STATUS_CONFLICT);
			}
		}

		return null;

	}//end unacceptedAgreement()

	/**
	 * The Woo information categories to choose from.
	 *
	 * @return JSONResponse {results: [{code, label}]}
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
	 */
	#[NoAdminRequired]
	public function categories(): JSONResponse {
		return new JSONResponse(['results' => $this->platform->categories()]);

	}//end categories()

	/**
	 * Start a publication for a document the caller can open.
	 *
	 * @return JSONResponse The record, 201
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	#[NoAdminRequired]
	public function create(): JSONResponse {
		$fileId = (string) $this->request->getParam('documentFileRef', '');
		if ($this->access->mayAct(uid: $this->uid(), fileId: $fileId) === false) {
			return $this->forbidden();
		}

		return $this->run(
			step: fn (): array => $this->flow->pipeline->create(
				documentFileRef: $fileId,
				subjectType: (string) $this->request->getParam('subjectType', 'document'),
				dossierRef: (string) $this->request->getParam('dossierRef', ''),
				actor: $this->uid()
			),
			status: Http::STATUS_CREATED
		);

	}//end create()

	/**
	 * One publication with its log.
	 *
	 * @param string $id The record
	 *
	 * @return JSONResponse The record with `log`
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	#[NoAdminRequired]
	public function show(string $id): JSONResponse {
		$record = $this->readable(id: $id);
		if ($record === null) {
			return $this->forbidden();
		}

		$record = (new DiwooMissingFields())->annotate(record: $this->flow->pipeline->sync(record: $record));
		$record['log'] = $this->flow->store->logFor(recordUuid: $id);
		$record['platformAvailable'] = $this->platform->available();

		return new JSONResponse($record);

	}//end show()

	/**
	 * Run the readiness checks again.
	 *
	 * @param string $id The record
	 *
	 * @return JSONResponse The record
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	#[NoAdminRequired]
	public function readiness(string $id): JSONResponse {
		$record = $this->readable(id: $id);
		if ($record === null) {
			return $this->forbidden();
		}

		return $this->run(step: fn (): array => $this->flow->pipeline->evaluate(record: $record, actor: $this->uid()));

	}//end readiness()

	/**
	 * Set the Woo metadata.
	 *
	 * @param string $id The record
	 *
	 * @return JSONResponse The record
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	#[NoAdminRequired]
	public function metadata(string $id): JSONResponse {
		$record = $this->authorised(id: $id);
		if ($record === null) {
			return $this->forbidden();
		}

		$metadata = $this->request->getParams();

		return $this->run(step: fn (): array => $this->flow->pipeline->updateMetadata(record: $record, metadata: $metadata, actor: $this->uid()));

	}//end metadata()

	/**
	 * Set one metadata value on a selection of records.
	 *
	 * Every record is checked on its own: one the caller may not act on is
	 * reported as skipped, never changed.
	 *
	 * @return JSONResponse `{updated, skipped}`
	 *
	 * @spec openspec/changes/woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-a-coordinator-fills-one-value-on-a-selection-req-wmw-002
	 */
	#[NoAdminRequired]
	public function bulkMetadata(): JSONResponse {
		$ids = $this->request->getParam('ids', []);
		$values = $this->request->getParam('values', []);
		if (is_array($ids) === false || $ids === [] || is_array($values) === false) {
			return new JSONResponse(['error' => 'Select at least one publication and name the values to set'], Http::STATUS_BAD_REQUEST);
		}

		$records = [];
		$refused = [];
		foreach (array_unique(array_map('strval', $ids)) as $id) {
			$record = $this->authorised(id: $id);
			if ($record === null) {
				$refused[] = ['uuid' => $id, 'reason' => 'not found'];
				continue;
			}

			$records[] = $record;
		}

		return $this->run(
			step: function () use ($records, $values, $refused): array {
				$outcome = $this->flow->pipeline->updateMetadataMany(records: $records, values: $values, actor: $this->uid());
				$outcome['skipped'] = array_merge($refused, $outcome['skipped']);

				return $outcome;
			}
		);

	}//end bulkMetadata()

	/**
	 * Hand the publication to OpenCatalogi.
	 *
	 * @param string $id The record
	 *
	 * @return JSONResponse The record, or 409 with the reasons
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	#[NoAdminRequired]
	public function handoff(string $id): JSONResponse {
		$record = $this->authorised(id: $id);
		if ($record === null) {
			return $this->forbidden();
		}

		return $this->run(step: fn (): array => $this->flow->pipeline->handoff(record: $record, actor: $this->uid()));

	}//end handoff()

	/**
	 * Withdraw the publication.
	 *
	 * @param string $id The record
	 *
	 * @return JSONResponse The record
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	#[NoAdminRequired]
	public function withdraw(string $id): JSONResponse {
		$record = $this->authorised(id: $id);
		if ($record === null) {
			return $this->forbidden();
		}

		$reason = (string) $this->request->getParam('reason', '');

		return $this->run(step: fn (): array => $this->flow->pipeline->withdraw(record: $record, reason: $reason, actor: $this->uid()));

	}//end withdraw()

	/**
	 * Record and pass on a destruction date.
	 *
	 * @param string $id The record
	 *
	 * @return JSONResponse The record
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	#[NoAdminRequired]
	public function destructionDate(string $id): JSONResponse {
		$record = $this->authorised(id: $id);
		if ($record === null) {
			return $this->forbidden();
		}

		return $this->run(
			step: fn (): array => $this->flow->pipeline->setDestructionDate(
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
		$record = $this->flow->store->findRecord(uuid: $id);
		if ($record === null || $this->access->mayAct(uid: $this->uid(), fileId: (string) ($record['documentFileRef'] ?? '')) === false) {
			return null;
		}

		return $record;

	}//end authorised()

	/**
	 * The record, when the caller may read it.
	 *
	 * @param string $id The record
	 *
	 * @return array<string, mixed>|null The record, or null for "not found or not yours".
	 */
	private function readable(string $id): ?array {
		$record = $this->flow->store->findRecord(uuid: $id);
		if ($record === null || $this->access->maySee(uid: $this->uid(), fileId: (string) ($record['documentFileRef'] ?? '')) === false) {
			return null;
		}

		return $record;

	}//end readable()

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
