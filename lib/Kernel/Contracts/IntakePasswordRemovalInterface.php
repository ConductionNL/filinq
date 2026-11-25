<?php

/**
 * Contract of IntakePasswordRemoval
 *
 * The public surface of the IntakePasswordRemoval service, so callers in other
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

use OCA\Filinq\Exception\IntakeRefusedException;

/**
 * Contract of IntakePasswordRemoval.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface IntakePasswordRemovalInterface {
	/**
	 * Remove the password of a waiting intake document's file.
	 *
	 * The caller's right to change intake documents is checked before the
	 * file is read, so a refused caller never makes a copy. On success the
	 * document's `file` names the copy and `lockedOriginal` keeps the file
	 * that arrived; unlocking a copy again keeps the first arrival.
	 *
	 * @param string $uuid     The intake document.
	 * @param string $password The PDF's password; never stored or logged.
	 *
	 * @return array<string, mixed> The removal's answer (`outcome`, and on success the copy and its findings), with `intakeDocument` on success.
	 *
	 * @throws IntakeRefusedException 404 unknown document or unreadable file, 409 not waiting, 403 no right to change it.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-pdf-password-removal/specs/intake-pdf-password-removal/spec.md#requirement-an-intake-document-keeps-what-arrived-req-ppr-004
	 */
	public function unlock(string $uuid, string $password): array;
}//end interface
