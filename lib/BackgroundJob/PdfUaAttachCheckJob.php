<?php

/**
 * Filinq PdfUaAttachCheckJob
 *
 * Checks one PDF that was attached to an OpenRegister object against PDF/UA-1
 * and tags the verdict on the file. Queued by PdfUaAttachListener, so the
 * upload itself never waits for veraPDF. A check that gives no verdict
 * (veraPDF absent, a timeout, output it cannot read) tags the file
 * `pdfua-niet-gecontroleerd` and stores no report: no verdict is made up.
 *
 * @category  BackgroundJob
 * @package   OCA\Filinq\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-every-pdf-attached-to-an-openregister-object-is-checked-for-pdfua-req-ddpum-006
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\BackgroundJob;

use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceService;
use OCA\Filinq\Service\VeraPdf\PdfUaVerdictTagger;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * One PDF/UA check of an attached PDF, with its verdict tag.
 */
class PdfUaAttachCheckJob extends QueuedJob {

	/**
	 * Constructor.
	 *
	 * @param ITimeFactory                    $clock       The clock.
	 * @param AccessibilityConformanceService $conformance The PDF/UA-1 check.
	 * @param PdfUaVerdictTagger              $tagger      Tags the verdict.
	 * @param IRootFolder                     $rootFolder  The file tree.
	 * @param LoggerInterface                 $logger      The log.
	 *
	 * @return void
	 */
	public function __construct(
		ITimeFactory $clock,
		private readonly AccessibilityConformanceService $conformance,
		private readonly PdfUaVerdictTagger $tagger,
		private readonly IRootFolder $rootFolder,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(time: $clock);

	}//end __construct()

	/**
	 * Check the file and tag it.
	 *
	 * @param mixed $argument `{fileId: int}`.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-every-pdf-attached-to-an-openregister-object-is-checked-for-pdfua-req-ddpum-006
	 */
	protected function run(mixed $argument): void {
		$fileId = 0;
		if (is_array($argument) === true) {
			$fileId = (int) ($argument['fileId'] ?? 0);
		}

		$file = $this->file(fileId: $fileId);
		if ($file === null) {
			$this->logger->info('PdfUaAttachCheckJob: the file is gone', ['fileId' => $fileId]);
			return;
		}

		$this->tagger->tag(fileId: $fileId, verdict: PdfUaVerdictTagger::verdictOf(report: $this->check(file: $file)));

	}//end run()

	/**
	 * The stored report of a PDF/UA check, or null when there is no verdict.
	 *
	 * @param File $file The PDF.
	 *
	 * @return array<string, mixed>|null The report.
	 */
	private function check(File $file): ?array {
		if ($this->conformance->isAvailable() === false) {
			return null;
		}

		try {
			return $this->conformance->checkFile(file: $file, trigger: AccessibilityConformanceService::TRIGGER_ATTACH);
		} catch (Throwable $e) {
			$this->logger->warning('PdfUaAttachCheckJob: no PDF/UA verdict', ['fileId' => $file->getId(), 'error' => $e->getMessage()]);
			return null;
		}

	}//end check()

	/**
	 * The PDF with this id, or null.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return File|null The file.
	 */
	private function file(int $fileId): ?File {
		if ($fileId <= 0) {
			return null;
		}

		try {
			$node = $this->rootFolder->getFirstNodeById($fileId);
		} catch (Throwable) {
			return null;
		}

		if ($node instanceof File && $node->getMimetype() === 'application/pdf') {
			return $node;
		}

		return null;

	}//end file()
}//end class
