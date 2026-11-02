<?php

/**
 * Scan Batch Service
 *
 * A scanner delivers one PDF per batch. This cuts it at the separator sheets
 * filinq printed and hands every segment to the intake inbox as its own
 * document.
 *
 * It ASSIGNS NOTHING. A separator that names a case number puts that number on
 * the intake document as its `sourceRef`, and the inbox offers it to the clerk;
 * the clerk decides. A splitter that filed documents on the strength of a
 * number somebody wrote on a sheet would file them on a typo just as readily.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\Filinq\Event\IntakeDocumentReceivedEvent;
use OCA\Filinq\Exception\ScanSplitFailedException;
use OCA\Filinq\Kernel\Contracts\ScanBatchServiceInterface;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\Folder;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Receives a scanned batch and cuts it into documents.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) Cutting a batch needs the
 * profile, the separator reader, the page reader, the store and the file tree,
 * and each is already its own class. The coupling is the composition, not a
 * class doing several jobs; splitting it further would only move the same five
 * collaborators behind a sixth.
 */
class ScanBatchService implements ScanBatchServiceInterface {

	/**
	 * Constructor.
	 *
	 * @param ScanBatchRepository $batches The batch store.
	 * @param ScanPageReader $pages Reads the batch page by page.
	 * @param SeparatorSheetService $separators Knows what a separator says.
	 * @param PdfDocumentFactory $documents Assembles each segment.
	 * @param IEventDispatcher $events Where every segment is announced.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ScanBatchRepository $batches,
		private readonly ScanPageReader $pages,
		private readonly SeparatorSheetService $separators,
		private readonly PdfDocumentFactory $documents,
		private readonly IEventDispatcher $events,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Take in one batch a scanner delivered.
	 *
	 * @param File $file The PDF the scanner wrote.
	 * @param array<string, mixed> $profile The scan profile whose folder it landed in.
	 *
	 * @return array<string, mixed> The stored batch, in `received`.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function receive(File $file, array $profile): array {
		return $this->batches->save(
			batch: [
				'file' => $file->getId(),
				'scannerId' => (string)($profile['id'] ?? ''),
				'receivedAt' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
				'separatorMode' => $this->mode(profile: $profile),
				'status' => self::STATUS_RECEIVED,
			]
		);

	}//end receive()

	/**
	 * Cut one batch into documents and announce every one of them.
	 *
	 * @param array<string, mixed> $batch The batch, as received.
	 * @param array<string, mixed> $profile The scan profile.
	 * @param File $file The batch file.
	 * @param Folder $target The folder the segments are written to.
	 *
	 * @return array<string, mixed> The batch, `split` or `failed`.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function split(array $batch, array $profile, File $file, Folder $target): array {
		$mode = $this->mode(profile: $profile);

		try {
			$pages = $this->readPages(file: $file, mode: $mode);
			$batch['pageCount'] = count($pages);
			$segments = $this->segmentsOf(pages: $pages, mode: $mode, profile: $profile);
			$this->requireSegments(pages: $pages, segments: $segments);
			$delivery = new ScanDelivery(scannerId: (string)($batch['scannerId'] ?? ($profile['id'] ?? '')), target: $target);
			$batch['segments'] = $this->deliverAll(segments: $segments, delivery: $delivery);
		} catch (ScanSplitFailedException $e) {
			return $this->fail(batch: $batch, reason: $e->getMessage());
		}

		$batch['status'] = self::STATUS_SPLIT;

		return $this->batches->save(batch: $batch, uuid: (string)($batch['uuid'] ?? ''));
	}//end split()

	/**
	 * Read the batch page by page.
	 *
	 * @param File $file The batch file.
	 * @param string $mode The separator mode.
	 *
	 * @return array<int, string> The pages, in order.
	 *
	 * @throws ScanSplitFailedException When no page can be read, or separators cannot be seen in QR mode.
	 */
	private function readPages(File $file, string $mode): array {
		if ($mode === self::MODE_QR && $this->pages->isAvailable() === false) {
			// FAIL, do not deliver. Without a reader every page reads empty,
			// every separator goes unseen, and a forty-page batch would arrive
			// as one document that nobody has any reason to look at twice.
			throw new ScanSplitFailedException(
				message: 'This instance cannot read text off a scanned page, so the separator sheets in this batch could not be seen. Nothing was delivered.'
			);
		}

		try {
			return $this->pages->pages(batch: (string)$file->getContent());
		} catch (Throwable $e) {
			throw new ScanSplitFailedException(message: $e->getMessage(), code: 0, previous: $e);
		}

	}//end readPages()

	/**
	 * Refuse a batch without pages, or one with nothing between its separators.
	 *
	 * @param array<int, string> $pages The pages.
	 * @param array<int, array<string, mixed>> $segments The segments cut from them.
	 *
	 * @return void
	 *
	 * @throws ScanSplitFailedException When there is nothing to deliver.
	 */
	private function requireSegments(array $pages, array $segments): void {
		if ($pages === []) {
			throw new ScanSplitFailedException(message: 'The batch holds no pages.');
		}

		if ($segments === []) {
			throw new ScanSplitFailedException(message: 'Every page in this batch is a separator, so there is nothing between them to deliver.');
		}

	}//end requireSegments()

	/**
	 * Write and announce every segment, in order.
	 *
	 * @param array<int, array<string, mixed>> $segments The segments.
	 * @param ScanDelivery $delivery The scanner and the target folder.
	 *
	 * @return array<int, array<string, mixed>> What the batch records about each segment.
	 *
	 * @throws ScanSplitFailedException When a segment cannot be written; it names the segment.
	 */
	private function deliverAll(array $segments, ScanDelivery $delivery): array {
		$written = [];
		foreach ($segments as $index => $segment) {
			try {
				$written[] = $this->deliver(segment: $segment + ['position' => $index], delivery: $delivery);
			} catch (Throwable $e) {
				throw new ScanSplitFailedException(
					message: 'Segment ' . ($index + 1) . ' could not be written: ' . $e->getMessage(),
					code: 0,
					previous: $e
				);
			}
		}

		return $written;
	}//end deliverAll()

	/**
	 * Cut a list of pages into segments at the separators.
	 *
	 * A separator belongs to NEITHER side: it is a sheet of paper somebody put
	 * in the stack, not part of the document before it or after it. A run of
	 * separators with nothing between them produces no empty segment.
	 *
	 * @param array<int, string> $pages The pages, in order.
	 * @param string $mode The separator mode.
	 * @param array<string, mixed> $profile The scan profile.
	 *
	 * @return array<int, array<string, mixed>> The segments, each with its pages and the case its separator named.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function segmentsOf(array $pages, string $mode, array $profile): array {
		$rule = new SeparatorRule(mode: $mode, inkThreshold: (int)($profile['inkThreshold'] ?? 4096));
		$segments = [];
		$current = ['pages' => [], 'firstPage' => 1, 'caseNumber' => ''];
		$pending = '';

		foreach ($pages as $index => $page) {
			$separator = $this->separatorOf(page: $page, rule: $rule);
			if ($separator !== null) {
				if ($current['pages'] !== []) {
					$segments[] = $current;
				}

				$pending = $separator;
				$current = ['pages' => [], 'firstPage' => ($index + 2), 'caseNumber' => $pending];
				continue;
			}

			if ($current['pages'] === []) {
				$current['firstPage'] = ($index + 1);
				$current['caseNumber'] = $pending;
			}

			$current['pages'][] = $page;
		}//end foreach

		if ($current['pages'] !== []) {
			$segments[] = $current;
		}

		return $segments;
	}//end segmentsOf()

	/**
	 * What one page is: a separator naming a case, a separator naming none, or
	 * an ordinary page.
	 *
	 * @param string $page The page as PDF bytes.
	 * @param SeparatorRule $rule The separator mode and ink threshold.
	 *
	 * @return string|null The case number the separator named, an empty string when it named none, or null for an ordinary page.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	private function separatorOf(string $page, SeparatorRule $rule): ?string {
		if ($rule->mode === self::MODE_BLANK) {
			if ($this->pages->weight(page: $page) > $rule->inkThreshold) {
				return null;
			}

			if (trim($this->pages->text(page: $page)) !== '') {
				return null;
			}

			return '';
		}

		return $this->separators->readPayload(payload: $this->pages->text(page: $page));
	}//end separatorOf()

	/**
	 * Write one segment and announce it to the intake inbox.
	 *
	 * @param array<string, mixed> $segment The segment, with its `position` in the batch from 0.
	 * @param ScanDelivery $delivery The scanner and the folder it is written to.
	 *
	 * @return array<string, mixed> What the batch records about this segment.
	 *
	 * @throws RuntimeException When the segment cannot be assembled or written.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	private function deliver(array $segment, ScanDelivery $delivery): array {
		$document = $this->documents->create();
		foreach ($segment['pages'] as $page) {
			$this->documents->appendPages(document: $document, pdf: $page);
		}

		$scannerId = $delivery->scannerId;
		$name = $delivery->target->getNonExistingName(
			sprintf('scan-%s-%02d.pdf', $scannerId, ($segment['position'] + 1))
		);
		$file = $delivery->target->newFile($name, $this->documents->output(document: $document));

		// The event carries the case number the SEPARATOR named, as a source
		// reference. It is a suggestion for the clerk, never an assignment.
		$this->events->dispatchTyped(
			new IntakeDocumentReceivedEvent(
				channel: IntakeDocumentReceivedEvent::CHANNEL_SCAN,
				fileId: $file->getId(),
				fileName: $name,
				subject: $name,
				sender: $scannerId,
				sourceRef: (string)($segment['caseNumber'] ?? '')
			)
		);

		$this->logger->info(
			message: '[ScanBatchService] a segment of a scanned batch reached the intake inbox',
			context: [
				'file' => __FILE__,
				'line' => __LINE__,
				'scannerId' => $scannerId,
				'fileId' => $file->getId(),
				'pages' => count($segment['pages']),
			]
		);

		return [
			'fileId' => $file->getId(),
			'name' => $name,
			'firstPage' => (int)($segment['firstPage'] ?? 0),
			'pages' => count($segment['pages']),
			'sourceRef' => (string)($segment['caseNumber'] ?? ''),
		];

	}//end deliver()

	/**
	 * Stop a batch, saying why, having delivered nothing.
	 *
	 * @param array<string, mixed> $batch The batch as it stands.
	 * @param string $reason What went wrong.
	 *
	 * @return array<string, mixed> The failed batch.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	private function fail(array $batch, string $reason): array {
		$uuid = (string)($batch['uuid'] ?? '');
		$batch['status'] = self::STATUS_FAILED;
		$batch['lastError'] = $reason;

		$this->logger->error(
			message: '[ScanBatchService] a scanned batch could not be cut, and nothing was delivered',
			context: ['file' => __FILE__, 'line' => __LINE__, 'uuid' => $uuid, 'reason' => $reason]
		);

		return $this->batches->save(batch: $batch, uuid: $uuid);
	}//end fail()

	/**
	 * The separator mode one profile asks for.
	 *
	 * @param array<string, mixed> $profile The profile.
	 *
	 * @return string The mode.
	 *
	 * @spec exclude Reads one declared value with a default.
	 */
	private function mode(array $profile): string {
		$mode = (string)($profile['separatorMode'] ?? self::MODE_QR);
		if ($mode === self::MODE_BLANK) {
			return self::MODE_BLANK;
		}

		return self::MODE_QR;
	}//end mode()
}//end class
