<?php

/**
 * The PDF/A conformance check on a document: read the stored reports, or
 * run veraPDF and store a new one.
 *
 * A file is looked up in the caller's own folder only; one they cannot
 * open is a 404 with the same body as one that does not exist.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Exception\VeraPdfException;
use OCA\Filinq\Service\VeraPdf\ConformanceService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * GET and POST api/validation/conformance/{fileId}.
 */
class ConformanceController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string             $appName     The app id.
	 * @param IRequest           $request     The request.
	 * @param ConformanceService $conformance The conformance check.
	 * @param IRootFolder        $rootFolder  The file tree.
	 * @param IUserSession       $userSession The session.
	 * @param IL10N              $l10n        Translations.
	 * @param LoggerInterface    $logger      The log.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly ConformanceService $conformance,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * The stored reports for a file, and whether a new check can run.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return JSONResponse {available, reports: {file?, conversionOutput?}}, or 404.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.3
	 */
	#[NoAdminRequired]
	public function show(int $fileId): JSONResponse {
		$file = $this->ownFile(fileId: $fileId);
		if ($file === null) {
			return $this->notFound();
		}

		try {
			$reports = $this->conformance->reportsFor(fileId: $fileId);
		} catch (Throwable $e) {
			$this->logger->error(message: '[ConformanceController] reports could not be read', context: ['fileId' => $fileId, 'error' => $e->getMessage()]);
			return new JSONResponse(['error' => $this->l10n->t('The conformance report could not be read. Try again later.')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		return new JSONResponse(['available' => $this->conformance->isAvailable(), 'reports' => (object) $reports]);

	}//end show()

	/**
	 * Run veraPDF on a file and store the report.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return JSONResponse The report; 400 for a file that is not a PDF,
	 *                      404 for one the caller cannot open, 503 when
	 *                      veraPDF gives no verdict.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.3
	 */
	#[NoAdminRequired]
	public function check(int $fileId): JSONResponse {
		$file = $this->ownFile(fileId: $fileId);
		if ($file === null) {
			return $this->notFound();
		}

		if ($file->getMimetype() !== 'application/pdf') {
			return new JSONResponse(['error' => $this->l10n->t('Only a PDF can be checked against the PDF/A standard.')], Http::STATUS_BAD_REQUEST);
		}

		try {
			$report = $this->conformance->checkFile(file: $file, trigger: ConformanceService::TRIGGER_MANUAL);
		} catch (VeraPdfException $e) {
			$this->logger->warning(message: '[ConformanceController] no verdict', context: ['fileId' => $fileId, 'reason' => $e->getReason(), 'error' => $e->getMessage()]);
			return new JSONResponse(['reason' => $e->getReason(), 'error' => $this->unavailableMessage(reason: $e->getReason())], Http::STATUS_SERVICE_UNAVAILABLE);
		} catch (Throwable $e) {
			$this->logger->error(message: '[ConformanceController] the report could not be stored', context: ['fileId' => $fileId, 'error' => $e->getMessage()]);
			return new JSONResponse(['error' => $this->l10n->t('The check ran, but its report could not be stored. Try again later.')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		return new JSONResponse(['report' => $report]);

	}//end check()

	/**
	 * The file with this id in the caller's own folder.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return File|null The file, or null when there is no caller or no such file for them.
	 */
	private function ownFile(int $fileId): ?File {
		$user = $this->userSession->getUser();
		if ($user === null || $fileId <= 0) {
			return null;
		}

		try {
			$nodes = $this->rootFolder->getUserFolder($user->getUID())->getById($fileId);
		} catch (Throwable) {
			return null;
		}

		$node = ($nodes[0] ?? null);

		return ($node instanceof File) ? $node : null;

	}//end ownFile()

	/**
	 * The 404 every unresolvable file gets.
	 *
	 * @return JSONResponse The response.
	 */
	private function notFound(): JSONResponse {
		return new JSONResponse(['error' => $this->l10n->t('File not found')], Http::STATUS_NOT_FOUND);

	}//end notFound()

	/**
	 * Why no verdict came, for the person who asked.
	 *
	 * @param string $reason A VeraPdfException reason.
	 *
	 * @return string The sentence.
	 */
	private function unavailableMessage(string $reason): string {
		return match ($reason) {
			VeraPdfException::REASON_UNAVAILABLE => $this->l10n->t('The PDF/A validator is not installed on the server, so this document was not checked.'),
			VeraPdfException::REASON_TIMEOUT => $this->l10n->t('The PDF/A validator took too long and was stopped, so this document was not checked.'),
			default => $this->l10n->t('The PDF/A validator could not read this document, so it was not checked.'),
		};

	}//end unavailableMessage()
}//end class
