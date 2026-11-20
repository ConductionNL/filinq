<?php

/**
 * Content Hasher
 *
 * The SHA-256 of a stored file's bytes, read as a stream so a large scan is
 * never held in memory. Exact content only: the same letter scanned twice
 * gives different bytes and a different hash.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Intake
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-an-arrival-whose-content-came-before-is-marked-never-refused-req-iws-003
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Intake;

use OCP\Files\File;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Hashes a file by its Nextcloud id.
 *
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-an-arrival-whose-content-came-before-is-marked-never-refused-req-iws-003
 */
class ContentHasher {

	/**
	 * How many bytes one read hashes.
	 *
	 * @var int
	 */
	private const CHUNK_BYTES = 1048576;

	/**
	 * Constructor.
	 *
	 * @param IRootFolder     $rootFolder The file tree, read without a user: a channel delivers without a session.
	 * @param LoggerInterface $logger     Logs a file that could not be read.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The lowercase hex SHA-256 of a file's bytes, or null when it cannot be read.
	 *
	 * An unreadable file is no reason to refuse an arrival: the document is
	 * kept without a hash, and so is never marked as anyone's duplicate.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return string|null The digest, or null.
	 *
	 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-an-arrival-whose-content-came-before-is-marked-never-refused-req-iws-003
	 */
	public function hashOf(int $fileId): ?string {
		if ($fileId <= 0) {
			return null;
		}

		try {
			$node = $this->rootFolder->getFirstNodeById($fileId);
			if (($node instanceof File) === false) {
				return null;
			}

			$handle = $node->fopen('r');
			if (is_resource($handle) === false) {
				return null;
			}

			try {
				$context = hash_init('sha256');
				while (hash_update_stream($context, $handle, self::CHUNK_BYTES) > 0) {
					// Each pass hashes the next chunk.
				}

				return hash_final($context);
			} finally {
				fclose($handle);
			}
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[ContentHasher] could not read the file to hash it, keeping the document without a hash',
				context: ['file' => __FILE__, 'line' => __LINE__, 'fileId' => $fileId, 'error' => $e->getMessage()]
			);
			return null;
		}//end try

	}//end hashOf()
}//end class
