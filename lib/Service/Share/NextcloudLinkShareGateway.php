<?php

/**
 * Nextcloud's share manager behind LinkShareGateway.
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

use DateTime;
use DateTimeImmutable;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IURLGenerator;
use OCP\Share\IManager;
use OCP\Share\IShare;
use RuntimeException;
use Throwable;

/**
 * Makes read-only link shares through `OCP\Share\IManager` (design D1).
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-2.1
 */
class NextcloudLinkShareGateway implements LinkShareGateway {

	/**
	 * The provider prefix Nextcloud's share manager expects in a full share id.
	 *
	 * @var string
	 */
	private const PROVIDER = 'ocinternal:';

	/**
	 * Constructor.
	 *
	 * @param IRootFolder   $rootFolder   Finds the file as the user sees it.
	 * @param IManager      $shareManager Nextcloud's share manager.
	 * @param IURLGenerator $urls         Builds the public link.
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly IManager $shareManager,
		private readonly IURLGenerator $urls,
	) {

	}//end __construct()

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
	public function createLink(int $fileId, string $uid, DateTimeImmutable $expires, ?string $password, string $note): array {
		$node = $this->rootFolder->getUserFolder($uid)->getFirstNodeById($fileId);
		if (($node instanceof File) === false) {
			throw new RuntimeException('The file cannot be found', 404);
		}

		$share = $this->shareManager->newShare();
		$share->setNode($node)
			->setShareType(IShare::TYPE_LINK)
			->setPermissions(Constants::PERMISSION_READ)
			->setSharedBy($uid)
			->setShareOwner($node->getOwner()?->getUID() ?? $uid)
			->setExpirationDate(DateTime::createFromImmutable($expires))
			->setNote($note);
		if ($password !== null && $password !== '') {
			$share->setPassword($password);
		}

		try {
			$share = $this->shareManager->createShare($share);
		} catch (Throwable $e) {
			throw new RuntimeException($e->getMessage(), 403, $e);
		}

		return [
			'id' => (string) $share->getId(),
			'url' => $this->urls->linkToRouteAbsolute('files_sharing.sharecontroller.showShare', ['token' => $share->getToken()]),
		];

	}//end createLink()

	/**
	 * Remove a link share. A share that is already gone is not an error.
	 *
	 * @param string $shareId The share id.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.1
	 */
	public function delete(string $shareId): void {
		try {
			$share = $this->shareManager->getShareById(self::PROVIDER . $shareId);
		} catch (Throwable) {
			return;
		}

		$this->shareManager->deleteShare($share);

	}//end delete()

	/**
	 * Whether the link share still exists in Nextcloud.
	 *
	 * @param string $shareId The share id.
	 *
	 * @return bool True while the share exists.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.1
	 */
	public function exists(string $shareId): bool {
		try {
			$this->shareManager->getShareById(self::PROVIDER . $shareId);
			return true;
		} catch (Throwable) {
			return false;
		}

	}//end exists()

	/**
	 * Nextcloud's rules for public links.
	 *
	 * @return array{maxDays: int|null, passwordRequired: bool} The enforced maximum and the password rule.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.1
	 */
	public function linkPolicy(): array {
		$maxDays = null;
		if ($this->shareManager->shareApiLinkDefaultExpireDateEnforced() === true) {
			$maxDays = $this->shareManager->shareApiLinkDefaultExpireDays();
		}

		return ['maxDays' => $maxDays, 'passwordRequired' => $this->shareManager->shareApiLinkEnforcePassword()];

	}//end linkPolicy()
}//end class
