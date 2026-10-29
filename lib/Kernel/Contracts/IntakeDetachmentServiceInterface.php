<?php

/**
 * Contract of IntakeDetachmentService
 *
 * The public surface of the IntakeDetachmentService service, so callers in other
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
 * Contract of IntakeDetachmentService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface IntakeDetachmentServiceInterface {
	/**
	 * Take one document off the record it is filed on.
	 *
	 * @param int $fileId The Nextcloud file id of the document.
	 * @param string $reason Why it does not belong on that record.
	 * @param string $documentName The document's name, for a record that has to be created here.
	 *
	 * @return array<string, mixed> The intake document, now on the worklist.
	 *
	 * @throws IntakeRefusedException When there is no reason, or the user may not write the intake register.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function detach(int $fileId, string $reason, string $documentName = ''): array;
}//end interface
