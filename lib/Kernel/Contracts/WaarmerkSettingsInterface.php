<?php

/**
 * Contract of WaarmerkSettings
 *
 * The public surface of the WaarmerkSettings service, so callers in other
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
 * Contract of WaarmerkSettings.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface WaarmerkSettingsInterface {
	/**
	 * What the admin panel shows: public facts and custody, never the key.
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-organisation-certificate-is-managed-in-admin-settings-with-credentialref-key-custody-req-ddwmk-001
	 */
	public function summary(): array;

	/**
	 * Save the certificate, the credential reference and the custody mode.
	 *
	 * A local key, when given, must belong to the certificate; it is stored
	 * encrypted and sensitive. Switching to broker custody removes it.
	 *
	 * @param array<string, mixed> $input certificate, credentialRef, custody, localKey.
	 *
	 * @return array<string, mixed> The new summary.
	 *
	 * @throws WaarmerkRefusedException When the input is invalid.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-organisation-certificate-is-managed-in-admin-settings-with-credentialref-key-custody-req-ddwmk-001
	 */
	public function save(array $input): array;
}
