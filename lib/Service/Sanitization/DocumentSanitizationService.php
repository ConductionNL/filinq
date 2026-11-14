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
use OCA\Filinq\Kernel\Contracts\DocumentSanitizationServiceInterface;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Sanitizes one document into a derivative and records the run.
 */
class DocumentSanitizationService implements DocumentSanitizationServiceInterface {

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
		[$engine, $engineName] = $this->selectEngine(mime: $mime);
		if ($engine === null) {
			return $this->skipped(fileId: $fileId, reason: 'pdf_sanitizer_unavailable');
		}

		$result = $this->runEngine(engine: $engine, fileId: $fileId);
		$derivative = $this->writeAndDiscard(source: $file, tempPath: $result->path);

		$report = $result->report->jsonSerialize();
		if ($engineName === 'PdfDocumentSanitizer') {
			$report = self::pdfReport(report: $report);
		}

		$record = $this->records->save(
			record: [
				'fileId'          => $fileId,
				'sanitizedFileId' => $derivative->getId(),
				'trigger'         => $trigger,
				'engine'          => $engineName,
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
	 * The engine for a mime type and its name.
	 *
	 * A PDF engine that is not installed comes back null, so the caller skips;
	 * any other format the office engine cannot sanitize is refused.
	 *
	 * @param string $mime The file's mime type.
	 *
	 * @return array{0: mixed, 1: string} The engine (null: PDF engine unavailable) and its name.
	 *
	 * @throws InvalidArgumentException 415 when the format is not supported.
	 */
	private function selectEngine(string $mime): array {
		if ($mime === 'application/pdf') {
			return [$this->engine(className: self::PDF_ENGINE), 'PdfDocumentSanitizer'];
		}

		$engine = $this->engine(className: self::OFFICE_ENGINE);
		if ($engine === null || $engine->isSanitizable($mime) === false) {
			throw new InvalidArgumentException(message: 'unsupported_format', code: 415);
		}

		return [$engine, 'OfficeDocumentSanitizer'];

	}//end selectEngine()

	/**
	 * Write the derivative beside the source, then remove the engine's temp file.
	 *
	 * @param File   $source   The source file.
	 * @param string $tempPath The engine's output.
	 *
	 * @return File The derivative.
	 */
	private function writeAndDiscard(File $source, string $tempPath): File {
		try {
			return $this->writeDerivative(source: $source, tempPath: $tempPath);
		} finally {
			if (is_file($tempPath) === true) {
				unlink($tempPath);
			}
		}

	}//end writeAndDiscard()

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
	 * An OpenRegister sanitizer, or null when this OpenRegister does not have it.
	 *
	 * @param string $className OFFICE_ENGINE or PDF_ENGINE.
	 *
	 * @return mixed The engine.
	 */
	private function engine(string $className): mixed {
		try {
			return $this->locator->get(className: $className);
		} catch (Throwable) {
			return null;
		}

	}//end engine()

	/**
	 * OpenRegister's PDF report in the record's report shape: counts per
	 * category, the full re-save, and the PDF/A-3 archival attachments kept.
	 *
	 * @param array<string, mixed> $report The PdfSanitizationReport as data.
	 *
	 * @return array<string, mixed> The record report.
	 *
	 * @spec openspec/changes/document-sanitization/specs/document-sanitization/spec.md#requirement-pdf-sanitization-removes-hidden-payload-through-a-full-re-save-req-ddsan-002
	 */
	private static function pdfReport(array $report): array {
		$preserved = [];
		foreach ((array) ($report['preservedAttachments'] ?? []) as $attachment) {
			$preserved[] = [
				'name' => mb_substr((string) ($attachment['name'] ?? ''), 0, 255),
				'relationship' => (string) ($attachment['relationship'] ?? ''),
			];
		}

		return [
			'metadataFieldsScrubbed' => (int) ($report['metadataFieldsScrubbed'] ?? 0),
			'xmpNamespacesStripped' => (int) (($report['xmpScrubbed'] ?? false) === true),
			'annotationsRemoved' => (int) ($report['annotationsRemoved'] ?? 0),
			'embeddedFilesRemoved' => (int) ($report['embeddedFilesRemoved'] ?? 0),
			'scriptsRemoved' => (int) ($report['javascriptRemoved'] ?? 0)
				+ (int) ($report['openActionsRemoved'] ?? 0)
				+ (int) ($report['additionalActionsRemoved'] ?? 0),
			'resaved' => true,
			'preservedAttachments' => $preserved,
		];

	}//end pdfReport()

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
