<?php

/**
 * Filinq Template Import Service
 *
 * Migrates an existing template estate: a ZIP upload or a Files folder of
 * DOCX/ODT templates and text fragments becomes office templates and
 * textFragment objects, in a background job whose state and per-file report
 * live on a templateImportJob object.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use OCA\Filinq\Kernel\Contracts\OpenRegisterResolverInterface;
use OCA\Filinq\Kernel\JobQueueInterface;
use OCP\Files\File;
use OCP\Files\Folder;
use Throwable;
use ZipArchive;

/**
 * Starts and runs template imports.
 *
 * A file that cannot be imported is marked failed with its reason and the
 * import goes on with the next one. Nothing is held in memory between the
 * request and the job: the archive is stored and the job reads it back.
 *
 * @category Service
 * @package  OCA\Filinq\Service\OfficeTemplate
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity) The complexity is the sum of small independent
 * steps of one office template (tags, fragments, conditions, tables).
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
 */
class TemplateImportService {

	/**
	 * The schema slug of an import job.
	 */
	public const SCHEMA = 'templateImportJob';

	/**
	 * The folder whose files become text fragments.
	 */
	public const FRAGMENTS_FOLDER = 'fragments';

	/**
	 * The template extensions.
	 */
	private const TEMPLATE_EXTENSIONS = ['docx', 'odt', 'docm', 'dotm', 'dotx'];

	/**
	 * The fragment extensions.
	 */
	private const FRAGMENT_EXTENSIONS = ['txt', 'html', 'htm'];

	/**
	 * The largest single file read from an archive, in bytes.
	 */
	private const MAX_ENTRY_BYTES = 52428800;

	/**
	 * How many files are imported between two saves of the job's progress.
	 */
	private const SAVE_EVERY = 25;

	/**
	 * Constructor.
	 *
	 * @param TemplatesRegisterObjects      $objects   The templates register.
	 * @param OfficeTemplateService         $templates Creates office templates from files.
	 * @param TextFragmentService           $fragments Creates text fragments.
	 * @param TemplateImportFiles           $files     Stores the uploaded archive and reads a Files folder.
	 * @param JobQueueInterface             $jobs      Queues the import.
	 * @param OpenRegisterResolverInterface $resolver  Checks the namespace.
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
	 */
	public function __construct(
		private readonly TemplatesRegisterObjects $objects,
		private readonly OfficeTemplateService $templates,
		private readonly TextFragmentService $fragments,
		private readonly TemplateImportFiles $files,
		private readonly JobQueueInterface $jobs,
		private readonly OpenRegisterResolverInterface $resolver,
	) {
	}//end __construct()

	/**
	 * Start an import from an uploaded ZIP or a folder in the user's Files.
	 *
	 * @param array<string, mixed> $fields  namespace (required), boundRegister, boundSchema, path.
	 * @param string|null          $archive The ZIP bytes, or null to read `path`.
	 * @param string               $userId  The user who starts it.
	 *
	 * @return array<string, mixed> The queued import job.
	 *
	 * @throws Exception With code 400 when the namespace, archive or folder is missing or unreadable.
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
	 */
	public function start(array $fields, ?string $archive, string $userId): array {
		$namespace = trim((string) ($fields['namespace'] ?? ''));
		if ($namespace === '') {
			throw new Exception(message: 'Namespace is required', code: 400);
		}

		$this->resolver->validateNamespace(namespace: $namespace);
		$job = [
			'status' => 'queued',
			'totalFiles' => 0,
			'imported' => 0,
			'failed' => 0,
			'skipped' => 0,
			'startedBy' => $userId,
			'namespace' => $namespace,
			'report' => [],
		];
		$folder = ['path' => ($fields['path'] ?? null), 'userId' => $userId];
		$job = array_merge($job, $this->bindings(source: $fields), $this->source(archive: $archive, folder: $folder));
		$saved = $this->objects->save(schema: self::SCHEMA, data: $job);
		$this->jobs->add(job: JobQueueInterface::TEMPLATE_IMPORT, argument: ['jobId' => (string) ($saved['id'] ?? ''), 'userId' => $userId]);

		return $saved;
	}//end start()

	/**
	 * The register and schema bindings a source gives, trimmed, without empty ones.
	 *
	 * @param array<string, mixed> $source The fields or the job.
	 *
	 * @return array<string, string>
	 */
	private function bindings(array $source): array {
		$bindings = [];
		foreach (['boundRegister', 'boundSchema'] as $binding) {
			$value = trim((string) ($source[$binding] ?? ''));
			if ($value !== '') {
				$bindings[$binding] = $value;
			}
		}

		return $bindings;
	}//end bindings()

	/**
	 * Where an import reads from, and how many files it holds.
	 *
	 * @param string|null          $archive The uploaded ZIP's bytes, or null for a folder.
	 * @param array<string, mixed> $folder  The folder's `path` in the Files of `userId`.
	 *
	 * @return array<string, mixed> `totalFiles` with `archiveFileId` or `sourcePath`.
	 *
	 * @throws Exception With code 400 when neither a ZIP nor a folder is given.
	 */
	private function source(?string $archive, array $folder): array {
		if ($archive !== null) {
			return [
				'totalFiles' => count($this->zipNames(bytes: $archive)),
				'archiveFileId' => $this->files->store->store(bytes: $archive, extension: 'zip')['fileId'],
			];
		}

		$path = trim((string) ($folder['path'] ?? ''));
		if ($path === '') {
			throw new Exception(message: 'A ZIP file or a folder path is required', code: 400);
		}

		$files = $this->folderFiles(folder: $this->userFolder(userId: (string) $folder['userId'], path: $path));

		return ['sourcePath' => $path, 'totalFiles' => count($files)];
	}//end source()

	/**
	 * One import job.
	 *
	 * @param string $jobId The job's id.
	 *
	 * @return array<string, mixed>
	 *
	 * @throws Exception With code 404 when there is no such job.
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
	 */
	public function get(string $jobId): array {
		return $this->objects->get(schema: self::SCHEMA, id: $jobId);
	}//end get()

	/**
	 * Run a queued import job to its end.
	 *
	 * @param string $jobId The job's id.
	 *
	 * @return array<string, mixed> The finished job.
	 *
	 * @throws Exception With code 404 when there is no such job.
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
	 */
	public function run(string $jobId): array {
		$job = $this->get(jobId: $jobId);
		if (in_array($job['status'] ?? '', ['completed', 'failed'], true) === true) {
			return $job;
		}

		$job = array_merge($job, ['status' => 'running', 'imported' => 0, 'failed' => 0, 'skipped' => 0, 'report' => []]);
		$job['startedAt'] = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
		$job = $this->save(job: $job);

		try {
			$entries = $this->entries(job: $job);
		} catch (Throwable $e) {
			$job['status'] = 'failed';
			$job['report'][] = ['file' => '', 'status' => 'failed', 'reason' => 'The import source could not be read: '.$e->getMessage()];
			$job['finishedAt'] = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
			return $this->save(job: $job);
		}

		$job['totalFiles'] = count($entries);
		$count = 0;
		foreach ($entries as $name => $read) {
			$row = $this->importOne(job: $job, entry: ['read' => $read] + $this->entry(name: $name));
			$job['report'][] = $row;
			$job[$row['status']]++;
			$count++;
			if ($count % self::SAVE_EVERY === 0) {
				$job = $this->save(job: $job);
			}
		}

		$job['status'] = 'completed';
		$job['finishedAt'] = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);

		return $this->save(job: $job);
	}//end run()

	/**
	 * Import one file and describe the outcome.
	 *
	 * @param array<string, mixed> $job   The job.
	 * @param array<string, mixed> $entry The entry (see entry()), with `read` returning its bytes.
	 *
	 * @return array<string, mixed> The report row.
	 */
	private function importOne(array $job, array $entry): array {
		$name = $entry['name'];
		if ($entry['kind'] === null) {
			return ['file' => $name, 'status' => 'skipped', 'reason' => 'Not a template (DOCX, ODT) or a fragment (in a fragments folder).'];
		}

		try {
			$bytes = (string) ($entry['read'])();
			if ($entry['kind'] === 'fragment') {
				return $this->importFragment(job: $job, entry: ['bytes' => $bytes] + $entry);
			}

			return $this->importTemplate(job: $job, entry: ['bytes' => $bytes] + $entry);
		} catch (Throwable $e) {
			return ['file' => $name, 'status' => 'failed', 'reason' => $e->getMessage()];
		}
	}//end importOne()

	/**
	 * What an entry's path makes it: a template, a fragment or neither.
	 *
	 * @param string $name The entry's path.
	 *
	 * @return array{name: string, fileName: string, extension: string, title: string, folders: array<int, string>, kind: string|null}
	 */
	private function entry(string $name): array {
		$folders = explode('/', trim($name, '/'));
		$fileName = (string) array_pop($folders);
		$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
		$kind = null;
		if (in_array($extension, self::TEMPLATE_EXTENSIONS, true) === true) {
			$kind = 'template';
		}

		if (in_array(self::FRAGMENTS_FOLDER, array_map('strtolower', $folders), true) === true
			&& in_array($extension, self::FRAGMENT_EXTENSIONS, true) === true
		) {
			$kind = 'fragment';
		}

		$title = trim((string) pathinfo($fileName, PATHINFO_FILENAME));

		return ['name' => $name, 'fileName' => $fileName, 'extension' => $extension, 'title' => $title, 'folders' => $folders, 'kind' => $kind];
	}//end entry()

	/**
	 * Create one template from an entry, in the first folder's category.
	 *
	 * @param array<string, mixed> $job   The job.
	 * @param array<string, mixed> $entry The entry, with its `bytes`.
	 *
	 * @return array<string, mixed> The report row.
	 *
	 * @throws Exception When the template is refused.
	 */
	private function importTemplate(array $job, array $entry): array {
		$fields = array_merge(['name' => $entry['title'], 'namespace' => $job['namespace']], $this->bindings(source: $job));
		if ($entry['folders'] !== []) {
			$fields['category'] = $entry['folders'][0];
		}

		$created = $this->templates->create(fields: $fields, fileName: $entry['fileName'], bytes: (string) $entry['bytes']);
		$unknown = array_values((array) ($created['tagReport']['unknown'] ?? []));

		return [
			'file' => $entry['name'],
			'status' => 'imported',
			'kind' => 'template',
			'id' => (string) ($created['template']['id'] ?? ''),
			'tags' => count((array) ($created['template']['mergeFields'] ?? [])),
			'unknownTags' => count($unknown),
			'unknown' => $unknown,
			'converted' => $created['converted'],
		];
	}//end importTemplate()

	/**
	 * Import one text fragment.
	 *
	 * @param array<string, mixed> $job   The job.
	 * @param array<string, mixed> $entry The entry: its path, title, extension and `bytes`.
	 *
	 * @return array<string, mixed> The report row.
	 */
	private function importFragment(array $job, array $entry): array {
		$text = (string) $entry['bytes'];
		if ($entry['extension'] !== 'txt') {
			// HTML fragments keep their line breaks and lose their markup.
			$text = (string) preg_replace('#<\s*(br|/p|/div|/li)[^>]*>#i', "\n", $text);
			$text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}

		$text = trim(str_replace(["\r\n", "\r"], "\n", $text));
		$fragment = $this->fragments->create(
			data: ['name' => $entry['title'], 'slug' => $this->slug(title: $entry['title']), 'content' => $text, 'namespace' => $job['namespace']]
		);

		return [
			'file' => $entry['name'],
			'status' => 'imported',
			'kind' => 'fragment',
			'id' => (string) ($fragment['id'] ?? ''),
			'slug' => ($fragment['slug'] ?? null),
		];
	}//end importFragment()

	/**
	 * A slug made from a file name.
	 *
	 * @param string $title The name.
	 *
	 * @return string
	 */
	private function slug(string $title): string {
		$ascii = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title);
		$slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($ascii)), '-');

		return substr($slug, 0, 128);
	}//end slug()

	/**
	 * The files of an import, each with a reader for its bytes.
	 *
	 * @param array<string, mixed> $job The job.
	 *
	 * @return array<string, callable> Readers by path.
	 *
	 * @throws Exception When the source cannot be read.
	 */
	private function entries(array $job): array {
		if (empty($job['archiveFileId']) === false) {
			$bytes = $this->files->store->read(fileId: (int) $job['archiveFileId']);
			if ($bytes === null) {
				throw new Exception(message: 'the stored archive is gone');
			}

			return $this->zipEntries(bytes: $bytes);
		}

		$folder = $this->userFolder(userId: (string) ($job['startedBy'] ?? ''), path: (string) ($job['sourcePath'] ?? ''));
		$entries = [];
		foreach ($this->folderFiles(folder: $folder) as $path => $file) {
			$entries[$path] = static fn (): string => $file->getContent();
		}

		return $entries;
	}//end entries()

	/**
	 * The files of a ZIP, each with a reader; folders and system entries are left out.
	 *
	 * @param string $bytes The ZIP.
	 *
	 * @return array<string, callable>
	 *
	 * @throws Exception With code 400 when it is not a ZIP.
	 */
	private function zipEntries(string $bytes): array {
		[$zip, $path] = $this->openZip(bytes: $bytes);
		$entries = [];
		for ($index = 0; $index < $zip->numFiles; $index++) {
			$stat = $zip->statIndex($index);
			$name = (string) ($stat['name'] ?? '');
			if ($this->isSystemEntry(name: $name) === false) {
				$entries[$name] = $this->zipReader(zip: $zip, stat: $stat);
			}
		}

		$zip->close();
		unlink($path);

		return $entries;
	}//end zipEntries()

	/**
	 * A reader for one ZIP entry; an entry over the size limit is not read
	 * and its reader refuses.
	 *
	 * @param ZipArchive           $zip  The open archive.
	 * @param array<string, mixed> $stat The entry's statIndex().
	 *
	 * @return callable(): string
	 */
	private function zipReader(ZipArchive $zip, array $stat): callable {
		$size = (int) ($stat['size'] ?? 0);
		$content = null;
		if ($size <= self::MAX_ENTRY_BYTES) {
			$content = $zip->getFromIndex((int) ($stat['index'] ?? 0));
		}

		return static function () use ($content, $size): string {
			if ($content === null || $content === false) {
				throw new Exception(message: 'The file is too large to import ('.$size.' bytes).');
			}

			return $content;
		};
	}//end zipReader()

	/**
	 * Open ZIP bytes through a temporary file.
	 *
	 * @param string $bytes The archive.
	 *
	 * @return array{0: ZipArchive, 1: string} The archive and the temporary path to unlink after closing.
	 *
	 * @throws Exception With code 400 when the bytes are not a ZIP archive.
	 */
	private function openZip(string $bytes): array {
		$path = (string) tempnam(sys_get_temp_dir(), 'filinq-import-');
		file_put_contents($path, $bytes);
		$zip = new ZipArchive();
		if ($zip->open($path, ZipArchive::RDONLY) !== true) {
			unlink($path);
			throw new Exception(message: 'The file is not a ZIP archive.', code: 400);
		}

		return [$zip, $path];
	}//end openZip()

	/**
	 * The names of the importable entries of ZIP bytes.
	 *
	 * @param string $bytes The archive.
	 *
	 * @return array<int, string>
	 *
	 * @throws Exception With code 400 when the bytes are not a ZIP archive.
	 */
	private function zipNames(string $bytes): array {
		[$zip, $path] = $this->openZip(bytes: $bytes);
		$names = [];
		for ($index = 0; $index < $zip->numFiles; $index++) {
			$name = (string) $zip->getNameIndex($index);
			if ($this->isSystemEntry(name: $name) === false) {
				$names[] = $name;
			}
		}

		$zip->close();
		unlink($path);

		return $names;
	}//end zipNames()

	/**
	 * Whether a ZIP entry is a folder or an operating system's bookkeeping file.
	 *
	 * @param string $name The entry's name.
	 *
	 * @return bool
	 */
	private function isSystemEntry(string $name): bool {
		$base = basename($name);

		return $name === '' || str_ends_with($name, '/') === true || str_starts_with($name, '__MACOSX/') === true
			|| str_starts_with($base, '.') === true || str_starts_with($base, '~$') === true;
	}//end isSystemEntry()

	/**
	 * A folder in a user's Files.
	 *
	 * @param string $userId The user.
	 * @param string $path   The folder's path.
	 *
	 * @return Folder
	 *
	 * @throws Exception With code 400 when it is not a folder the user has.
	 */
	private function userFolder(string $userId, string $path): Folder {
		try {
			$node = $this->files->rootFolder->getUserFolder($userId)->get($path);
		} catch (Throwable) {
			$node = null;
		}

		if ($node instanceof Folder === false) {
			throw new Exception(message: 'The folder "'.$path.'" was not found in your files.', code: 400);
		}

		return $node;
	}//end userFolder()

	/**
	 * The files of a folder and its subfolders, by path relative to it.
	 *
	 * @param Folder $folder The folder.
	 * @param string $prefix The path so far.
	 *
	 * @return array<string, File>
	 */
	private function folderFiles(Folder $folder, string $prefix=''): array {
		$files = [];
		foreach ($folder->getDirectoryListing() as $node) {
			$name = $prefix.$node->getName();
			if ($node instanceof Folder) {
				$files = array_merge($files, $this->folderFiles(folder: $node, prefix: $name.'/'));
				continue;
			}

			if ($node instanceof File && $this->isSystemEntry(name: $name) === false) {
				$files[$name] = $node;
			}
		}

		return $files;
	}//end folderFiles()

	/**
	 * Save the job's state.
	 *
	 * @param array<string, mixed> $job The job.
	 *
	 * @return array<string, mixed> The saved job.
	 */
	private function save(array $job): array {
		$id = (string) ($job['id'] ?? '');
		$saved = $this->objects->save(schema: self::SCHEMA, data: $job, id: $id);

		return array_merge($job, $saved);
	}//end save()
}//end class
