<?php

/**
 * Document Merge Service
 *
 * Turns a selection of documents into one ordered PDF: convert each input,
 * concatenate them in the order the person chose, prepend a cover page when one
 * is asked for, add one bookmark per input, and write the result where they
 * said.
 *
 * Two rules the rest of the class is arranged around:
 *
 * 1. The checks run BEFORE the job exists. A caller who may not read one of the
 *    inputs gets a refusal and no job, because a failed job in the register is
 *    a record of an attempt that should never have been recorded.
 * 2. A failure writes NO partial file. A bundle missing its third document
 *    looks exactly like a complete one, and the person who hands it over has no
 *    way to tell.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Exception\MergeRefusedException;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Converts, orders, bookmarks and stores one merged PDF.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */
class DocumentMergeService {

	/**
	 * The schema holding the jobs.
	 *
	 * @var string
	 */
	public const SCHEMA = 'mergeJob';

	/**
	 * Queued, not started.
	 *
	 * @var string
	 */
	public const STATUS_QUEUED = 'queued';

	/**
	 * Running.
	 *
	 * @var string
	 */
	public const STATUS_RUNNING = 'running';

	/**
	 * Finished, with a result file.
	 *
	 * @var string
	 */
	public const STATUS_DONE = 'done';

	/**
	 * Stopped, with a reason and no result file.
	 *
	 * @var string
	 */
	public const STATUS_FAILED = 'failed';

	/**
	 * Constructor.
	 *
	 * @param MergeJobRepository $jobs The job store.
	 * @param PdfConversionService $conversion Converts one input to PDF, and says which backend did it.
	 * @param Pdfa3ConversionService $archival Turns the merged PDF into PDF/A-3b, where the instance can.
	 * @param PdfDocumentFactory $documents Builds the FPDI document the pages go into.
	 * @param MergeCoverRenderer $cover Renders the cover page, when one is asked for.
	 * @param IRootFolder $rootFolder The file tree.
	 * @param IUserSession $userSession The current session; every input is read as this person.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly MergeJobRepository $jobs,
		private readonly PdfConversionService $conversion,
		private readonly Pdfa3ConversionService $archival,
		private readonly PdfDocumentFactory $documents,
		private readonly MergeCoverRenderer $cover,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Merge a selection into one PDF.
	 *
	 * @param array<int, array<string, mixed>> $inputs The documents, in order, each `{fileId, label}`.
	 * @param array<string, mixed> $options The cover template, the bookmarks toggle, the target folder and the name.
	 * @param array<string, mixed> $hostObject The object the merge was started from.
	 *
	 * @return array<string, mixed> The finished job.
	 *
	 * @throws MergeRefusedException When the caller may not read an input or write the target.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function merge(array $inputs, array $options = [], array $hostObject = []): array {
		$resolved = $this->resolveInputs(inputs: $inputs);
		$folder = $this->resolveTarget(options: $options, first: $resolved[0]['file'], owner: $this->currentUserId());

		$job = $this->jobs->create(
			job: [
				'inputs' => $this->inputSummary(resolved: $resolved),
				'options' => $options,
				'requestedBy' => $this->currentUserId(),
				'hostObject' => $hostObject,
				'progress' => 0,
				'status' => self::STATUS_RUNNING,
			]
		);

		return $this->run(job: $job, resolved: $resolved, folder: $folder, options: $options);

	}//end merge()

	/**
	 * Record a merge to be run later, once the checks have passed.
	 *
	 * The checks run HERE, while the person who asked is still on the other end
	 * of the request. A queued job is the record that they passed, which is why
	 * the background job does not repeat them as somebody else.
	 *
	 * @param array<int, array<string, mixed>> $inputs The documents, in order.
	 * @param array<string, mixed> $options What is asked for.
	 * @param array<string, mixed> $hostObject The object the merge was started from.
	 *
	 * @return array<string, mixed> The queued job.
	 *
	 * @throws MergeRefusedException When the caller may not read an input or write the target.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function queue(array $inputs, array $options = [], array $hostObject = []): array {
		$resolved = $this->resolveInputs(inputs: $inputs);
		$this->resolveTarget(options: $options, first: $resolved[0]['file'], owner: $this->currentUserId());

		return $this->jobs->create(
			job: [
				'inputs' => $this->inputSummary(resolved: $resolved),
				'options' => $options,
				'requestedBy' => $this->currentUserId(),
				'hostObject' => $hostObject,
				'progress' => 0,
				'status' => self::STATUS_QUEUED,
			]
		);

	}//end queue()

	/**
	 * Run a job that was queued earlier.
	 *
	 * Every input is resolved as the person who ASKED, not as whoever the
	 * background runner happens to be. A merge that ran as an administrator
	 * would quietly include files the requester could not read, and the bundle
	 * would look exactly the same.
	 *
	 * @param array<string, mixed> $job The queued job.
	 *
	 * @return array<string, mixed> The finished job.
	 *
	 * @throws MergeRefusedException When the inputs no longer resolve for that person.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function resume(array $job): array {
		$owner = (string)($job['requestedBy'] ?? '');
		$inputs = [];
		if (isset($job['inputs']) === true && is_array($job['inputs']) === true) {
			$inputs = $job['inputs'];
		}

		$options = [];
		if (isset($job['options']) === true && is_array($job['options']) === true) {
			$options = $job['options'];
		}

		$resolved = $this->resolveInputs(inputs: $inputs, owner: $owner);
		$folder = $this->resolveTarget(options: $options, first: $resolved[0]['file'], owner: $owner);

		$job['status'] = self::STATUS_RUNNING;
		$job = $this->jobs->save(job: $job, uuid: (string)($job['uuid'] ?? ''));

		return $this->run(job: $job, resolved: $resolved, folder: $folder, options: $options);

	}//end resume()

	/**
	 * Whether a selection is large enough to be queued rather than waited for.
	 *
	 * The page count is an ESTIMATE: the real one is only known after every
	 * input has been converted, and converting them to decide whether to
	 * convert them in the background defeats the point. A selection that says
	 * how many pages each input has is believed; one that does not is estimated
	 * from the file sizes.
	 *
	 * @param array<int, array<string, mixed>> $inputs The requested inputs.
	 * @param int $threshold The page count above which a merge is queued.
	 *
	 * @return bool True when the merge should be queued.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function shouldQueue(array $inputs, int $threshold): bool {
		if ($threshold <= 0) {
			return false;
		}

		$pages = 0;
		foreach ($inputs as $input) {
			$declared = (int)($input['pages'] ?? 0);
			if ($declared > 0) {
				$pages += $declared;
				continue;
			}

			$size = (int)($input['size'] ?? 0);
			$pages += max(1, (int)ceil(($size / 51200)));
		}

		return ($pages > $threshold);

	}//end shouldQueue()

	/**
	 * Run a job whose checks have already passed.
	 *
	 * @param array<string, mixed> $job The job, already stored.
	 * @param array<int, array<string, mixed>> $resolved The resolved inputs.
	 * @param Folder $folder The folder the result goes in.
	 * @param array<string, mixed> $options What was asked for.
	 *
	 * @return array<string, mixed> The finished job.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	private function run(array $job, array $resolved, Folder $folder, array $options): array {
		$uuid = (string)($job['uuid'] ?? '');
		$document = $this->documents->create();
		$pages = 0;
		$wantsBookmarks = (($options['bookmarks'] ?? true) === true);

		$coverRef = trim((string)($options['coverTemplateRef'] ?? ''));
		if ($coverRef !== '') {
			try {
				$pages += $this->documents->appendPages(
					document: $document,
					pdf: $this->cover->render(
						templateRef: $coverRef,
						data: [
							'inputs' => $this->inputSummary(resolved: $resolved),
							'requestedBy' => $this->currentUserId(),
							'documentCount' => count($resolved),
						]
					)
				);
			} catch (Throwable $e) {
				// A bundle whose cover did not render says nothing about what
				// it is, so this fails rather than quietly shipping the pages.
				return $this->fail(
					uuid: $uuid,
					job: $job,
					reason: 'Could not render the cover page: ' . $e->getMessage()
				);
			}

			if ($wantsBookmarks === true) {
				$document->addBookmark('Voorblad', 1);
			}
		}//end if

		foreach ($resolved as $index => $input) {
			try {
				$pdf = $this->conversion->convertToPdfReporting(source: $input['file']);
			} catch (Throwable $e) {
				// NOTHING has been written yet, and nothing will be: the file
				// is only created after every input has converted.
				return $this->fail(
					uuid: $uuid,
					job: $job,
					reason: 'Could not convert ' . $input['label'] . ': ' . $e->getMessage()
				);
			}

			try {
				$added = $this->documents->appendPages(
					document: $document,
					pdf: (string)$pdf['file']->getContent()
				);
			} catch (Throwable $e) {
				return $this->fail(
					uuid: $uuid,
					job: $job,
					reason: 'Could not add ' . $input['label'] . ' to the merge: ' . $e->getMessage()
				);
			}

			if ($wantsBookmarks === true) {
				$document->addBookmark($input['label'], ($pages + 1));
			}

			$pages += $added;
			$job['progress'] = (int)round(((($index + 1) / count($resolved)) * 100));
			$this->jobs->save(job: $job, uuid: $uuid);
		}//end foreach

		try {
			$bytes = $this->documents->output(document: $document);
		} catch (Throwable $e) {
			return $this->fail(uuid: $uuid, job: $job, reason: 'Could not produce the merged PDF: ' . $e->getMessage());
		}

		$name = $this->resultName(options: $options);

		try {
			$file = $folder->newFile($folder->getNonExistingName($name), $bytes);
		} catch (Throwable $e) {
			return $this->fail(uuid: $uuid, job: $job, reason: 'Could not write the merged PDF: ' . $e->getMessage());
		}

		$conformance = $this->toArchival(file: $file);

		$job['resultFileId'] = $file->getId();
		$job['pageCount'] = $pages;
		$job['progress'] = 100;
		$job['conformance'] = $conformance;
		$job['status'] = self::STATUS_DONE;

		return $this->jobs->save(job: $job, uuid: $uuid);

	}//end run()

	/**
	 * Turn the merged file into PDF/A-3b where the instance can.
	 *
	 * An instance without the archival toolchain gets an ordinary PDF and the
	 * job SAYS so. Failing the whole merge because a binary is missing would
	 * lose the bundle; claiming PDF/A-3b that was never produced would be
	 * worse than either.
	 *
	 * @param File $file The merged file.
	 *
	 * @return string `pdfa-3b` or `pdf`.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	private function toArchival(File $file): string {
		try {
			$converted = $this->archival->convertExistingPdf($file);
		} catch (Throwable $e) {
			$this->logger->info(
				message: '[DocumentMergeService] the merge stays an ordinary PDF; the archival conversion is not available here',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);

			return 'pdf';
		}

		$content = (string)($converted['content'] ?? '');
		if ($content === '') {
			return 'pdf';
		}

		try {
			$file->putContent($content);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[DocumentMergeService] the archival conversion ran but could not be written back',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);

			return 'pdf';
		}

		return 'pdfa-3b';

	}//end toArchival()

	/**
	 * Stop a job, naming what went wrong, and leave no file behind.
	 *
	 * @param string $uuid The job.
	 * @param array<string, mixed> $job The job as it stands.
	 * @param string $reason What went wrong.
	 *
	 * @return array<string, mixed> The failed job.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	private function fail(string $uuid, array $job, string $reason): array {
		$job['status'] = self::STATUS_FAILED;
		$job['lastError'] = $reason;

		$this->logger->error(
			message: '[DocumentMergeService] the merge failed and wrote no file',
			context: ['file' => __FILE__, 'line' => __LINE__, 'uuid' => $uuid, 'reason' => $reason]
		);

		return $this->jobs->save(job: $job, uuid: $uuid);

	}//end fail()

	/**
	 * Resolve every input to a file the caller can actually read.
	 *
	 * @param array<int, array<string, mixed>> $inputs The requested inputs.
	 * @param string $owner The person the inputs are read as, or an empty string for the session user.
	 *
	 * @return array<int, array<string, mixed>> The resolved inputs, in order.
	 *
	 * @throws MergeRefusedException When an input is missing, unreadable or absent from the request.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	private function resolveInputs(array $inputs, string $owner = ''): array {
		if ($inputs === []) {
			throw new MergeRefusedException(
				message: 'A merge names the documents it merges.',
				status: 400
			);
		}

		if ($owner === '') {
			$owner = $this->currentUserId();
		}

		if ($owner === '') {
			throw new MergeRefusedException(message: 'You must be logged in to merge documents.', status: 401);
		}

		$userFolder = $this->rootFolder->getUserFolder($owner);
		$resolved = [];

		foreach ($inputs as $input) {
			$fileId = (int)($input['fileId'] ?? 0);
			if ($fileId <= 0) {
				throw new MergeRefusedException(message: 'Every input names a file.', status: 400);
			}

			$nodes = $userFolder->getById($fileId);
			if ($nodes === []) {
				// Indistinguishable from "does not exist", on purpose: the
				// caller learns no more about a file they may not read than
				// about one that is not there.
				throw new MergeRefusedException(
					message: 'You may not read one of the documents in this selection.',
					status: 403
				);
			}

			$node = $nodes[0];
			$label = trim((string)($input['label'] ?? ''));
			if ($label === '') {
				$label = $node->getName();
			}

			$resolved[] = ['fileId' => $fileId, 'label' => $label, 'file' => $node];
		}//end foreach

		return $resolved;

	}//end resolveInputs()

	/**
	 * The folder the result goes in, checked for write.
	 *
	 * @param array<string, mixed> $options What was asked for.
	 * @param File $first The first input, whose folder is the default.
	 * @param string $owner The person the folder is resolved as.
	 *
	 * @return Folder The target folder.
	 *
	 * @throws MergeRefusedException When the caller may not write there.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	private function resolveTarget(array $options, File $first, string $owner): Folder {
		$path = trim((string)($options['targetFolder'] ?? ''));

		if ($path === '' || $owner === '') {
			$parent = $first->getParent();
			$this->requireWritable(folder: $parent);

			return $parent;
		}

		try {
			$folder = $this->rootFolder->getUserFolder($owner)->get(ltrim($path, '/'));
		} catch (Throwable $e) {
			throw new MergeRefusedException(
				message: 'You may not write to the folder this merge names.',
				status: 403,
				previous: $e
			);
		}

		if (($folder instanceof Folder) === false) {
			throw new MergeRefusedException(message: 'The merge target is not a folder.', status: 400);
		}

		$this->requireWritable(folder: $folder);

		return $folder;

	}//end resolveTarget()

	/**
	 * Refuse a folder the caller cannot write.
	 *
	 * @param Folder $folder The folder.
	 *
	 * @return void
	 *
	 * @throws MergeRefusedException When it is not writable.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	private function requireWritable(Folder $folder): void {
		if ($folder->isCreatable() === true) {
			return;
		}

		throw new MergeRefusedException(
			message: 'You may not write to the folder this merge names.',
			status: 403
		);

	}//end requireWritable()

	/**
	 * The inputs as they are stored on the job: no file handles, just the order.
	 *
	 * @param array<int, array<string, mixed>> $resolved The resolved inputs.
	 *
	 * @return array<int, array<string, mixed>> The stored shape.
	 *
	 * @spec exclude Shape adapter with no behaviour of its own.
	 */
	private function inputSummary(array $resolved): array {
		$summary = [];
		foreach ($resolved as $input) {
			$summary[] = ['fileId' => $input['fileId'], 'label' => $input['label']];
		}

		return $summary;

	}//end inputSummary()

	/**
	 * The name the result is written under.
	 *
	 * @param array<string, mixed> $options What was asked for.
	 *
	 * @return string The file name, always ending in .pdf.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	private function resultName(array $options): string {
		$name = trim((string)($options['name'] ?? ''));
		if ($name === '') {
			$name = 'samenvoeging';
		}

		if (str_ends_with(strtolower($name), '.pdf') === false) {
			$name .= '.pdf';
		}

		return $name;

	}//end resultName()

	/**
	 * The user id of the person at the keyboard.
	 *
	 * @return string The user id, or an empty string when there is no session.
	 *
	 * @spec exclude Session accessor with no behaviour of its own.
	 */
	private function currentUserId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return '';
		}

		return $user->getUID();

	}//end currentUserId()
}//end class
