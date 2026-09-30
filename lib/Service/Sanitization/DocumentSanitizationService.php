<?php

/**
 * Document sanitization service
 *
 * Removes the hidden payload of a document (comments, tracked changes,
 * author metadata, field codes) into a new file beside it, through
 * OpenRegister's office sanitizer, and keeps a record of what was removed:
 * counts per category, never the removed content. A PDF is refused
 * fail-flagged until OpenRegister ships a PDF sanitizer; nothing then claims
 * the PDF was cleaned.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Sanitization
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-sanitization/tasks.md#3-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Sanitization;

use InvalidArgumentException;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Sanitizes one document into a derivative and records the run.
 */
class DocumentSanitizationService {

	/**
	 * OpenRegister's office sanitizer.
	 */
	public const OFFICE_ENGINE = 'OCA\OpenRegister\Service\File\OfficeDocumentSanitizer';

	/**
	 * Constructor.
	 *
	 * @param IRootFolder                  $rootFolder The files, read through the user's folder.
	 * @param OpenRegisterServiceLocator   $locator    OpenRegister's services.
	 * @param SanitizationRecordRepository $records    The run records.
	 * @param LoggerInterface              $logger     The logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly OpenRegisterServiceLocator $locator,
		private readonly SanitizationRecordRepository $records,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Sanitize a file the user can read into a derivative beside it.
	 *
	 * @param int    $fileId  The file.
	 * @param string $userId  Who asks.
	 * @param string $trigger manual, anonymisation or publication.
	 *
	 * @return array<string, mixed> sanitized, sanitizedFileId, sanitizedFileName, report, recordId; or sanitizationSkipped with a reason.
	 *
	 * @throws InvalidArgumentException 404 unreachable, 415 unsupported, 422 encrypted, 500 engine failure.
	 *
	 * @spec openspec/changes/document-sanitization/tasks.md#3-1
	 */
	public function sanitize(int $fileId, string $userId, string $trigger='manual'): array {
		$file = $this->resolve(fileId: $fileId, userId: $userId);
		$mime = $file->getMimeType();
		if ($mime === 'application/pdf') {
			return $this->skipped(fileId: $fileId, reason: 'pdf_sanitizer_unavailable');
		}

		$engine = $this->officeEngine();
		if ($engine === null || $engine->isSanitizable($mime) === false) {
			throw new InvalidArgumentException(message: 'unsupported_format', code: 415);
		}

		$result = $this->runEngine(engine: $engine, fileId: $fileId);
		try {
			$derivative = $this->writeDerivative(source: $file, tempPath: $result->path);
		} finally {
			@unlink($result->path);
		}

		$report = $result->report->jsonSerialize();
		$record = $this->records->save(
			record: [
				'fileId'          => $fileId,
				'sanitizedFileId' => $derivative->getId(),
				'trigger'         => $trigger,
				'engine'          => 'OfficeDocumentSanitizer',
				'report'          => $report,
				'sanitizedAt'     => gmdate(format: 'Y-m-d\TH:i:s\Z'),
				'sanitizedBy'     => $userId,
			]
		);

		return [
			'sanitized'         => true,
			'sanitizationSkipped' => false,
			'fileId'            => $fileId,
			'sanitizedFileId'   => $derivative->getId(),
			'sanitizedFileName' => $derivative->getName(),
			'report'            => $report,
			'recordId'          => (string) ($record['uuid'] ?? ''),
		];

	}//end sanitize()

	/**
	 * The file, through the user's folder: not found and not allowed read the same.
	 *
	 * @param int    $fileId The file.
	 * @param string $userId The user.
	 *
	 * @return File The file.
	 *
	 * @throws InvalidArgumentException 404.
	 */
	private function resolve(int $fileId, string $userId): File {
		try {
			$nodes = $this->rootFolder->getUserFolder($userId)->getById($fileId);
		} catch (Throwable) {
			$nodes = [];
		}

		foreach ($nodes as $node) {
			if ($node instanceof File) {
				return $node;
			}
		}

		throw new InvalidArgumentException(message: 'not_found', code: 404);

	}//end resolve()

	/**
	 * OpenRegister's office sanitizer, or null when it is not there.
	 *
	 * @return mixed The engine.
	 */
	private function officeEngine(): mixed {
		try {
			return $this->locator->get(className: self::OFFICE_ENGINE);
		} catch (Throwable) {
			return null;
		}

	}//end officeEngine()

	/**
	 * Run the engine; map its refusals to statuses.
	 *
	 * @param mixed $engine The office sanitizer.
	 * @param int   $fileId The file.
	 *
	 * @return mixed The engine's SanitizationResult.
	 *
	 * @throws InvalidArgumentException 415, 422 or 500.
	 */
	private function runEngine(mixed $engine, int $fileId): mixed {
		try {
			return $engine->sanitize($fileId);
		} catch (Throwable $e) {
			$reason = 'internal';
			if (method_exists($e, 'getReason') === true) {
				$reason = (string) $e->getReason();
			}

			$status = match ($reason) {
				'encrypted' => 422,
				'unsupported-mime' => 415,
				'corrupt-zip' => 422,
				default => 500,
			};
			if ($status === 500) {
				$this->logger->error('Sanitization failed', ['fileId' => $fileId, 'reason' => $reason]);
			}

			throw new InvalidArgumentException(message: $reason, code: $status, previous: $e);
		}//end try

	}//end runEngine()

	/**
	 * Write the clean bytes beside the source as <name>_sanitized.<ext>.
	 *
	 * @param File   $source   The source.
	 * @param string $tempPath The engine's output.
	 *
	 * @return File The derivative.
	 */
	private function writeDerivative(File $source, string $tempPath): File {
		$name = $source->getName();
		$dot = strrpos($name, '.');
		$derivativeName = $name . '_sanitized';
		if ($dot !== false && $dot > 0) {
			$derivativeName = substr($name, 0, $dot) . '_sanitized' . substr($name, $dot);
		}

		$parent = $source->getParent();
		$stream = fopen($tempPath, 'r');

		return $parent->newFile($parent->getNonExistingName($derivativeName), $stream);

	}//end writeDerivative()

	/**
	 * A fail-flagged skip: no file, no record.
	 *
	 * @param int    $fileId The file.
	 * @param string $reason Why.
	 *
	 * @return array<string, mixed> The answer.
	 */
	private function skipped(int $fileId, string $reason): array {
		return [
			'sanitized'           => false,
			'sanitizationSkipped' => true,
			'reason'              => $reason,
			'fileId'              => $fileId,
		];

	}//end skipped()
}//end class
