<?php

/**
 * Contract of DocumentDomainService
 *
 * The public surface of the DocumentDomainService service, so callers in other
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

use RuntimeException;

/**
 * Contract of DocumentDomainService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface DocumentDomainServiceInterface {
	/**
	 * Link one document record to one domain.
	 *
	 * Linking the same domain twice changes nothing: a list of domains is a
	 * set, and a duplicate would make the record look like it serves four
	 * domains when it serves three.
	 *
	 * @param string $uuid The document record.
	 * @param array<string, mixed> $domain The domain, as register, schema and id.
	 *
	 * @return array<string, mixed> The record, with its domains.
	 *
	 * @throws RuntimeException When the record does not exist or the domain is incomplete.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function link(string $uuid, array $domain): array;

	/**
	 * Unlink one domain from one document record.
	 *
	 * @param string $uuid The document record.
	 * @param array<string, mixed> $domain The domain to remove.
	 *
	 * @return array<string, mixed> The record, which still exists.
	 *
	 * @throws RuntimeException When the record does not exist or the domain is incomplete.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function unlink(string $uuid, array $domain): array;

	/**
	 * The document records one person created.
	 *
	 * @param string $userId The user id, or an empty string for the current user.
	 *
	 * @return array<int, array<string, mixed>> Their records.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function listMine(string $userId = ''): array;
}//end interface
