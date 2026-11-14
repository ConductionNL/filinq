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
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
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
use OCP\Files\IRootFolder;
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
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
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
	 * @param OfficeSourceStore             $store     Stores the uploaded archive.
	 * @param JobQueueInterface             $jobs      Queues the import.
	 * @param IRootFolder                   $rootFolder Reads a Files folder.
	 * @param OpenRegisterResolverInterface $resolver  Checks the namespace.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
	 */
	public function __construct(
		private readonly TemplatesRegisterObjects $objects,
		private readonly OfficeTemplateService $templates,
		private readonly TextFragmentService $fragments,
		private readonly OfficeSourceStore $store,
		private readonly JobQueueInterface $jobs,
		private readonly IRootFolder $rootFolder,
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
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
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
		foreach (['boundRegister', 'boundSchema'] as $binding) {
			$value = trim((string) ($fields[$binding] ?? ''));
			if ($value !== '') {
				$job[$binding] = $value;
			}
		}

		if ($archive !== null) {
			$job['totalFiles'] = count($this->zipNames(bytes: $archive));
			$job['archiveFileId'] = $this->store->store(bytes: $archive, extension: 'zip')['fileId'];
		} else {
			$path = trim((string) ($fields['path'] ?? ''));
			if ($path === '') {
				throw new Exception(message: 'A ZIP file or a folder path is required', code: 400);
			}

			$job['sourcePath'] = $path;
			$job['totalFiles'] = count($this->folderFiles(folder: $this->userFolder(userId: $userId, path: $path)));
		}

		$saved = $this->objects->save(schema: self::SCHEMA, data: $job);
		$this->jobs->add(job: JobQueueInterface::TEMPLATE_IMPORT, argument: ['jobId' => (string) ($saved['id'] ?? ''), 'userId' => $userId]);

		return $saved;
	}//end start()

	/**
	 * One import job.
	 *
	 * @param string $jobId The job's id.
	 *
	 * @return array<string, mixed>
	 *
	 * @throws Exception With code 404 when there is no such job.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
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
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
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
			$row = $this->importOne(job: $job, name: $name, read: $read);
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
	 * @param array<string, mixed> $job  The job.
	 * @param string               $name The file's path in the import.
	 * @param callable             $read Returns the file's bytes.
	 *
	 * @return array<string, mixed> The report row.
	 */
	private function importOne(array $job, string $name, callable $read): array {
		$parts = explode('/', trim($name, '/'));
		$fileName = (string) array_pop($parts);
		$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
		$title = trim((string) pathinfo($fileName, PATHINFO_FILENAME));
		$isFragment = in_array(self::FRAGMENTS_FOLDER, array_map('strtolower', $parts), true) === true
			&& in_array($extension, self::FRAGMENT_EXTENSIONS, true) === true;

		if ($isFragment === false && in_array($extension, self::TEMPLATE_EXTENSIONS, true) === false) {
			return ['file' => $name, 'status' => 'skipped', 'reason' => 'Not a template (DOCX, ODT) or a fragment (in a fragments folder).'];
		}

		try {
			$bytes = (string) $read();
			if ($isFragment === true) {
				return $this->importFragment(job: $job, name: $name, title: $title, bytes: $bytes, extension: $extension);
			}

			$fields = ['name' => $title, 'namespace' => $job['namespace']];
			foreach (['boundRegister', 'boundSchema'] as $binding) {
				if (empty($job[$binding]) === false) {
					$fields[$binding] = $job[$binding];
				}
			}

			if ($parts !== []) {
				$fields['category'] = $parts[0];
			}

			$created = $this->templates->create(fields: $fields, fileName: $fileName, bytes: $bytes);
			$report = $created['tagReport'];

			return [
				'file' => $name,
				'status' => 'imported',
				'kind' => 'template',
				'id' => (string) ($created['template']['id'] ?? ''),
				'tags' => count((array) ($created['template']['mergeFields'] ?? [])),
				'unknownTags' => count((array) ($report['unknown'] ?? [])),
				'unknown' => array_values((array) ($report['unknown'] ?? [])),
				'converted' => $created['converted'],
			];
		} catch (Throwable $e) {
			return ['file' => $name, 'status' => 'failed', 'reason' => $e->getMessage()];
		}//end try
	}//end importOne()

	/**
	 * Import one text fragment.
	 *
	 * @param array<string, mixed> $job       The job.
	 * @param string               $name      The file's path in the import.
	 * @param string               $title     The file's name without extension.
	 * @param string               $bytes     The file's content.
	 * @param string               $extension The file's extension.
	 *
	 * @return array<string, mixed> The report row.
	 */
	private function importFragment(array $job, string $name, string $title, string $bytes, string $extension): array {
		$text = $bytes;
		if ($extension !== 'txt') {
			// HTML fragments keep their line breaks and lose their markup.
			$text = (string) preg_replace('#<\s*(br|/p|/div|/li)[^>]*>#i', "\n", $text);
			$text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}

		$text = trim(str_replace(["\r\n", "\r"], "\n", $text));
		$fragment = $this->fragments->create(
			data: ['name' => $title, 'slug' => $this->slug(title: $title), 'content' => $text, 'namespace' => $job['namespace']]
		);

		return [
			'file' => $name,
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
			$bytes = $this->store->read(fileId: (int) $job['archiveFileId']);
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
		$path = (string) tempnam(sys_get_temp_dir(), 'filinq-import-');
		file_put_contents($path, $bytes);
		$zip = new ZipArchive();
		if ($zip->open($path, ZipArchive::RDONLY) !== true) {
			unlink($path);
			throw new Exception(message: 'The file is not a ZIP archive.', code: 400);
		}

		$entries = [];
		for ($index = 0; $index < $zip->numFiles; $index++) {
			$stat = $zip->statIndex($index);
			$name = (string) ($stat['name'] ?? '');
			if ($this->isSystemEntry(name: $name) === true) {
				continue;
			}

			$size = (int) ($stat['size'] ?? 0);
			$content = null;
			if ($size <= self::MAX_ENTRY_BYTES) {
				$content = $zip->getFromIndex($index);
			}

			$entries[$name] = static function () use ($content, $size): string {
				if ($content === null || $content === false) {
					throw new Exception(message: 'The file is too large to import ('.$size.' bytes).');
				}

				return $content;
			};
		}//end for

		$zip->close();
		unlink($path);

		return $entries;
	}//end zipEntries()

	/**
	 * The names of a ZIP's importable entries.
	 *
	 * @param string $bytes The ZIP.
	 *
	 * @return array<int, string>
	 *
	 * @throws Exception With code 400 when it is not a ZIP.
	 */
	private function zipNames(string $bytes): array {
		$path = (string) tempnam(sys_get_temp_dir(), 'filinq-import-');
		file_put_contents($path, $bytes);
		$zip = new ZipArchive();
		if ($zip->open($path, ZipArchive::RDONLY) !== true) {
			unlink($path);
			throw new Exception(message: 'The file is not a ZIP archive.', code: 400);
		}

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
			$node = $this->rootFolder->getUserFolder($userId)->get($path);
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
