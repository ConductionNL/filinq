<?php

/**
 * Document Version Service
 *
 * Thin, read/restore consumer of the Nextcloud `files_versions` capability for a
 * document's underlying Nextcloud file. Introduces NO Filinq-owned version
 * storage: versions are read exclusively from Nextcloud via `IVersionManager`.
 * Subjects are resolved through the requesting user's folder, so a file the user
 * cannot read is indistinguishable from a non-existent one (IDOR-safe, ADR-005),
 * and restore requires write access.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-versions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Exception\ComparisonException;
use OCP\App\IAppManager;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Service listing, reading and restoring a document's Nextcloud file versions.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-versions/spec.md
 */
class DocumentVersionService {
	/**
	 * Fully-qualified IVersionManager class (resolved lazily so the app
	 * degrades gracefully when files_versions is disabled).
	 *
	 * @var string
	 */
	private const VERSION_MANAGER = 'OCA\Files_Versions\Versions\IVersionManager';

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger Logger for diagnostics.
	 * @param IRootFolder $rootFolder Root folder for user-scoped file access.
	 * @param IUserSession $userSession Current user session.
	 * @param IAppManager $appManager App manager (files_versions availability).
	 * @param ContainerInterface $container DI container for lazy IVersionManager resolution.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly IAppManager $appManager,
		private readonly ContainerInterface $container,
	) {

	}//end __construct()

	/**
	 * List a document's Nextcloud file versions, newest first.
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param int $limit Maximum entries to return.
	 * @param int $offset Entries to skip (pagination).
	 *
	 * @return array<int, array<string, mixed>> The version entries (newest first).
	 *
	 * @throws ComparisonException 404 (not readable) / 422 (versions-unavailable).
	 *
	 * @spec openspec/specs/document-versions/spec.md
	 */
	public function listVersions(int $fileId, int $limit = 50, int $offset = 0): array {
		$file = $this->resolveFile(fileId: $fileId, requireWrite: false);
		$versionManager = $this->resolveVersionManager();
		$user = $this->requireUser();

		$entries = [];

		// The current file content is itself the newest version.
		$entries[] = [
			'timestamp' => $file->getMTime(),
			'author' => $this->fileAuthor(file: $file),
			'size' => $file->getSize(),
			'label' => '',
			'isCurrent' => true,
		];

		try {
			$versions = $versionManager->getVersionsForFile($user, $file);
			foreach ($versions as $version) {
				$entries[] = [
					'timestamp' => (int)$version->getTimestamp(),
					'author' => $this->versionAuthor(version: $version, fallback: $entries[0]['author']),
					'size' => $this->versionSize(version: $version),
					'label' => $this->versionLabel(version: $version),
					'isCurrent' => false,
				];
			}
		} catch (Throwable $e) {
			$this->logger->debug('Version listing failed', ['fileId' => $fileId, 'exception' => $e->getMessage()]);
			throw new ComparisonException(statusCode: 422, reason: 'versions-unavailable', message: 'version manager error');
		}

		// Newest first.
		usort($entries, static fn (array $a, array $b): int => ($b['timestamp'] <=> $a['timestamp']));

		return array_slice($entries, $offset, $limit);
	}//end listVersions()

	/**
	 * Read the bytes of a specific version (for open/download).
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param int $versionTimestamp The version timestamp (0 = current file).
	 *
	 * @return string The version bytes.
	 *
	 * @throws ComparisonException 404 / 422.
	 *
	 * @spec openspec/specs/document-versions/spec.md
	 */
	public function readVersion(int $fileId, int $versionTimestamp): string {
		$file = $this->resolveFile(fileId: $fileId, requireWrite: false);

		if ($versionTimestamp === 0) {
			try {
				return $file->getContent();
			} catch (Throwable $e) {
				throw new ComparisonException(statusCode: 404, reason: 'not-found', message: 'version');
			}
		}

		$versionManager = $this->resolveVersionManager();
		$user = $this->requireUser();

		try {
			foreach ($versionManager->getVersionsForFile($user, $file) as $version) {
				if ((int)$version->getTimestamp() === $versionTimestamp) {
					$content = $versionManager->read($version);
					if (is_resource($content) === true) {
						$content = (string)stream_get_contents($content);
					}

					return (string)$content;
				}
			}
		} catch (Throwable $e) {
			$this->logger->debug('Version read failed', ['fileId' => $fileId, 'exception' => $e->getMessage()]);
		}

		throw new ComparisonException(statusCode: 404, reason: 'not-found', message: 'version');
	}//end readVersion()

	/**
	 * Restore a prior version. Requires write access; Nextcloud preserves the
	 * current state as a new version on rollback.
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param int $versionTimestamp The version timestamp to restore.
	 *
	 * @return void
	 *
	 * @throws ComparisonException 404 (not writeable) / 422 / 404 (unknown version).
	 *
	 * @spec openspec/specs/document-versions/spec.md
	 */
	public function restoreVersion(int $fileId, int $versionTimestamp): void {
		$file = $this->resolveFile(fileId: $fileId, requireWrite: true);
		$versionManager = $this->resolveVersionManager();
		$user = $this->requireUser();

		try {
			foreach ($versionManager->getVersionsForFile($user, $file) as $version) {
				if ((int)$version->getTimestamp() === $versionTimestamp) {
					$versionManager->rollback($version);
					return;
				}
			}
		} catch (ComparisonException $e) {
			throw $e;
		} catch (Throwable $e) {
			$this->logger->debug('Version restore failed', ['fileId' => $fileId, 'exception' => $e->getMessage()]);
			throw new ComparisonException(statusCode: 422, reason: 'versions-unavailable', message: 'version manager error');
		}

		throw new ComparisonException(statusCode: 404, reason: 'not-found', message: 'version');
	}//end restoreVersion()

	/**
	 * Resolve a file through the requesting user's folder, enforcing read or
	 * write access. Returns the File node or throws 404 — without distinguishing
	 * "does not exist" from "no access" (ADR-005).
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param bool $requireWrite Whether write (update) permission is required.
	 *
	 * @return File The resolved file.
	 *
	 * @throws ComparisonException 404 when not resolvable or insufficient access.
	 */
	private function resolveFile(int $fileId, bool $requireWrite): File {
		$user = $this->userSession->getUser();
		if ($user === null || $fileId <= 0) {
			throw new ComparisonException(statusCode: 404, reason: 'not-found', message: 'Document not found.');
		}

		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$nodes = $userFolder->getById($fileId);
		if (empty($nodes) === true) {
			throw new ComparisonException(statusCode: 404, reason: 'not-found', message: 'Document not found.');
		}

		$node = $nodes[0];
		if (($node instanceof File) === false) {
			throw new ComparisonException(statusCode: 404, reason: 'not-found', message: 'Document not found.');
		}

		$needed = Constants::PERMISSION_READ;
		if ($requireWrite === true) {
			$needed = Constants::PERMISSION_UPDATE;
		}

		if (($node->getPermissions() & $needed) !== $needed) {
			// No write (or read) access — 404, no existence disclosure.
			throw new ComparisonException(statusCode: 404, reason: 'not-found', message: 'Document not found.');
		}

		return $node;
	}//end resolveFile()

	/**
	 * Resolve the IVersionManager, degrading gracefully when files_versions is off.
	 *
	 * @return mixed The IVersionManager instance.
	 *
	 * @throws ComparisonException 422 versions-unavailable.
	 */
	private function resolveVersionManager(): mixed {
		if ($this->appManager->isEnabledForUser('files_versions') === false) {
			throw new ComparisonException(statusCode: 422, reason: 'versions-unavailable', message: 'files_versions disabled');
		}

		try {
			return $this->container->get(self::VERSION_MANAGER);
		} catch (Throwable $e) {
			throw new ComparisonException(statusCode: 422, reason: 'versions-unavailable', message: 'version manager unavailable');
		}

	}//end resolveVersionManager()

	/**
	 * Require an authenticated user.
	 *
	 * @return \OCP\IUser The current user.
	 *
	 * @throws ComparisonException 404 when unauthenticated.
	 */
	private function requireUser(): \OCP\IUser {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new ComparisonException(statusCode: 404, reason: 'not-found', message: 'Document not found.');
		}

		return $user;
	}//end requireUser()

	/**
	 * Best-effort author (owner UID) of the current file.
	 *
	 * @param File $file The file.
	 *
	 * @return string The owner UID or ''.
	 */
	private function fileAuthor(File $file): string {
		try {
			$owner = $file->getOwner();
			if ($owner !== null) {
				return $owner->getUID();
			}
		} catch (Throwable $e) {
			$this->logger->debug('File owner lookup failed', ['exception' => $e->getMessage()]);
		}

		return '';
	}//end fileAuthor()

	/**
	 * Best-effort author of a version, tolerating NC API variations.
	 *
	 * @param mixed $version The IVersion object.
	 * @param string $fallback Fallback author when the version exposes none.
	 *
	 * @return string The author label.
	 */
	private function versionAuthor(mixed $version, string $fallback): string {
		if (method_exists($version, 'getAuthor') === true) {
			$author = $version->getAuthor();
			if (is_string($author) === true && $author !== '') {
				return $author;
			}
		}

		if (method_exists($version, 'getUser') === true) {
			$userObj = $version->getUser();
			if ($userObj !== null && method_exists($userObj, 'getUID') === true) {
				return (string)$userObj->getUID();
			}
		}

		return $fallback;
	}//end versionAuthor()

	/**
	 * Best-effort size of a version.
	 *
	 * @param mixed $version The IVersion object.
	 *
	 * @return int The size in bytes.
	 */
	private function versionSize(mixed $version): int {
		if (method_exists($version, 'getSize') === true) {
			return (int)$version->getSize();
		}

		return 0;
	}//end versionSize()

	/**
	 * Best-effort human label of a version.
	 *
	 * @param mixed $version The IVersion object.
	 *
	 * @return string The label, or ''.
	 */
	private function versionLabel(mixed $version): string {
		if (method_exists($version, 'getLabel') === true) {
			return (string)$version->getLabel();
		}

		return '';
	}//end versionLabel()
}//end class
