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
 * @spec openspec/specs/document-merge/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Exception\MergeRefusedException;
use OCA\Filinq\Exception\MergeStepFailedException;
use OCA\Filinq\Kernel\Contracts\DocumentMergeServiceInterface;
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
 * The merge steps are small private methods that share one step exception,
 * so the class total counts more methods and types than one long method did.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 *
 * @spec openspec/specs/document-merge/spec.md
 */
class DocumentMergeService implements DocumentMergeServiceInterface {

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
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function merge(array $inputs, array $options = [], array $hostObject = []): array {
		$selection = new MergeSelection(resolved: $this->resolveInputs(inputs: $inputs), options: $options);
		$plan = new MergePlan(selection: $selection, folder: $this->resolveTarget(selection: $selection, owner: $this->currentUserId()));

		$job = $this->jobs->create(job: $this->newJob(selection: $selection, hostObject: $hostObject) + ['status' => self::STATUS_RUNNING]);

		return $this->run(run: new MergeRun(job: $job, plan: $plan));
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
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function queue(array $inputs, array $options = [], array $hostObject = []): array {
		$selection = new MergeSelection(resolved: $this->resolveInputs(inputs: $inputs), options: $options);
		$this->resolveTarget(selection: $selection, owner: $this->currentUserId());

		return $this->jobs->create(job: $this->newJob(selection: $selection, hostObject: $hostObject) + ['status' => self::STATUS_QUEUED]);
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
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function resume(array $job): array {
		$owner = (string)($job['requestedBy'] ?? '');
		$resolved = $this->resolveInputs(inputs: $this->listField(job: $job, key: 'inputs'), owner: $owner);
		$selection = new MergeSelection(resolved: $resolved, options: $this->listField(job: $job, key: 'options'));
		$plan = new MergePlan(selection: $selection, folder: $this->resolveTarget(selection: $selection, owner: $owner));

		$job['status'] = self::STATUS_RUNNING;
		$job = $this->jobs->save(job: $job, uuid: (string)($job['uuid'] ?? ''));

		return $this->run(run: new MergeRun(job: $job, plan: $plan));
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
	 * @spec openspec/specs/document-merge/spec.md
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
	 * An array field of a stored job, or an empty array when it is missing or not an array.
	 *
	 * @param array<string, mixed> $job The stored job.
	 * @param string $key The field.
	 *
	 * @return array<mixed> The field's value.
	 */
	private function listField(array $job, string $key): array {
		if (isset($job[$key]) === true && is_array($job[$key]) === true) {
			return $job[$key];
		}

		return [];
	}//end listField()

	/**
	 * A new job for a checked selection, without its status.
	 *
	 * @param MergeSelection $selection The inputs and options.
	 * @param array<string, mixed> $hostObject The object the merge was started from.
	 *
	 * @return array<string, mixed> The job fields.
	 */
	private function newJob(MergeSelection $selection, array $hostObject): array {
		return [
			'inputs' => $this->inputSummary(resolved: $selection->resolved),
			'options' => $selection->options,
			'requestedBy' => $this->currentUserId(),
			'hostObject' => $hostObject,
			'progress' => 0,
		];

	}//end newJob()

	/**
	 * Run a job whose checks have already passed.
	 *
	 * @param MergeRun $run The stored job and its plan.
	 *
	 * @return array<string, mixed> The finished job.
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	private function run(MergeRun $run): array {
		$document = $this->documents->create();

		try {
			$run->pages = $this->appendCoverPage(document: $document, selection: $run->plan->selection);
			$this->appendInputs(document: $document, run: $run);
			$file = $this->writeResult(document: $document, plan: $run->plan);
		} catch (MergeStepFailedException $e) {
			return $this->fail(run: $run, reason: $e->getMessage());
		}

		return $this->finish(run: $run, file: $file);
	}//end run()

	/**
	 * Record the written result on the job and store it as done.
	 *
	 * @param MergeRun $run The merge that ran.
	 * @param File $file The written result.
	 *
	 * @return array<string, mixed> The finished job.
	 */
	private function finish(MergeRun $run, File $file): array {
		$run->job['resultFileId'] = $file->getId();
		$run->job['pageCount'] = $run->pages;
		$run->job['progress'] = 100;
		$run->job['conformance'] = $this->toArchival(file: $file);
		$run->job['status'] = self::STATUS_DONE;

		return $this->jobs->save(job: $run->job, uuid: $run->uuid());
	}//end finish()

	/**
	 * Put the cover page in front of the merge, when one was asked for.
	 *
	 * A bundle whose cover did not render says nothing about what it is, so
	 * this fails rather than quietly shipping the pages.
	 *
	 * @param MergedPdfDocument $document The document being built.
	 * @param MergeSelection $selection The inputs and options.
	 *
	 * @return int The number of pages added, 0 without a cover.
	 *
	 * @throws MergeStepFailedException When the cover does not render.
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	private function appendCoverPage(MergedPdfDocument $document, MergeSelection $selection): int {
		$coverRef = $selection->coverRef();
		if ($coverRef === '') {
			return 0;
		}

		try {
			return $this->documents->appendCover(
				document: $document,
				pdf: $this->cover->render(
					templateRef: $coverRef,
					data: [
						'inputs' => $this->inputSummary(resolved: $selection->resolved),
						'requestedBy' => $this->currentUserId(),
						'documentCount' => count($selection->resolved),
					]
				),
				withBookmark: $selection->wantsBookmarks()
			);
		} catch (Throwable $e) {
			throw new MergeStepFailedException(message: 'Could not render the cover page: ' . $e->getMessage(), code: 0, previous: $e);
		}

	}//end appendCoverPage()

	/**
	 * Add every input in order, with a bookmark on its first page when asked
	 * for, and store the progress after each.
	 *
	 * @param MergedPdfDocument $document The document being built.
	 * @param MergeRun $run The running merge; its page count and job move on.
	 *
	 * @return void
	 *
	 * @throws MergeStepFailedException When an input cannot be converted or added.
	 */
	private function appendInputs(MergedPdfDocument $document, MergeRun $run): void {
		$resolved = $run->plan->selection->resolved;
		foreach ($resolved as $index => $input) {
			$added = $this->appendInput(document: $document, input: $input);
			if ($run->plan->selection->wantsBookmarks() === true) {
				$document->addBookmark($input['label'], ($run->pages + 1));
			}

			$run->pages += $added;
			$run->job['progress'] = (int)round(((($index + 1) / count($resolved)) * 100));
			$this->jobs->save(job: $run->job, uuid: $run->uuid());
		}

	}//end appendInputs()

	/**
	 * Convert one input and add its pages.
	 *
	 * Nothing is written while inputs are added: the file is only created
	 * after every input has converted.
	 *
	 * @param MergedPdfDocument $document The document being built.
	 * @param array<string, mixed> $input The resolved input.
	 *
	 * @return int The number of pages added.
	 *
	 * @throws MergeStepFailedException When the input cannot be converted or added.
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	private function appendInput(MergedPdfDocument $document, array $input): int {
		try {
			$pdf = $this->conversion->convertToPdfReporting(source: $input['file']);
		} catch (Throwable $e) {
			throw new MergeStepFailedException(message: 'Could not convert ' . $input['label'] . ': ' . $e->getMessage(), code: 0, previous: $e);
		}

		try {
			return $this->documents->appendPages(
				document: $document,
				pdf: (string)$pdf['file']->getContent()
			);
		} catch (Throwable $e) {
			throw new MergeStepFailedException(message: 'Could not add ' . $input['label'] . ' to the merge: ' . $e->getMessage(), code: 0, previous: $e);
		}

	}//end appendInput()

	/**
	 * Produce the merged PDF and write it into the target folder.
	 *
	 * @param MergedPdfDocument $document The finished document.
	 * @param MergePlan $plan The folder the result goes in and the name asked for.
	 *
	 * @return File The written file.
	 *
	 * @throws MergeStepFailedException When the PDF cannot be produced or written.
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	private function writeResult(MergedPdfDocument $document, MergePlan $plan): File {
		try {
			$bytes = $this->documents->output(document: $document);
		} catch (Throwable $e) {
			throw new MergeStepFailedException(message: 'Could not produce the merged PDF: ' . $e->getMessage(), code: 0, previous: $e);
		}

		$name = $this->resultName(options: $plan->selection->options);

		try {
			return $plan->folder->newFile($plan->folder->getNonExistingName($name), $bytes);
		} catch (Throwable $e) {
			throw new MergeStepFailedException(message: 'Could not write the merged PDF: ' . $e->getMessage(), code: 0, previous: $e);
		}

	}//end writeResult()

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
	 * @spec openspec/specs/document-merge/spec.md
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
	 * @param MergeRun $run The running merge, its job as it stands.
	 * @param string $reason What went wrong.
	 *
	 * @return array<string, mixed> The failed job.
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	private function fail(MergeRun $run, string $reason): array {
		$run->job['status'] = self::STATUS_FAILED;
		$run->job['lastError'] = $reason;

		$this->logger->error(
			message: '[DocumentMergeService] the merge failed and wrote no file',
			context: ['file' => __FILE__, 'line' => __LINE__, 'uuid' => $run->uuid(), 'reason' => $reason]
		);

		return $this->jobs->save(job: $run->job, uuid: $run->uuid());
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
	 * @spec openspec/specs/document-merge/spec.md
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
	 * @param MergeSelection $selection The options and the first input, whose folder is the default.
	 * @param string $owner The person the folder is resolved as.
	 *
	 * @return Folder The target folder.
	 *
	 * @throws MergeRefusedException When the caller may not write there.
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	private function resolveTarget(MergeSelection $selection, string $owner): Folder {
		$path = trim((string)($selection->options['targetFolder'] ?? ''));

		if ($path === '' || $owner === '') {
			$parent = $selection->first()->getParent();
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
	 * @spec openspec/specs/document-merge/spec.md
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
	 * @spec openspec/specs/document-merge/spec.md
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
