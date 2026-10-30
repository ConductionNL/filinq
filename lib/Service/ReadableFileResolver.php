<?php

/**
 * Readable File Resolver
 *
 * Resolves a file id to the file the signed-in reader may read. A file in the
 * reader's own Files (owned, shared, group folder) resolves there. A file in
 * an OpenRegister object's folder lives in the openregister account's home,
 * which no reader has a mount on, so it resolves only when the reader may
 * read the object that owns it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/object-files-read-through-the-record/specs/document-register/spec.md
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
 * The file behind one id, as the signed-in reader may read it.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/object-files-read-through-the-record/specs/document-register/spec.md
 */
class ReadableFileResolver {

	/**
	 * OpenRegister's read check for a file: an object's file follows the object's read rule.
	 *
	 * @var string
	 */
	public const OR_READ_SCOPE = 'OCA\OpenRegister\Service\File\FileReadScope';

	/**
	 * OpenRegister's file service, which finds a file in an object's folder.
	 *
	 * @var string
	 */
	public const OR_FILE_SERVICE = 'OCA\OpenRegister\Service\FileService';

	/**
	 * Constructor.
	 *
	 * @param IRootFolder $rootFolder The file tree.
	 * @param IUserSession $userSession The current session.
	 * @param OpenRegisterServiceLocator $locator Resolver for OpenRegister services.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly OpenRegisterServiceLocator $locator,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The file behind one id, or null when the signed-in reader may not read it.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return Node|null The node, or null when there is no reader or the reader may not read it.
	 *
	 * @spec openspec/changes/object-files-read-through-the-record/specs/document-register/spec.md
	 */
	public function node(int $fileId): ?Node {
		$user = $this->userSession->getUser();
		if ($user === null || $fileId <= 0) {
			return null;
		}

		try {
			$nodes = $this->rootFolder->getUserFolder($user->getUID())->getById($fileId);
		} catch (Throwable $e) {
			$this->logger->debug(
				message: '[ReadableFileResolver] could not resolve a file in the reader\'s Files',
				context: ['file' => __FILE__, 'line' => __LINE__, 'fileId' => $fileId, 'error' => $e->getMessage()]
			);
			$nodes = [];
		}

		if ($nodes !== []) {
			return $nodes[0];
		}

		return $this->throughOwningObject(fileId: $fileId);

	}//end node()

	/**
	 * The file in an object's folder, opened only after the object's read rule admits the reader.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return Node|null The node, or null when no object admits the reader or OpenRegister is absent.
	 */
	private function throughOwningObject(int $fileId): ?Node {
		try {
			if ($this->locator->get(className: self::OR_READ_SCOPE)->mayReadFile($fileId) !== true) {
				return null;
			}

			$file = $this->locator->get(className: self::OR_FILE_SERVICE)->getFileById($fileId);
		} catch (Throwable $e) {
			$this->logger->debug(
				message: '[ReadableFileResolver] could not resolve a file through the object that owns it',
				context: ['file' => __FILE__, 'line' => __LINE__, 'fileId' => $fileId, 'error' => $e->getMessage()]
			);

			return null;
		}

		if (($file instanceof Node) === false) {
			return null;
		}

		return $file;

	}//end throughOwningObject()
}//end class
