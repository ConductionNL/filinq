<?php

/**
 * Contract of ProcessingCertificateService
 *
 * The public surface of the ProcessingCertificateService service, so callers in other
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

use OCA\Filinq\Exception\WaarmerkRefusedException;

/**
 * Contract of ProcessingCertificateService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ProcessingCertificateServiceInterface {
	/**
	 * Certify one anonymisation run.
	 *
	 * @param string $linkId The anonymizationLink uuid.
	 * @param string $userId The requesting user.
	 *
	 * @return array{waarmerk: array<string, mixed>, fileId: int, fileName: string, certificate: array<string, mixed>}
	 *
	 * @throws WaarmerkRefusedException 404 for an unknown link or an unreadable anonymised file.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-anonymization-runs-can-be-certified-with-a-sealed-processing-certificate-req-ddwmk-003
	 */
	public function certify(string $linkId, string $userId): array;
}
