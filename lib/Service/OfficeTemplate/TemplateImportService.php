<?php

/**
 * Template import service
 *
 * Imports a ZIP of office templates and text fragments as a background job
 * whose state lives in a `templateImportJob` object, never in memory. Every
 * DOCX or ODT becomes an office template (same checks as a single upload),
 * every .txt or .html under a `fragments/` folder a text fragment; a file
 * that fails is reported with its reason and the import goes on. The job
 * acts as the user who started it, so OpenRegister's rights are theirs.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\Filinq\BackgroundJob\TemplateImportJob;
use OCP\BackgroundJob\IJobList;
use OCP\IUserManager;
use OCP\IUserSession;
use Throwable;
use ZipArchive;

/**
 * Starts, runs and reports bulk template imports.
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-5
 */
class TemplateImportService {

	/**
	 * Office files an import turns into templates (macro files are tried and refused, so they show in the report).
	 *
	 * @var string[]
	 */
	private const TEMPLATE_EXTENSIONS = ['docx', 'odt', 'docm', 'dotm'];

	/**
	 * Fragment files under a fragments/ folder.
	 *
	 * @var string[]
	 */
	private const FRAGMENT_EXTENSIONS = ['txt', 'html', 'htm'];

	/**
	 * Constructor.
	 *
	 * @param OfficeTemplateService    $officeTemplates Creates each template.
	 * @param TemplateObjectRepository $objects         The job and fragment objects.
	 * @param OfficeSourceStore        $store           Keeps the ZIP while the job waits.
	 * @param IJobList                 $jobList         Queues the job.
	 * @param IUserManager             $users           Finds the user the job acts as.
	 * @param IUserSession             $session         Acts as that user while it runs.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly OfficeTemplateService $officeTemplates,
		private readonly TemplateObjectRepository $objects,
		private readonly OfficeSourceStore $store,
		private readonly IJobList $jobList,
		private readonly IUserManager $users,
		private readonly IUserSession $session,
	) {

	}//end __construct()

	/**
	 * Accept a ZIP and queue its import.
	 *
	 * @param string               $archiveName The uploaded name.
	 * @param string               $bytes       The ZIP.
	 * @param array<string, mixed> $meta        namespace (required), boundRegister, boundSchema.
	 * @param string               $userId      Who starts it.
	 *
	 * @return array The queued templateImportJob.
	 *
	 * @throws OfficeTemplateRefused 422 when it is not a ZIP or holds nothing to import, 400 without a namespace.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-5
	 */
	public function start(string $archiveName, string $bytes, array $meta, string $userId): array {
		$namespace = (string) ($meta['namespace'] ?? '');
		if (preg_match('/^[a-z0-9]+$/', $namespace) !== 1) {
			throw new OfficeTemplateRefused(message: 'A namespace (lowercase letters and digits) is required.', reason: 'namespace', code: 400);
		}

		$entries = $this->entriesOf(bytes: $bytes);
		if ($entries === []) {
			throw new OfficeTemplateRefused(message: 'The ZIP holds no DOCX or ODT templates and no fragments/ texts.', reason: 'empty');
		}

		$job = $this->jobs()->save(
			record: [
				'status' => 'queued',
				'namespace' => $namespace,
				'archiveName' => basename($archiveName),
				'archiveFileId' => $this->store->put(bytes: $bytes, extension: 'zip'),
				'boundRegister' => ($meta['boundRegister'] ?? null),
				'boundSchema' => ($meta['boundSchema'] ?? null),
				'totalFiles' => count($entries),
				'imported' => 0,
				'failed' => 0,
				'startedBy' => $userId,
				'startedAt' => null,
				'finishedAt' => null,
				'error' => null,
				'report' => [],
			]
		);
		$this->jobList->add(TemplateImportJob::class, ['jobId' => $job['uuid']]);

		return $job;

	}//end start()

	/**
	 * A job, for the user who started it.
	 *
	 * @param string $jobId  The job.
	 * @param string $userId The caller.
	 *
	 * @return array The job.
	 *
	 * @throws OfficeTemplateRefused 404 when it does not exist or is someone else's.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-5
	 */
	public function status(string $jobId, string $userId): array {
		$job = $this->jobs()->find(uuid: $jobId);
		if ($job === null || ($job['startedBy'] ?? '') !== $userId) {
			throw new OfficeTemplateRefused(message: 'Import job not found.', reason: 'not-found', code: 404);
		}

		return $job;

	}//end status()

	/**
	 * Run a queued job as the user who started it.
	 *
	 * @param string $jobId The job.
	 *
	 * @return array The job as it ended, or [] when it could not be read.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-5
	 */
	public function run(string $jobId): array {
		$job = $this->jobs()->find(uuid: $jobId);
		if ($job === null || ($job['status'] ?? '') !== 'queued') {
			return [];
		}

		$user = $this->users->get((string) ($job['startedBy'] ?? ''));
		if ($user === null) {
			return $this->finish(job: $job, status: 'failed', error: 'The user who started the import no longer exists.');
		}

		$this->session->setVolatileActiveUser($user);
		try {
			return $this->work(job: $job);
		} finally {
			$this->session->setVolatileActiveUser(null);
		}

	}//end run()

	/**
	 * Import every entry and record the outcome.
	 *
	 * @param array $job The job.
	 *
	 * @return array The finished job.
	 */
	private function work(array $job): array {
		$job['status'] = 'running';
		$job['startedAt'] = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
		$job = $this->jobs()->save(record: $job, uuid: $job['uuid']);

		try {
			$zip = $this->open(bytes: $this->store->read(fileId: (int) $job['archiveFileId']));
		} catch (Throwable $e) {
			return $this->finish(job: $job, status: 'failed', error: 'The ZIP could not be read: ' . $e->getMessage());
		}

		foreach ($this->importableEntries(zip: $zip['archive']) as $entry) {
			$row = $this->importEntry(entry: $entry, bytes: (string) $zip['archive']->getFromName($entry), job: $job);
			$job['report'][] = $row;
			// The row status (imported or failed) is also the job's counter.
			$job[$row['status']]++;
		}

		$zip['archive']->close();
		unlink($zip['path']);

		return $this->finish(job: $job, status: 'completed', error: null);

	}//end work()

	/**
	 * Import one entry; a failure becomes a report row.
	 *
	 * @param string $entry The path inside the ZIP.
	 * @param string $bytes Its content.
	 * @param array  $job   The job.
	 *
	 * @return array The report row.
	 */
	private function importEntry(string $entry, string $bytes, array $job): array {
		$row = ['file' => $entry, 'kind' => 'template', 'status' => 'imported', 'objectId' => null, 'tags' => 0, 'unknownTags' => [], 'reason' => null];
		if ($this->isFragment(entry: $entry) === true) {
			$row['kind'] = 'fragment';
		}

		try {
			if ($row['kind'] === 'fragment') {
				$row['objectId'] = $this->importFragment(entry: $entry, bytes: $bytes, namespace: $job['namespace']);
				return $row;
			}

			$created = $this->officeTemplates->createFromUpload(
				fileName: basename($entry),
				bytes: $bytes,
				meta: $this->templateMeta(entry: $entry, job: $job)
			);
			$row['objectId'] = (string) ($created['template']['id'] ?? ($created['template']['uuid'] ?? ''));
			$row['tags'] = count((array) ($created['template']['mergeFields'] ?? []));
			$row['unknownTags'] = array_values((array) ($created['tagReport']['unknown'] ?? []));
		} catch (Throwable $e) {
			$row['status'] = 'failed';
			$row['reason'] = mb_substr($e->getMessage(), 0, 1000);
		}

		return $row;

	}//end importEntry()

	/**
	 * Create or update a fragment from a text file; the file name is its slug.
	 *
	 * @param string $entry     The path inside the ZIP.
	 * @param string $bytes     Its content.
	 * @param string $namespace The namespace.
	 *
	 * @return string The fragment's uuid.
	 */
	private function importFragment(string $entry, string $bytes, string $namespace): string {
		$stem = pathinfo($entry, PATHINFO_FILENAME);
		$slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($stem)), '-');
		if ($slug === '') {
			throw new OfficeTemplateRefused(message: 'The file name gives no slug.', reason: 'slug');
		}

		$content = $bytes;
		if (in_array(strtolower(pathinfo($entry, PATHINFO_EXTENSION)), ['html', 'htm'], true) === true) {
			$content = html_entity_decode(strip_tags((string) preg_replace('#<br\s*/?>|</p>#i', "\n", $bytes)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}

		$fragments = $this->objects->forSchema(schema: 'textFragment');
		$existing = $fragments->search(filters: ['slug' => $slug, 'namespace' => $namespace]);
		$saved = $fragments->save(
			record: [
				'name' => ucfirst(str_replace('-', ' ', $slug)),
				'slug' => $slug,
				'namespace' => $namespace,
				'content' => mb_substr(trim($content), 0, 20000),
			],
			uuid: ($existing[0]['uuid'] ?? null)
		);

		return (string) $saved['uuid'];

	}//end importFragment()

	/**
	 * The template fields of an imported file: its name from the file name,
	 * its category from the folder it sits in.
	 *
	 * @param string $entry The path inside the ZIP.
	 * @param array  $job   The job.
	 *
	 * @return array<string, mixed> The fields.
	 */
	private function templateMeta(string $entry, array $job): array {
		$meta = [
			'name' => ucfirst(trim(str_replace(['-', '_'], ' ', pathinfo($entry, PATHINFO_FILENAME)))),
			'namespace' => $job['namespace'],
			'boundRegister' => ($job['boundRegister'] ?? null),
			'boundSchema' => ($job['boundSchema'] ?? null),
		];
		$folder = dirname($entry);
		if ($folder !== '.' && $folder !== '') {
			$meta['category'] = basename($folder);
		}

		return $meta;

	}//end templateMeta()

	/**
	 * Close a job with its outcome and drop the stored ZIP.
	 *
	 * @param array       $job    The job.
	 * @param string      $status completed or failed.
	 * @param string|null $error  Why it failed.
	 *
	 * @return array The stored job.
	 */
	private function finish(array $job, string $status, ?string $error): array {
		if (($job['archiveFileId'] ?? null) !== null) {
			$this->store->remove(fileId: (int) $job['archiveFileId']);
		}

		$job['status'] = $status;
		$job['error'] = $error;
		$job['archiveFileId'] = null;
		$job['finishedAt'] = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);

		return $this->jobs()->save(record: $job, uuid: $job['uuid']);

	}//end finish()

	/**
	 * The entries a ZIP holds that an import handles.
	 *
	 * @param string $bytes The ZIP.
	 *
	 * @return string[] The entry paths.
	 *
	 * @throws OfficeTemplateRefused 422 when it is not a ZIP.
	 */
	private function entriesOf(string $bytes): array {
		$opened = $this->open(bytes: $bytes);
		$entries = $this->importableEntries(zip: $opened['archive']);
		$opened['archive']->close();
		unlink($opened['path']);

		return $entries;

	}//end entriesOf()

	/**
	 * The entries an import handles, in archive order.
	 *
	 * @param ZipArchive $zip An open archive.
	 *
	 * @return string[] The entry paths.
	 */
	private function importableEntries(ZipArchive $zip): array {
		$entries = [];
		for ($index = 0; $index < $zip->numFiles; $index++) {
			$name = (string) $zip->getNameIndex($index);
			if (str_ends_with($name, '/') === true || str_contains($name, '__MACOSX') === true || str_starts_with(basename($name), '.') === true) {
				continue;
			}

			$extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
			$fragment = $this->isFragment(entry: $name) === true && in_array($extension, self::FRAGMENT_EXTENSIONS, true) === true;
			if ($fragment === true || in_array($extension, self::TEMPLATE_EXTENSIONS, true) === true) {
				$entries[] = $name;
			}
		}

		return $entries;

	}//end importableEntries()

	/**
	 * Whether an entry sits in a fragments/ folder.
	 *
	 * @param string $entry The path inside the ZIP.
	 *
	 * @return bool True for a fragment.
	 */
	private function isFragment(string $entry): bool {
		return str_starts_with($entry, 'fragments/') === true || str_contains($entry, '/fragments/') === true;

	}//end isFragment()

	/**
	 * Open ZIP bytes.
	 *
	 * @param string $bytes The ZIP.
	 *
	 * @return array{archive: ZipArchive, path: string} The archive and its temp file.
	 *
	 * @throws OfficeTemplateRefused 422 when it is not a ZIP.
	 */
	private function open(string $bytes): array {
		$path = tempnam(sys_get_temp_dir(), 'filinq_import_');
		file_put_contents($path, $bytes);
		$zip = new ZipArchive();
		if (str_starts_with($bytes, "PK\x03\x04") === false || $zip->open($path) !== true) {
			unlink($path);
			throw new OfficeTemplateRefused(message: 'The upload is not a ZIP archive.', reason: 'mime');
		}

		return ['archive' => $zip, 'path' => $path];

	}//end open()

	/**
	 * The job objects, written as the system (the job runs without a request).
	 *
	 * @return TemplateObjectRepository The repository.
	 */
	private function jobs(): TemplateObjectRepository {
		return $this->objects->forSchema(schema: 'templateImportJob')->asSystem();

	}//end jobs()
}//end class
