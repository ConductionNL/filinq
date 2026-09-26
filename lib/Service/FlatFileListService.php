<?php

/**
 * Flat File List Service
 *
 * Answers "what files are on this case", where the record list answers "what
 * documents does this case have". Two questions, one store: both read the same
 * document records, and neither replaces the other.
 *
 * Each file names the record it belongs to, because a flat list whose rows do
 * not say where they came from moves the work of finding the record from the
 * list to the reader.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Lists every file on every document record of one object.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */
class FlatFileListService {

	/**
	 * Constructor.
	 *
	 * @param FinalDocumentRepository $repository The document record store.
	 * @param IRootFolder $rootFolder The file tree.
	 * @param IUserSession $userSession The current session.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly FinalDocumentRepository $repository,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Every file on one object, one page of it.
	 *
	 * @param array<string, mixed> $domain The object, as register, schema and id.
	 * @param int $page The page to read, from 1.
	 * @param int $limit How many rows a page holds.
	 * @param string $search A name filter, matched case-insensitively on the file name.
	 *
	 * @return array{results: array<int, array<string, mixed>>, total: int, page: int, pages: int} The page.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function listFor(array $domain, int $page = 1, int $limit = 50, string $search = ''): array {
		$rows = [];
		foreach ($this->repository->findByDomain(domain: $domain) as $record) {
			$row = $this->row(record: $record);
			if ($row === null) {
				continue;
			}

			if ($search !== '' && stripos((string)$row['name'], $search) === false) {
				continue;
			}

			$rows[] = $row;
		}

		usort(
			$rows,
			static fn (array $left, array $right): int => strcasecmp((string)$left['name'], (string)$right['name'])
		);

		$total = count($rows);
		$limit = max(1, $limit);
		$pages = (int)ceil(($total / $limit));
		$page = max(1, $page);

		return [
			'results' => array_slice($rows, (($page - 1) * $limit), $limit),
			'total' => $total,
			'page' => $page,
			'pages' => $pages,
		];

	}//end listFor()

	/**
	 * Every document record of one object, readable file or not.
	 *
	 * The flat list leaves out a record whose file this reader cannot see,
	 * which is right for a list. A MANIFEST needs the other half: it has to say
	 * that something was left out, or a filtered bundle is indistinguishable
	 * from a complete one.
	 *
	 * @param array<string, mixed> $domain The object, as register, schema and id.
	 *
	 * @return array<int, array<string, mixed>> The records.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function recordsFor(array $domain): array {
		return $this->repository->findByDomain(domain: $domain);

	}//end recordsFor()

	/**
	 * One row of the flat list: the file, and the record it belongs to.
	 *
	 * A record whose file the reader cannot see is LEFT OUT rather than listed
	 * as a name with nothing behind it. The flat list obeys the same
	 * permissions as the folder it flattens.
	 *
	 * @param array<string, mixed> $record The document record.
	 *
	 * @return array<string, mixed>|null The row, or null when there is no readable file.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	private function row(array $record): ?array {
		$fileId = (int)($record['fileId'] ?? 0);
		if ($fileId <= 0) {
			return null;
		}

		$node = $this->node(fileId: $fileId);
		if ($node === null) {
			return null;
		}

		return [
			'fileId' => $fileId,
			'name' => $node->getName(),
			'path' => $node->getPath(),
			'size' => $node->getSize(),
			'mtime' => $node->getMTime(),
			'mimetype' => $node->getMimetype(),
			'record' => [
				'uuid' => (string)($record['uuid'] ?? ''),
				'name' => (string)($record['documentName'] ?? $node->getName()),
				'status' => (string)($record['status'] ?? ''),
			],
		];

	}//end row()

	/**
	 * The file behind one id, as the current user can see it.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return Node|null The node, or null when this user cannot see it.
	 *
	 * @spec exclude File lookup with no behaviour of its own.
	 */
	private function node(int $fileId): ?Node {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return null;
		}

		try {
			$nodes = $this->rootFolder->getUserFolder($user->getUID())->getById($fileId);
		} catch (Throwable $e) {
			$this->logger->debug(
				message: '[FlatFileListService] could not resolve a file of the flat list',
				context: ['file' => __FILE__, 'line' => __LINE__, 'fileId' => $fileId, 'error' => $e->getMessage()]
			);

			return null;
		}

		if ($nodes === []) {
			return null;
		}

		return $nodes[0];

	}//end node()
}//end class
