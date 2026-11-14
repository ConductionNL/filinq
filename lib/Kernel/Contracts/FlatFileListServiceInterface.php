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
