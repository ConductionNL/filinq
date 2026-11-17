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
use OCA\Filinq\Kernel\Contracts\OpenCatalogiPlatformInterface;
use OCA\Filinq\Kernel\Contracts\PublicationAccessInterface;
use OCA\Filinq\Service\Publication\DiwooMissingFields;
use OCA\Filinq\Exception\PublicationNotReadyException;
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
