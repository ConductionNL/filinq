<?php

/**
 * Contract of WaarmerkCms
 *
 * The public surface of the WaarmerkCms service, so callers in other
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
 * Contract of WaarmerkCms.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface WaarmerkCmsInterface {
	/**
	 * The signer of a detached CMS over a hash, when it is valid and by the recorded certificate.
	 *
	 * Fail-closed: any OpenSSL error, a missing signer or another signer
	 * certificate answers null.
	 *
	 * @param string $hash The lowercase hex sha256 the seal recorded.
	 * @param string $cmsBase64 The detached CMS, base64 DER.
	 * @param string $fingerprint The recorded certificate fingerprint (colon hex).
	 *
	 * @return string|null The signer certificate (PEM) when the signature is valid, else null.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-waarmerk-is-publicly-verifiable-fail-closed-req-ddwmk-004
	 */
	public function verify(string $hash, string $cmsBase64, string $fingerprint): ?string;
}
