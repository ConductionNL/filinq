<?php

/**
 * Consent update
 *
 * The consent record an update is for, and the fields it changes.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/consent-management/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel;

/**
 * The consent id, its register and schema, and the update payload. The
 * acting user is not part of it: callers pass the session user separately.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/consent-management/spec.md
 */
final class ConsentUpdate {

	/**
	 * Constructor.
	 *
	 * @param string               $consentId The consent object UUID
	 * @param string               $register  The register ID
	 * @param string               $schema    The schema ID
	 * @param array<string, mixed> $data      The update payload
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $consentId,
		public readonly string $register,
		public readonly string $schema,
		public readonly array $data,
	) {

	}//end __construct()
}//end class
