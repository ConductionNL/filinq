<?php

/**
 * Intake OCR Job
 *
 * Reads the text of one arriving intake document in the background and
 * moves its reading state through `reading` to `read`, or to `failed` with a
 * reason a registrar can act on.
 *
 * @category  BackgroundJob
 * @package   OCA\Filinq\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-ocr-on-arrival/tasks.md#task-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\BackgroundJob;

use OCA\Filinq\Service\Intake\IntakeReadingProgress;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\OcrService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * One reading of one intake document.
 *
 * @spec openspec/changes/intake-ocr-on-arrival/specs/intake-ocr-on-arrival/spec.md
 */
class IntakeOcrJob extends QueuedJob {

	/**
	 * The longest text kept on the record, in characters.
	 *
	 * @var int
	 */
	public const MAX_TEXT = 500000;

	public const REASON_UNAVAILABLE = 'Text recognition is off or not installed on this server';

	public const REASON_NO_TEXT = 'No text was found in this document';

	public const REASON_NO_FILE = 'The file of this document could not be found';

	/**
	 * Constructor.
	 *
	 * @param ITimeFactory $time The clock.
	 * @param IntakeRepository $repository The intake documents.
	 * @param OcrService $ocr The Tesseract engine and the admin's OCR settings.
	 * @param IRootFolder $rootFolder Resolves the file without a user session.
	 * @param IntakeReadingProgress $progress The reading states.
	 * @param LoggerInterface $logger The logger.
	 */
	public function __construct(
		private readonly ITimeFactory $clock,
		private readonly IntakeRepository $repository,
		private readonly OcrService $ocr,
		private readonly IRootFolder $rootFolder,
		private readonly IntakeReadingProgress $progress,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(time: $clock);

	}//end __construct()

	/**
	 * Read the document's file and record the outcome on the document.
	 *
	 * @param mixed $argument {uuid, fileId}.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/intake-ocr-on-arrival/tasks.md#task-1.2
	 */
	protected function run(mixed $argument): void {
		$uuid = (string) ($argument['uuid'] ?? '');
		$document = $this->repository->findByUuid(uuid: $uuid);
		if ($document === null) {
			$this->logger->warning('IntakeOcrJob: the intake document is gone', ['uuid' => $uuid]);
			return;
		}

		$document = $this->record(uuid: $uuid, document: $document, state: IntakeReadingProgress::READING);

		[$text, $reason] = $this->read(fileId: (int) ($argument['fileId'] ?? ($document['file'] ?? 0)));
		if ($reason !== '') {
			$this->record(uuid: $uuid, document: $document, state: IntakeReadingProgress::FAILED, error: $reason);
			return;
		}

		$document['contentText'] = mb_substr($text, 0, self::MAX_TEXT);
		$this->record(uuid: $uuid, document: $document, state: IntakeReadingProgress::READ);

	}//end run()

	/**
	 * Run OCR on the file.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array{0: string, 1: string} The text, and the reason it failed ('' when it did not).
	 */
	private function read(int $fileId): array {
		if ($this->ocr->isOcrEnabled() === false || $this->ocr->isTesseractAvailable() === false) {
			return ['', self::REASON_UNAVAILABLE];
		}

		try {
			$node = $this->rootFolder->getFirstNodeById($fileId);
			if ($node instanceof File === false) {
				return ['', self::REASON_NO_FILE];
			}

			$text = trim($this->ocr->processNode(file: $node)['text']);
		} catch (Throwable $e) {
			$this->logger->warning('IntakeOcrJob: reading failed', ['fileId' => $fileId, 'exception' => $e->getMessage()]);
			return ['', 'Text recognition failed: ' . $e->getMessage()];
		}

		if ($text === '') {
			return ['', self::REASON_NO_TEXT];
		}

		return [$text, ''];

	}//end read()

	/**
	 * Store a reading state on the document.
	 *
	 * @param string $uuid The document.
	 * @param array<string, mixed> $document Its fields.
	 * @param string $state The state reached.
	 * @param string $error Why it failed.
	 *
	 * @return array<string, mixed> The document as stored.
	 */
	private function record(string $uuid, array $document, string $state, string $error = ''): array {
		$document = array_merge(
			$document,
			$this->progress->progressFor(
				state: $state,
				error: $error,
				moment: $this->clock->getDateTime()->format(DATE_ATOM)
			)
		);

		return $this->repository->save(document: $document, uuid: $uuid);

	}//end record()
}//end class
