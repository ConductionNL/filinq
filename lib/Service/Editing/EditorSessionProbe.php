<?php

/**
 * Editor Session Probe
 *
 * Whether an office editor holds a document open, and which one. An editor
 * holds the authoritative copy while it is open, so an agent edit must then go
 * through it rather than under it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Editing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/edit-through-the-open-editor/tasks.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Editing;

use OCA\Filinq\Kernel\AppIdentity;
use OCP\Files\Lock\ILock;
use OCP\Files\Lock\ILockManager;
use OCP\Files\Lock\NoLockProviderException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Detects an editor session on a file from the locks held on it.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/edit-through-the-open-editor/specs/edit-through-the-open-editor/spec.md#requirement-the-editor-must-remain-the-only-writer-while-it-is-open
 */
class EditorSessionProbe {

	/**
	 * Constructor.
	 *
	 * @param ILockManager           $lockManager The lock registry editors write their locks into.
	 * @param EditorDeliveryRegistry $registry    The declared editors.
	 * @param LoggerInterface        $logger      The logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ILockManager $lockManager,
		private readonly EditorDeliveryRegistry $registry,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * The editor session holding a file, or null when no editor holds it.
	 *
	 * The probe only READS the locks. It never takes, extends or breaks one: the
	 * editor's lock is what keeps its copy the only one being written.
	 *
	 * A lock Filinq itself holds is not an editor. A lock under any other owner
	 * is, whether or not the registry declares it, so an undeclared editor is
	 * refused by name instead of being written underneath.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array{owner: string, suite: string, adapter: string|null}|null The session.
	 *
	 * @spec openspec/changes/edit-through-the-open-editor/tasks.md#1-know-whether-an-editor-holds-the-document
	 */
	public function attachedTo(int $fileId): ?array {
		try {
			$locks = $this->lockManager->getLocks($fileId);
		} catch (NoLockProviderException) {
			// No lock registry: nothing can be detected, and the direct path's own
			// warning already says a concurrent session could not be excluded.
			return null;
		} catch (Throwable $e) {
			$this->logger->warning(
				'Filinq could not read the locks on a file to detect an open editor',
				['fileId' => $fileId, 'exception' => $e->getMessage()]
			);
			return null;
		}

		foreach ($locks as $lock) {
			// Editors take APP locks. A user's own lock is not an editor session,
			// and the direct path already refuses it by name.
			if ($lock->getType() !== ILock::TYPE_APP) {
				continue;
			}

			$owner = trim($lock->getOwner());
			if ($owner === '' || strcasecmp($owner, AppIdentity::APP_ID) === 0) {
				continue;
			}

			$declared = $this->registry->forLockOwner(owner: $owner);
			if ($declared === null) {
				return ['owner' => $owner, 'suite' => $owner, 'adapter' => null];
			}

			return ['owner' => $owner, 'suite' => $declared['suite'], 'adapter' => $declared['adapter']];
		}

		return null;
	}//end attachedTo()
}//end class
