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
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Kernel\Contracts\FlatFileListServiceInterface;
use OCP\Files\Node;
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
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 */
class FlatFileListService implements FlatFileListServiceInterface {

	/**
	 * Constructor.
	 *
	 * @param FinalDocumentRepository $repository The document record store.
	 * @param ReadableFileResolver $files The file behind an id, as the reader may read it.
	 * @param ContentSearchClient|null $content OpenRegister's content query, for the content mode.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
	 */
	public function __construct(
		private readonly FinalDocumentRepository $repository,
		private readonly ReadableFileResolver $files,
		private readonly ?ContentSearchClient $content=null,
	) {

	}//end __construct()

	/**
	 * Every file on one object, one page of it.
	 *
	 * In mode `content` with a search, the page lists the name matches first,
	 * then the files OpenRegister finds the words in, best rank first, each
	 * with its snippet; `content` says whether the query was there and how
	 * many listed files have no extracted text yet (REQ-WCS-001, REQ-WCS-002).
	 *
	 * @param array<string, mixed> $domain The object, as register, schema and id.
	 * @param int $page The page to read, from 1.
	 * @param int $limit How many rows a page holds.
	 * @param string $search A name filter, matched case-insensitively on the file name.
	 * @param string $mode `name` (the default) or `content`, which also searches the words in the files.
	 *
	 * @return array{results: array<int, array<string, mixed>>, total: int, page: int, pages: int, content?: array{available: bool, unread: int}} The page.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
	 */
	public function listFor(array $domain, int $page = 1, int $limit = 50, string $search = '', string $mode = 'name'): array {
		$rows     = [];
		$dossiers = null;
		foreach ($this->repository->findByDomain(domain: $domain) as $record) {
			$dossiers ??= $this->repository->dossierIndex();
			$row        = $this->row(record: $record, dossiers: $dossiers);
			if ($row !== null) {
				$rows[] = $row;
			}
		}

		usort(
			$rows,
			static fn (array $left, array $right): int => strcasecmp((string)$left['name'], (string)$right['name'])
		);

		$content = null;
		if ($mode === 'content' && trim($search) !== '') {
			[$rows, $content] = $this->withContent(rows: $rows, search: $search);
		} else if ($search !== '') {
			$rows = array_values(
				array_filter($rows, static fn (array $row): bool => stripos((string)$row['name'], $search) !== false)
			);
		}

		$total = count($rows);
		$limit = max(1, $limit);
		$pages = (int)ceil(($total / $limit));
		$page = max(1, $page);

		$answer = [
			'results' => array_slice($rows, (($page - 1) * $limit), $limit),
			'total' => $total,
			'page' => $page,
			'pages' => $pages,
		];
		if ($content !== null) {
			$answer['content'] = $content;
		}

		return $answer;

	}//end listFor()

	/**
	 * The name matches, then the content matches with their snippets.
	 *
	 * Only the files of this list are asked about, and a file the query does
	 * not return is not listed as a content match (REQ-WCS-003).
	 *
	 * @param array<int, array<string, mixed>> $rows The readable rows, in name order.
	 * @param string $search The words.
	 *
	 * @return array{0: array<int, array<string, mixed>>, 1: array{available: bool, unread: int}} The rows, and what the content query said.
	 *
	 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
	 */
	private function withContent(array $rows, string $search): array {
		$byName = [];
		$byFile = [];
		foreach ($rows as $row) {
			if (stripos((string)$row['name'], $search) !== false) {
				$byName[] = $row + ['match' => 'name'];
				continue;
			}

			$byFile[(int)$row['fileId']] = $row;
		}

		if ($this->content === null) {
			return [$byName, ['available' => false, 'unread' => 0]];
		}

		$answer = $this->content->search(
			words: $search,
			fileIds: array_map(static fn (array $row): int => (int)$row['fileId'], $rows)
		);

		$byContent = [];
		foreach ($answer['hits'] as $hit) {
			if (isset($byFile[$hit['fileId']]) === false) {
				continue;
			}

			$byContent[] = $byFile[$hit['fileId']] + ['match' => 'content', 'snippet' => $hit['snippet']];
		}

		return [
			array_merge($byName, $byContent),
			['available' => $answer['available'], 'unread' => count($answer['unread'])],
		];

	}//end withContent()

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
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function recordsFor(array $domain): array {
		return $this->repository->findByDomain(domain: $domain);

	}//end recordsFor()

	/**
	 * One row of the flat list: the file, and the record it belongs to.
	 *
	 * A record whose file the reader cannot see is LEFT OUT rather than listed
	 * as a name with nothing behind it. A file in an object's folder is seen
	 * through the read right on that object (ReadableFileResolver).
	 *
	 * @param array<string, mixed> $record The document record.
	 * @param DossierFileIndex|null $dossiers The dossiers, by member file and by folder.
	 *
	 * @return array<string, mixed>|null The row, or null when there is no readable file; `dossier` names the dossier the file is in, or is null.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	private function row(array $record, ?DossierFileIndex $dossiers=null): ?array {
		$fileId = (int)($record['fileId'] ?? 0);
		if ($fileId <= 0) {
			return null;
		}

		$node = $this->files->node(fileId: $fileId);
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
			'dossier' => $dossiers?->dossierOf(fileId: $fileId, folderId: $this->folderOf(node: $node)),
		];

	}//end row()

	/**
	 * The id of the folder a file is in, 0 when it has none to give.
	 *
	 * @param Node $node The file.
	 *
	 * @return int
	 */
	private function folderOf(Node $node): int {
		try {
			return (int) $node->getParent()->getId();
		} catch (Throwable) {
			return 0;
		}

	}//end folderOf()
}//end class
