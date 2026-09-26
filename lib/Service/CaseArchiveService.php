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
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
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
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */
class CaseArchiveService {

	/**
	 * The schema holding the jobs.
	 *
	 * @var string
	 */
	public const SCHEMA = 'archiveJob';

	/**
	 * The ceiling used when an instance declared none, in bytes.
	 *
	 * @var int
	 */
	public const DEFAULT_CEILING = 2147483648;

	/**
	 * A file left out because the bundle would go over the ceiling.
	 *
	 * @var string
	 */
	public const REASON_CEILING = 'ceiling';

	/**
	 * A file left out because the person asking may not read it.
	 *
	 * @var string
	 */
	public const REASON_PERMISSION = 'permission';

	/**
	 * Constructor.
	 *
	 * @param FlatFileListService $files Every file on the object.
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param IUserSession $userSession The current session.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly FlatFileListService $files,
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
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
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
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
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
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
	 * Record one bundle as a job, so what was handed over can be checked later.
	 *
	 * @param array<string, mixed> $domain The object.
	 * @param array<string, mixed> $manifest The manifest of what went in and what did not.
	 * @param int $ceiling The ceiling in force.
	 * @param int $fileId The archive's own file id, when one was written.
	 *
	 * @return array<string, mixed> The stored job.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
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

		try {
			$this->objectResolver->resolve()->saveObject(
				object: $job,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA
			);
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[CaseArchiveService] could not record the archive job',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);
		}

		return $job;

	}//end record()

	/**
	 * The files of the object this person can actually read.
	 *
	 * @param array<string, mixed> $domain The object.
	 *
	 * @return array<int, array<string, mixed>> The rows.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
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
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
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
