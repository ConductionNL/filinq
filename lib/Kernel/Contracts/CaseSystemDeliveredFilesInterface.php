<?php

/**
 * Contract of CaseSystemDeliveredFiles
 *
 * The public surface of the CaseSystemDeliveredFiles service, so callers in other
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
 * Contract of CaseSystemDeliveredFiles.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface CaseSystemDeliveredFilesInterface {
	/**
	 * Which of these files are in the case system: their delivery was written back.
	 *
	 * One query for the whole list, so a page of documents costs one lookup.
	 * The answer is checked against the asked ids, whatever the query returns.
	 *
	 * @param array<int, int> $fileIds The Nextcloud file ids.
	 *
	 * @return array<int, int> The file ids among them with a written-back delivery, in the order asked.
	 *
	 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
	 */
	public function deliveredFileIds(array $fileIds): array;
}//end interface
