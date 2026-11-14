<?php

/**
 * Case Archive Service
 *
 * Bundles every file on one object into one archive, with a manifest naming
 * what went in and what was left out and why.
 *
 * NOTHING IS DROPPED SILENTLY. A file over the ceiling, a file the person
 * asking may not read, a file that has gone missing: each is in the manifest
 * with its reason. A bezwaarcommissie that receives a bundle has to be able to
 * check what it received, and a bundle that quietly omitted three documents
 * looks exactly like a complete one.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use OCP\Files\File;
use OCA\Filinq\Kernel\Contracts\CaseArchiveServiceInterface;
use OCP\IAppConfig;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Collects the files of one object into one bundle, and says what it holds.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */
class CaseArchiveService implements CaseArchiveServiceInterface {
	use ReadsCurrentUserId;

	/**
	 * Constructor.
	 *
	 * @param FlatFileListService $files Every file on the object.
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param IUserSession $userSession The current session.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 * @param CaseArchiveWriter $writer Writes the archive into the requester's Files.
	 * @param IAppConfig $appConfig Holds the administered ceiling.
	 * @param ReadableFileResolver $readable Chosen files, as the requester may read them.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly FlatFileListService $files,
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
		private readonly CaseArchiveWriter $writer,
		private readonly IAppConfig $appConfig,
		private readonly ReadableFileResolver $readable,
	) {

	}//end __construct()

	/**
	 * What this bundle would be, before anybody starts building it.
	 *
	 * The caller shows this to the person asking. Being told afterwards that
	 * eleven documents did not fit is being told too late.
	 *
	 * @param array<string, mixed> $domain The object, as register, schema and id.
	 * @param int $ceiling The ceiling in force, in bytes.
	 *
	 * @return array{files: int, bytes: int, ceilingBytes: int, exceedsCeiling: bool} The preflight.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function preflight(array $domain, int $ceiling = self::DEFAULT_CEILING): array {
		$rows = $this->readable(domain: $domain);
		$bytes = 0;
		foreach ($rows as $row) {
			$bytes += (int)($row['size'] ?? 0);
		}

		return [
			'files' => count($rows),
			'bytes' => $bytes,
			'ceilingBytes' => $ceiling,
			'exceedsCeiling' => ($bytes > $ceiling),
		];

	}//end preflight()

	/**
	 * Build the manifest of one bundle: what goes in, and what does not.
	 *
	 * The files are taken in name order and added until the ceiling is reached.
	 * Everything after that is left out with `ceiling` as its reason, so the
	 * manifest says which documents are missing rather than only that some are.
	 *
	 * @param array<string, mixed> $domain The object.
	 * @param int $ceiling The ceiling in force, in bytes.
	 *
	 * @return array{included: array<int, array<string, mixed>>, excluded: array<int, array<string, mixed>>, bytes: int} The manifest.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function manifestFor(array $domain, int $ceiling = self::DEFAULT_CEILING): array {
		$included = [];
		$excluded = [];
		$bytes = 0;

		foreach ($this->readable(domain: $domain) as $row) {
			$size = (int)($row['size'] ?? 0);
			if (($bytes + $size) > $ceiling) {
				$excluded[] = [
					'fileId' => ($row['fileId'] ?? 0),
					'name' => ($row['name'] ?? ''),
					'record' => ($row['record']['uuid'] ?? ''),
					'reason' => self::REASON_CEILING,
					'explanation' => 'The bundle would go over the administered ceiling.',
				];
				continue;
			}

			$bytes += $size;
			$included[] = [
				'fileId' => ($row['fileId'] ?? 0),
				'name' => ($row['name'] ?? ''),
				'record' => ($row['record']['uuid'] ?? ''),
				'size' => $size,
			];
		}//end foreach

		foreach ($this->unreadable(domain: $domain) as $row) {
			$excluded[] = [
				'fileId' => ($row['fileId'] ?? 0),
				'name' => ($row['name'] ?? ''),
				'record' => ($row['uuid'] ?? ''),
				'reason' => self::REASON_PERMISSION,
				'explanation' => 'The person asking for the bundle may not read this file.',
			];
		}

		return ['included' => $included, 'excluded' => $excluded, 'bytes' => $bytes];

	}//end manifestFor()

	/**
	 * The ceiling in force: the administered one, which a caller may lower but never raise.
	 *
	 * @param int $requested The ceiling a caller asked for, or 0 for none.
	 *
	 * @return int The ceiling in bytes.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function ceiling(int $requested=0): int {
		$administered = $this->appConfig->getValueInt(
			app: 'filinq',
			key: self::CONFIG_CEILING,
			default: self::DEFAULT_CEILING
		);
		if ($administered <= 0) {
			$administered = self::DEFAULT_CEILING;
		}

		if ($requested > 0 && $requested < $administered) {
			return $requested;
		}

		return $administered;

	}//end ceiling()

	/**
	 * Build the bundle of one object: one archive with the files and a manifest, recorded as a job.
	 *
	 * The archive is written to the requester's `Case bundles` folder as a
	 * zip holding every included file and `manifest.json`. A file that
	 * cannot be read at that moment moves to the excluded list with reason
	 * `missing`, so the manifest in the archive and the job agree. When the
	 * job cannot be recorded the archive is deleted again and the call
	 * fails: an unrecorded bundle is never handed over (REQ-DFT-02, #1210).
	 *
	 * @param array<string, mixed> $domain The object, as register, schema and id.
	 * @param int $requestedCeiling A lower ceiling the caller asks for, or 0.
	 *
	 * @return array<string, mixed> The manifest, plus `archive` with the new file's id, name and path.
	 *
	 * @throws RuntimeException When the archive cannot be written or the job cannot be recorded.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function build(array $domain, int $requestedCeiling=0): array {
		$ceiling  = $this->ceiling(requested: $requestedCeiling);
		$manifest = $this->manifestFor(domain: $domain, ceiling: $ceiling);

		$userId = $this->currentUserId();
		if ($userId === '') {
			throw new RuntimeException('No authenticated user', 401);
		}

		$written  = $this->writer->write(userId: $userId, domain: $domain, manifest: $manifest, ceiling: $ceiling);
		$archive  = $written['file'];
		$manifest = $written['manifest'];

		try {
			$this->record(domain: $domain, manifest: $manifest, ceiling: $ceiling, fileId: (int) $archive->getId());
		} catch (RuntimeException $e) {
			$this->writer->discard(file: $archive);
			throw $e;
		}

		$manifest['ceilingBytes'] = $ceiling;
		$manifest['archive']      = [
			'fileId' => (int) $archive->getId(),
			'name'   => $archive->getName(),
			'path'   => $written['path'],
		];

		return $manifest;

	}//end build()

	/**
	 * Record one bundle as a job, so what was handed over can be checked later.
	 *
	 * @param array<string, mixed> $domain The object.
	 * @param array<string, mixed> $manifest The manifest of what went in and what did not.
	 * @param int $ceiling The ceiling in force.
	 * @param int $fileId The archive's own file id, when one was written.
	 *
	 * @return array<string, mixed> The stored job.
	 *
	 * @throws RuntimeException When the job cannot be stored.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function record(array $domain, array $manifest, int $ceiling, int $fileId = 0): array {
		$job = [
			'subject' => $domain,
			'requestedBy' => $this->currentUserId(),
			'requestedAt' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
			'ceilingBytes' => $ceiling,
			'includedCount' => count(($manifest['included'] ?? [])),
			'excludedCount' => count(($manifest['excluded'] ?? [])),
			'totalBytes' => (int)($manifest['bytes'] ?? 0),
			'manifest' => array_merge(($manifest['included'] ?? []), ($manifest['excluded'] ?? [])),
			'fileId' => $fileId,
			'status' => 'completed',
		];

		// A job says completed only when there is an archive behind it
		// (#1210): without a file id it is recorded as failed, with the reason.
		if ($fileId <= 0) {
			$job['status'] = 'failed';
			$job['errorMessage'] = 'No archive was written.';
		}

		$this->storeJob(job: $job);

		return $job;

	}//end record()

	/**
	 * What a bundle of chosen files would be, before anybody builds it.
	 *
	 * @param array<int, int|string> $fileIds The chosen files.
	 * @param int                    $ceiling The ceiling in force, in bytes.
	 *
	 * @return array<string, mixed> The preflight (files, bytes, ceilingBytes, exceedsCeiling), with what would go in
	 *                              (included) and what would be left out (excluded).
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.4
	 */
	public function preflightFiles(array $fileIds, int $ceiling = self::DEFAULT_CEILING): array {
		$rows  = $this->chosenFiles(fileIds: $fileIds);
		$bytes = 0;
		foreach ($rows['readable'] as $row) {
			$bytes += (int) $row['size'];
		}

		$manifest = $this->manifestForFiles(fileIds: $fileIds, ceiling: $ceiling);

		return [
			'files' => count($rows['readable']),
			'bytes' => $bytes,
			'ceilingBytes' => $ceiling,
			'exceedsCeiling' => ($bytes > $ceiling),
			'included' => $manifest['included'],
			'excluded' => $manifest['excluded'],
		];

	}//end preflightFiles()

	/**
	 * The manifest of a bundle of chosen files: what goes in, and what does not.
	 *
	 * The same rules as manifestFor(): files in name order up to the ceiling,
	 * the rest left out with `ceiling`, and a file the requester may not read
	 * left out with `permission`.
	 *
	 * @param array<int, int|string> $fileIds The chosen files.
	 * @param int                    $ceiling The ceiling in force, in bytes.
	 *
	 * @return array{included: array<int, array<string, mixed>>, excluded: array<int, array<string, mixed>>, bytes: int} The manifest.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.4
	 */
	public function manifestForFiles(array $fileIds, int $ceiling = self::DEFAULT_CEILING): array {
		$rows     = $this->chosenFiles(fileIds: $fileIds);
		$included = [];
		$excluded = [];
		$bytes    = 0;

		foreach ($rows['readable'] as $row) {
			if (($bytes + $row['size']) > $ceiling) {
				$excluded[] = [
					'fileId' => $row['fileId'],
					'name' => $row['name'],
					'record' => '',
					'reason' => self::REASON_CEILING,
					'explanation' => 'The bundle would go over the administered ceiling.',
				];
				continue;
			}

			$bytes     += $row['size'];
			$included[] = [
				'fileId' => $row['fileId'],
				'name' => $row['name'],
				'record' => '',
				'size' => $row['size'],
				'version' => $row['version'],
			];
		}

		foreach ($rows['unreadable'] as $fileId) {
			$excluded[] = [
				'fileId' => $fileId,
				'name' => '',
				'record' => '',
				'reason' => self::REASON_PERMISSION,
				'explanation' => 'The person asking for the bundle may not read this file.',
			];
		}

		return ['included' => $included, 'excluded' => $excluded, 'bytes' => $bytes];

	}//end manifestForFiles()

	/**
	 * Build one archive of chosen files with its manifest, recorded as a job.
	 *
	 * The archive is written to the folder given, under the requester's Files.
	 * When the job cannot be recorded the archive is deleted again and the
	 * call fails, so a share never goes out on an unrecorded bundle (D4).
	 *
	 * @param array<int, int|string> $fileIds          The chosen files.
	 * @param string                 $folderPath       The folder, relative to the requester's Files root.
	 * @param string                 $name             The bundle's name; `.zip` is added.
	 * @param int                    $requestedCeiling A lower ceiling the caller asks for, or 0.
	 *
	 * @return array<string, mixed> The manifest, plus `archive` (fileId, name, path) and `job` (the stored job, with its uuid).
	 *
	 * @throws RuntimeException When nothing can go in (400), there is no user (401), or the archive or job cannot be written (500).
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.4
	 */
	public function buildFromFiles(array $fileIds, string $folderPath, string $name, int $requestedCeiling = 0): array {
		$ceiling  = $this->ceiling(requested: $requestedCeiling);
		$manifest = $this->manifestForFiles(fileIds: $fileIds, ceiling: $ceiling);

		$userId = $this->currentUserId();
		if ($userId === '') {
			throw new RuntimeException('No authenticated user', 401);
		}

		if ($manifest['included'] === []) {
			throw new RuntimeException('None of the chosen files can go into the bundle', 400);
		}

		$subject = ['kind' => 'files', 'name' => $name, 'fileIds' => array_column($manifest['included'], 'fileId')];
		$written = $this->writer->writeTo(
			userId: $userId,
			subject: $subject,
			manifest: $manifest,
			ceiling: $ceiling,
			folderPath: $folderPath,
			archiveName: $name
		);
		$archive  = $written['file'];
		$manifest = $written['manifest'];

		$job = [
			'subject' => $subject,
			'requestedBy' => $userId,
			'requestedAt' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
			'ceilingBytes' => $ceiling,
			'includedCount' => count($manifest['included']),
			'excludedCount' => count(($manifest['excluded'] ?? [])),
			'totalBytes' => (int) ($manifest['bytes'] ?? 0),
			'manifest' => array_merge($manifest['included'], ($manifest['excluded'] ?? [])),
			'fileId' => (int) $archive->getId(),
			'status' => 'completed',
		];

		try {
			$job['uuid'] = $this->storeJob(job: $job);
		} catch (RuntimeException $e) {
			$this->writer->discard(file: $archive);
			throw $e;
		}

		$manifest['ceilingBytes'] = $ceiling;
		$manifest['archive']      = [
			'fileId' => (int) $archive->getId(),
			'name'   => $archive->getName(),
			'path'   => $written['path'],
		];
		$manifest['job']          = $job;

		return $manifest;

	}//end buildFromFiles()

	/**
	 * Save one archive job.
	 *
	 * @param array<string, mixed> $job The job.
	 *
	 * @return string The stored job's uuid, or an empty string when OpenRegister did not say.
	 *
	 * @throws RuntimeException When the job cannot be stored.
	 */
	private function storeJob(array $job): string {
		try {
			$stored = $this->objectResolver->resolve()->saveObject(
				object: $job,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA
			);
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[CaseArchiveService] could not record the archive job',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);
			// A bundle nobody can check afterwards is not handed over: the
			// caller stops rather than returning an unrecorded archive.
			throw new RuntimeException('The archive job could not be recorded', 500, $e);
		}

		if (is_object($stored) === true && method_exists($stored, 'getUuid') === true) {
			return (string) $stored->getUuid();
		}

		if (is_array($stored) === true) {
			return (string) ($stored['uuid'] ?? ($stored['id'] ?? ''));
		}

		return '';

	}//end storeJob()

	/**
	 * The chosen files, split into those the requester can read and those not.
	 *
	 * Ids are taken once each; readable files come back in name order.
	 *
	 * @param array<int, int|string> $fileIds The chosen files.
	 *
	 * @return array{readable: array<int, array{fileId: int, name: string, size: int, version: string}>, unreadable: array<int, int>} The split.
	 */
	private function chosenFiles(array $fileIds): array {
		$readable   = [];
		$unreadable = [];
		$seen       = [];
		foreach ($fileIds as $raw) {
			$fileId = (int) $raw;
			if ($fileId <= 0 || isset($seen[$fileId]) === true) {
				continue;
			}

			$seen[$fileId] = true;
			$node = $this->readable->node(fileId: $fileId);
			if (($node instanceof File) === false) {
				$unreadable[] = $fileId;
				continue;
			}

			$readable[] = [
				'fileId' => $fileId,
				'name' => (string) $node->getName(),
				'size' => (int) $node->getSize(),
				'version' => (string) $node->getEtag(),
			];
		}

		usort(
			$readable,
			static function (array $a, array $b): int {
				$byName = strnatcasecmp($a['name'], $b['name']);
				if ($byName !== 0) {
					return $byName;
				}

				return ($a['fileId'] <=> $b['fileId']);
			}
		);

		return ['readable' => $readable, 'unreadable' => $unreadable];

	}//end chosenFiles()

	/**
	 * The files of the object this person can actually read.
	 *
	 * @param array<string, mixed> $domain The object.
	 *
	 * @return array<int, array<string, mixed>> The rows.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	private function readable(array $domain): array {
		$page = $this->files->listFor(domain: $domain, page: 1, limit: 10000);

		return ($page['results'] ?? []);

	}//end readable()

	/**
	 * The records of the object whose file this person cannot read.
	 *
	 * The flat list leaves those out, which is right for a list and wrong for a
	 * manifest: a bundle has to say that something was left out on permission,
	 * or the reader cannot tell a complete bundle from a filtered one.
	 *
	 * @param array<string, mixed> $domain The object.
	 *
	 * @return array<int, array<string, mixed>> The records whose file did not come back.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	private function unreadable(array $domain): array {
		$visible = [];
		foreach ($this->readable(domain: $domain) as $row) {
			$visible[(int)($row['fileId'] ?? 0)] = true;
		}

		$missing = [];
		foreach ($this->files->recordsFor(domain: $domain) as $record) {
			$fileId = (int)($record['fileId'] ?? 0);
			if ($fileId > 0 && isset($visible[$fileId]) === false) {
				$missing[] = [
					'fileId' => $fileId,
					'name' => (string)($record['documentName'] ?? ''),
					'uuid' => (string)($record['uuid'] ?? ''),
				];
			}
		}

		return $missing;

	}//end unreadable()
}//end class
