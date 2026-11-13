<?php

/**
 * The boundary between sharing a document by link and Nextcloud's share manager.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Share
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/share-document-links/specs/document-share-links/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Share;

use DateTimeImmutable;
use RuntimeException;

/**
 * Makes, removes and reads Nextcloud public link shares.
 *
 * `OCP\Share\IManager` does not exist under the unit bootstrap, so the
 * service talks to this seam and PHPUnit drives it through a stub, like
 * DomainFolderGateway (design D2).
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-2.1
 */
interface LinkShareGateway {

	/**
	 * Make a read-only public link on one file, as that user.
	 *
	 * @param int               $fileId   The Nextcloud file id, as the user sees it.
	 * @param string            $uid      The user who shares.
	 * @param DateTimeImmutable $expires  The day the link stops working.
	 * @param string|null       $password The link password, or null for none.
	 * @param string            $note     Who the link is for.
	 *
	 * @return array{id: string, url: string} The share id and the link.
	 *
	 * @throws RuntimeException With Nextcloud's reason when it refuses the share.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.1
	 */
	public function createLink(int $fileId, string $uid, DateTimeImmutable $expires, ?string $password, string $note): array;

	/**
	 * Remove a link share. A share that is already gone is not an error.
	 *
	 * @param string $shareId The share id.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.1
	 */
	public function delete(string $shareId): void;

	/**
	 * Whether the link share still exists in Nextcloud.
	 *
	 * @param string $shareId The share id.
	 *
	 * @return bool True while the share exists.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.1
	 */
	public function exists(string $shareId): bool;

	/**
	 * Nextcloud's rules for public links.
	 *
	 * @return array{maxDays: int|null, passwordRequired: bool} The enforced maximum expiry in days (null when none) and
	 *                                                          whether a link needs a password.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.1
	 */
	public function linkPolicy(): array;
}//end interface
