<?php

/**
 * OCR controller
 *
 * Runs the local OCR engine on a file the caller can open, and reports a
 * file's last OCR result. The response carries the text length, never the
 * text.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\Ocr\OcrResultRepository;
use OCA\Filinq\Service\Ocr\OcrRunService;
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
 * POST api/ocr/{fileId}, GET api/ocr/{fileId} and GET api/ocr.
 */
class OcrController extends Controller {

	/**
	 * The most file ids one status request may ask about.
	 *
	 * @var int
	 */
	private const MAX_STATUS_FILES = 200;

	/**
	 * Constructor.
	 *
	 * @param string $appName The app id.
	 * @param IRequest $request The request.
	 * @param OcrRunService $runs Runs OCR and records the result.
	 * @param OcrResultRepository $results The ocrResult rows.
	 * @param IRootFolder $rootFolder Resolves files in the caller's own folder.
	 * @param IUserSession $userSession The caller.
	 * @param IL10N $l10n Translations.
	 * @param LoggerInterface $logger The logger.
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly OcrRunService $runs,
		private readonly OcrResultRepository $results,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Run OCR on a file the caller can open.
	 *
	 * 200 with the result; 409 when an admin switched OCR off; 503 when
	 * Tesseract is not installed; 404 for a file the caller cannot open; 400
	 * for a type OCR does not read.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return JSONResponse The result, never the text.
	 *
	 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-2.1
	 */
	#[NoAdminRequired]
	public function run(int $fileId): JSONResponse {
		$file = $this->requireReadableFile(fileId: $fileId);
		if ($file === null) {
			return $this->notFound();
		}

		try {
			$outcome = $this->runs->run(file: $file, trigger: 'manual');
		} catch (Throwable $e) {
			$this->logger->error('OCR result could not be stored', ['fileId' => $fileId, 'exception' => $e->getMessage()]);
			return new JSONResponse(
				['error' => $this->l10n->t('The OCR result could not be saved.')],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

		if ($outcome['processed'] === true) {
			return new JSONResponse($this->describe(fileId: $fileId, result: $outcome['result']));
		}

		return $this->refused(fileId: $fileId, reason: (string) $outcome['reason']);

	}//end run()

	/**
	 * A file's last OCR result.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return JSONResponse {fileId, ocrProcessed, ocrConfidence, result}.
	 *
	 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-2.1
	 */
	#[NoAdminRequired]
	public function show(int $fileId): JSONResponse {
		$file = $this->requireReadableFile(fileId: $fileId);
		if ($file === null) {
			return $this->notFound();
		}

		$result = $this->results->findForFile(fileId: $fileId);
		$status = [
			'fileId' => $fileId,
			'ocrProcessed' => false,
			'ocrConfidence' => null,
			'ocrAvailable' => $this->runs->isCandidate(mimeType: $file->getMimeType()),
			'result' => null,
		];
		if ($result !== null) {
			$status = array_merge($status, $this->describe(fileId: $fileId, result: $result), ['result' => $this->publicFields(result: $result)]);
		}

		return new JSONResponse($status);

	}//end show()

	/**
	 * Whether OCR can run here, and the results for the files asked about.
	 *
	 * Files the caller cannot open are left out, the same as files that never ran.
	 *
	 * @return JSONResponse {capability: {enabled, tesseractAvailable, available}, results: {fileId: result}}.
	 *
	 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-3.1
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		$requested = array_slice(
			array_unique(array_filter(array_map('intval', explode(',', (string) $this->request->getParam('fileIds', ''))))),
			0,
			self::MAX_STATUS_FILES
		);

		$results = [];
		foreach ($requested as $fileId) {
			if ($this->requireReadableFile(fileId: $fileId) === null) {
				continue;
			}

			$result = $this->results->findForFile(fileId: $fileId);
			if ($result !== null) {
				$results[(string) $fileId] = $this->publicFields(result: $result);
			}
		}

		return new JSONResponse(['capability' => $this->runs->capability(), 'results' => (object) $results]);

	}//end index()

	/**
	 * The file with this id in the caller's own folder, or null.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return File|null The file, when the caller can read it.
	 */
	private function requireReadableFile(int $fileId): ?File {
		$user = $this->userSession->getUser();
		if ($user === null || $fileId <= 0) {
			return null;
		}

		try {
			$node = $this->rootFolder->getUserFolder($user->getUID())->getFirstNodeById($fileId);
		} catch (Throwable $e) {
			return null;
		}

		if ($node instanceof File === false || $node->isReadable() === false) {
			return null;
		}

		return $node;

	}//end requireReadableFile()

	/**
	 * The run's answer: status, confidence, length and settings.
	 *
	 * @param int $fileId The file id.
	 * @param array<string, mixed> $result The ocrResult row.
	 *
	 * @return array<string, mixed> The answer.
	 */
	private function describe(int $fileId, array $result): array {
		return array_merge(
			['fileId' => $fileId, 'ocrProcessed' => true, 'ocrConfidence' => $result['confidence'] ?? null],
			$this->publicFields(result: $result)
		);

	}//end describe()

	/**
	 * The row's fields a caller may see. There is no text to leave out: the
	 * row never holds it.
	 *
	 * @param array<string, mixed> $result The ocrResult row.
	 *
	 * @return array<string, mixed> confidence, textLength, languages, dpi, ocrProcessedAt, triggeredBy, engineVersion.
	 */
	private function publicFields(array $result): array {
		$fields = [];
		foreach (['confidence', 'textLength', 'languages', 'dpi', 'ocrProcessedAt', 'triggeredBy', 'engineVersion'] as $key) {
			$fields[$key] = $result[$key] ?? null;
		}

		return $fields;

	}//end publicFields()

	/**
	 * A run that did not happen, with the status its reason calls for.
	 *
	 * @param int $fileId The file id.
	 * @param string $reason One of OcrRunService::SKIP_*.
	 *
	 * @return JSONResponse The refusal.
	 */
	private function refused(int $fileId, string $reason): JSONResponse {
		$answers = [
			OcrRunService::SKIP_DISABLED => [Http::STATUS_CONFLICT, $this->l10n->t('OCR is switched off by an administrator.')],
			OcrRunService::SKIP_NO_TESSERACT => [Http::STATUS_SERVICE_UNAVAILABLE, $this->l10n->t('OCR is not available: Tesseract is not installed on the server.')],
			OcrRunService::SKIP_NOT_CANDIDATE => [Http::STATUS_BAD_REQUEST, $this->l10n->t('OCR reads images and PDF files only.')],
			OcrRunService::SKIP_NO_TEXT => [Http::STATUS_OK, $this->l10n->t('OCR found no text in this file.')],
			OcrRunService::SKIP_FAILED => [Http::STATUS_INTERNAL_SERVER_ERROR, $this->l10n->t('OCR failed on this file.')],
		];
		[$status, $message] = ($answers[$reason] ?? $answers[OcrRunService::SKIP_FAILED]);

		return new JSONResponse(
			['fileId' => $fileId, 'ocrProcessed' => false, 'reason' => $reason, 'error' => $message, 'textLength' => 0],
			$status
		);

	}//end refused()

	/**
	 * The answer for a file the caller cannot open, the same as one that does not exist.
	 *
	 * @return JSONResponse 404.
	 */
	private function notFound(): JSONResponse {
		return new JSONResponse(['error' => $this->l10n->t('File not found')], Http::STATUS_NOT_FOUND);

	}//end notFound()
}//end class
