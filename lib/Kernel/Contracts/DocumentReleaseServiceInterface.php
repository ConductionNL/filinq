<?php

/**
 * Contract of DocumentReleaseService
 *
 * The public surface of the DocumentReleaseService service, so callers in other
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

use OCA\Filinq\Exception\DomainLinkRefusedException;
use RuntimeException;

/**
 * Contract of DocumentReleaseService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface DocumentReleaseServiceInterface {
	/**
	 * Release one document to the person who requested a record.
	 *
	 * The caller must be allowed to write the record, and the document must
	 * be on it: a document cannot be released on a record it is not part of.
	 *
	 * @param string               $uuid       The document record.
	 * @param array<string, mixed> $record     The record, as register, schema and id.
	 * @param string               $contactRef The requester's contact reference.
	 * @param string               $kind       `decision` or `document`.
	 *
	 * @return array<string, mixed> The stored release.
	 *
	 * @throws DomainLinkRefusedException When the caller may not write the record.
	 * @throws RuntimeException When the request is incomplete or the release cannot be stored.
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-5.1
	 */
	public function release(string $uuid, array $record, string $contactRef, string $kind = 'document'): array;


	/**
	 * Withdraw a release, so the portal no longer lists or serves it.
	 *
	 * @param string $releaseId The release.
	 *
	 * @return array<string, mixed> The withdrawn release.
	 *
	 * @throws DomainLinkRefusedException When the caller may not write the release's record.
	 * @throws RuntimeException When there is no such release or it cannot be stored.
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-5.1
	 */
	public function withdraw(string $releaseId): array;


	/**
	 * The documents of one release as the portal lists them, without where the file lives in the browser.
	 *
	 * A withdrawn release, an unknown id or an unreadable store lists
	 * nothing. Portaliq calls this only for a release it has proven is the
	 * person's own, and keeps the `file` reference on the server.
	 *
	 * @param string $releaseId The release.
	 *
	 * @return array<int, array<string, mixed>> The entry, or none.
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-5.1
	 */
	public function portalEntries(string $releaseId): array;
}//end interface
