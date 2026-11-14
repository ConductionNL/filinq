<?php

/**
 * Contract of SubjectErasureRun
 *
 * The public surface of the SubjectErasureRun service, so callers in other
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

use OCA\Filinq\Exception\SubjectErasureRefusedException;

/**
 * Contract of SubjectErasureRun.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SubjectErasureRunInterface {
	/**
	 * Run, or resume, the erasure.
	 *
	 * @param string $uuid   The request uuid.
	 * @param string $userId The caller.
	 *
	 * @return array{request: array<string, mixed>, certificate: array<string, mixed>|null} The
	 *         request as it stands, and the certificate when every document was reached.
	 *
	 * @throws SubjectErasureRefusedException When refused, before a preview, or when
	 *                                        the audit trail stops the run.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-3.1
	 */
	public function run(string $uuid, string $userId): array;

	/**
	 * The certificate of a completed request.
	 *
	 * @param string $uuid   The request uuid.
	 * @param string $userId The caller.
	 *
	 * @return array<string, mixed> The certificate.
	 *
	 * @throws SubjectErasureRefusedException When refused or there is no certificate yet.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-4.2
	 */
	public function certificate(string $uuid, string $userId): array;
}//end interface
