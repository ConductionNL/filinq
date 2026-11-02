<?php

/**
 * Contract of PublicationAccess
 *
 * The public surface of the PublicationAccess service, so callers in other
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
 * Contract of PublicationAccess.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PublicationAccessInterface {
	/**
	 * The app setting naming the groups that may publish: a JSON list of group ids.
	 *
	 * @var string
	 */
	public const PUBLISHER_GROUPS_KEY = 'publication_publisher_groups';

	/**
	 * The publisher groups when nothing is configured.
	 *
	 * @var string
	 */
	public const DEFAULT_PUBLISHER_GROUPS = '["filinq-publishers"]';

	/**
	 * Whether a user may see a document's publication.
	 *
	 * @param string $uid    The user
	 * @param string $fileId The document's file id
	 *
	 * @return bool True for an admin, or when the user can open the document.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	public function maySee(string $uid, string $fileId): bool;

	/**
	 * Whether a user may start, hand off, withdraw or change a document's publication.
	 *
	 * File access alone is not enough: a read-only share recipient can open the
	 * document. The user must also be in a publisher group.
	 *
	 * @param string $uid    The user
	 * @param string $fileId The document's file id
	 *
	 * @return bool True for an admin, or a publisher who can open the document.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	public function mayAct(string $uid, string $fileId): bool;
}//end interface
