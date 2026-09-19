<?php

/**
 * Intake File Placement
 *
 * Moves the file of an assigned intake document into the folder of the record
 * it was assigned to, when that record owns one. A record without a folder is
 * an ordinary case, not a failure: the link on the intake document is what
 * makes the document findable, and the move is a convenience on top of it.
 *
 * So every failure here is logged and answered with null. An assignment that
 * refused to complete because a folder was missing would strand the document in
 * the inbox with no way forward.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Puts an assigned document in the folder of the record it belongs to.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */
class IntakeFilePlacement {

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param IRootFolder $rootFolder The file tree.
	 * @param IUserSession $userSession The current session.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Move one file into the folder of one record.
	 *
	 * @param int $fileId The Nextcloud file id, or 0 when the intake document carries no file.
	 * @param string $register The target register slug.
	 * @param string $schema The target schema slug.
	 * @param string $id The target object id.
	 *
	 * @return string|null The path the file now sits at, or null when nothing moved.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function place(int $fileId, string $register, string $schema, string $id): ?string {
		if ($fileId <= 0) {
			return null;
		}

		$user = $this->userSession->getUser();
		if ($user === null) {
			return null;
		}

		$folder = $this->folderOf(register: $register, schema: $schema, id: $id);
		if ($folder === null) {
			return null;
		}

		try {
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			$nodes = $userFolder->getById($fileId);
			if ($nodes === []) {
				return null;
			}

			if ($userFolder->nodeExists($folder) === false) {
				$this->logger->info(
					message: '[IntakeFilePlacement] the record has a folder the clerk cannot see, leaving the file where it is',
					context: ['file' => __FILE__, 'line' => __LINE__, 'folder' => $folder, 'fileId' => $fileId]
				);

				return null;
			}

			$node = $nodes[0];
			$destination = $userFolder->getPath() . '/' . $folder . '/' . $node->getName();
			$node->move($destination);

			return $destination;
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[IntakeFilePlacement] could not move the assigned file, the assignment itself stands',
				context: [
					'file' => __FILE__,
					'line' => __LINE__,
					'fileId' => $fileId,
					'error' => $e->getMessage(),
				]
			);

			return null;
		}//end try

	}//end place()

	/**
	 * The folder one record owns, relative to the user's files root.
	 *
	 * @param string $register The register slug.
	 * @param string $schema The schema slug.
	 * @param string $id The object id.
	 *
	 * @return string|null The folder, or null when the record owns none.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	private function folderOf(string $register, string $schema, string $id): ?string {
		try {
			$object = $this->objectResolver->resolve()->find(
				id: $id,
				register: $register,
				schema: $schema
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[IntakeFilePlacement] could not read the target record',
				context: ['file' => __FILE__, 'line' => __LINE__, 'id' => $id, 'error' => $e->getMessage()]
			);

			return null;
		}

		if ($object === null) {
			return null;
		}

		return $this->folderIn(object: $object);

	}//end folderOf()

	/**
	 * The folder path a record carries, read from either place it can sit.
	 *
	 * OpenRegister puts it under `@self.folder`; a record written before that
	 * carries a plain `folder`. Both are read, because a record that has one
	 * and is read as having none goes to the inbox root instead of its case.
	 *
	 * @param mixed $object The record, as OpenRegister returned it.
	 *
	 * @return string|null The folder path, or null when it names none.
	 *
	 * @spec exclude Shape adapter over an OpenRegister response.
	 */
	private function folderIn(mixed $object): ?string {
		$data = $object;
		if (is_object($object) === true && method_exists($object, 'jsonSerialize') === true) {
			$data = $object->jsonSerialize();
		}

		if (is_array($data) === false) {
			return null;
		}

		$folder = '';
		if (isset($data['@self']['folder']) === true) {
			$folder = (string)$data['@self']['folder'];
		} else if (isset($data['folder']) === true && is_string($data['folder']) === true) {
			$folder = $data['folder'];
		}

		$folder = trim($folder, '/ ');
		if ($folder === '') {
			return null;
		}

		return $folder;

	}//end folderIn()
}//end class
