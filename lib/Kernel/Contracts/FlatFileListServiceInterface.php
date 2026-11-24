<?php

/**
 * Contract of FlatFileListService
 *
 * The public surface of the FlatFileListService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

/**
 * Contract of FlatFileListService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface FlatFileListServiceInterface {

	/**
	 * The most files one search of a list may ask about.
	 *
	 * @var int
	 */
	public const MAX_SEARCH_FILES = 500;

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
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function listFor(array $domain, int $page = 1, int $limit = 50, string $search = ''): array;

	/**
	 * Every file on one object whose name or content holds the words, one page of it.
	 *
	 * @param array<string, mixed> $domain The object, as register, schema and id.
	 * @param string $search The words.
	 * @param int $page The page to read, from 1.
	 * @param int $limit How many rows a page holds.
	 *
	 * @return array<string, mixed> The page: results, total, page, pages, and content as {available, unread}.
	 *
	 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
	 */
	public function searchContent(array $domain, string $search, int $page = 1, int $limit = 50): array;

	/**
	 * Which of the given files hold the words, among those the caller may read.
	 *
	 * @param string           $words   The words.
	 * @param array<int, mixed> $fileIds The files of the caller's list, at most MAX_SEARCH_FILES.
	 *
	 * @return array{available: bool, hits: array<int, array{fileId: int, rank: float, snippet: string}>, unread: array<int, int>} The answer.
	 *
	 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
	 */
	public function searchFiles(string $words, array $fileIds): array;

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
	public function recordsFor(array $domain): array;
}//end interface
