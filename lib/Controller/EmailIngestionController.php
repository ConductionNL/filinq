<?php

/**
 * Email ingestion controller
 *
 * The status list of filed and failed emails, a re-scan by hand, the retry
 * of a conversion, and the inbox to dossier mapping. Admins and delegated
 * admins of the filinq settings only: the records name senders and
 * recipients, and the mapping decides which dossier receives which mail.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/email-ingestion/tasks.md#2-5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use InvalidArgumentException;
use OCA\Filinq\Service\EmailIngestion\EmailDocumentRepository;
use OCA\Filinq\Service\EmailIngestion\EmailIngestionService;
use OCA\Filinq\Service\EmailIngestion\EmailIngestionSettings;
use OCA\Filinq\Settings\FilinqAdmin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * api/email-ingestion.
 */
class EmailIngestionController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string                  $appName    The app id.
	 * @param IRequest                $request    The request.
	 * @param EmailIngestionService   $ingestion  The ingestion.
	 * @param EmailDocumentRepository $repository The records.
	 * @param EmailIngestionSettings  $settings   The mapping.
	 * @param LoggerInterface         $logger     The logger.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly EmailIngestionService $ingestion,
		private readonly EmailDocumentRepository $repository,
		private readonly EmailIngestionSettings $settings,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * The ingested emails, newest first, filtered by status and dossier.
	 *
	 * @param string $status  filed or failed; '' for all.
	 * @param string $dossier A dossier; '' for all.
	 *
	 * @return JSONResponse {results}.
	 *
	 * @spec openspec/changes/email-ingestion/tasks.md#3-1
	 */
	#[AuthorizedAdminSetting(settings: FilinqAdmin::class)]
	public function index(string $status = '', string $dossier = ''): JSONResponse {
		$filters = array_filter(['status' => $status, 'dossierRef' => $dossier], static fn (string $value): bool => $value !== '');

		return $this->answer(
			action: function () use ($filters): array {
				$rows = $this->repository->search(filters: $filters);
				usort($rows, static fn (array $left, array $right): int => strcmp((string) ($right['ingestedAt'] ?? ''), (string) ($left['ingestedAt'] ?? '')));

				return ['results' => $rows];
			}
		);

	}//end index()

	/**
	 * Scan the inboxes now, with the job's budget.
	 *
	 * @return JSONResponse {processed, filed, failed, duplicates}.
	 *
	 * @spec openspec/changes/email-ingestion/tasks.md#2-5
	 */
	#[AuthorizedAdminSetting(settings: FilinqAdmin::class)]
	public function scan(): JSONResponse {
		return $this->answer(action: fn (): array => $this->ingestion->scan(limit: $this->settings->filesPerTick(), source: EmailIngestionService::SOURCE_MANUAL));

	}//end scan()

	/**
	 * Convert a filed email that was not converted.
	 *
	 * @param string $uuid The record.
	 *
	 * @return JSONResponse The record, 404 or 409.
	 *
	 * @spec openspec/changes/email-ingestion/tasks.md#2-5
	 */
	#[AuthorizedAdminSetting(settings: FilinqAdmin::class)]
	public function retry(string $uuid): JSONResponse {
		return $this->answer(action: fn (): array => $this->ingestion->retryConversion(uuid: $uuid));

	}//end retry()

	/**
	 * The inbox mapping and the per-tick budget.
	 *
	 * @return JSONResponse {inboxes, filesPerTick}.
	 *
	 * @spec openspec/changes/email-ingestion/tasks.md#2-5
	 */
	#[AuthorizedAdminSetting(settings: FilinqAdmin::class)]
	public function settings(): JSONResponse {
		return $this->answer(action: fn (): array => $this->settings->toArray());

	}//end settings()

	/**
	 * Store the inbox mapping and the per-tick budget.
	 *
	 * @param array<int, mixed> $inboxes      Rows of {folderId, dossierRef}.
	 * @param int               $filesPerTick The budget.
	 *
	 * @return JSONResponse The stored settings, or 400.
	 *
	 * @spec openspec/changes/email-ingestion/tasks.md#2-5
	 */
	#[AuthorizedAdminSetting(settings: FilinqAdmin::class)]
	public function updateSettings(array $inboxes = [], int $filesPerTick = EmailIngestionSettings::DEFAULT_FILES_PER_TICK): JSONResponse {
		return $this->answer(action: fn (): array => $this->settings->update(inboxes: $inboxes, filesPerTick: $filesPerTick));

	}//end updateSettings()

	/**
	 * Run an action; a refusal keeps its status, anything else is a 500 with a generic body.
	 *
	 * @param callable $action The action.
	 *
	 * @return JSONResponse The answer.
	 */
	private function answer(callable $action): JSONResponse {
		try {
			return new JSONResponse(data: $action());
		} catch (InvalidArgumentException $e) {
			$status = $e->getCode();
			if (in_array($status, [400, 404, 409], true) === false) {
				$status = Http::STATUS_INTERNAL_SERVER_ERROR;
			}

			return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: $status);
		} catch (Throwable $e) {
			$this->logger->error('[EmailIngestionController] request failed', ['exception' => $e->getMessage()]);

			return new JSONResponse(data: ['error' => 'failed'], statusCode: Http::STATUS_INTERNAL_SERVER_ERROR);
		}

	}//end answer()
}//end class
