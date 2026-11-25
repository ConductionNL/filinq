<?php

/**
 * OpenRegister's slug-search target value: RegisterSchemaSlugs (openregister main, 21 Nov 2026).
 *
 * Loaded by tests/stubs/OpenRegisterStubs.php.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\OpenRegister
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\OpenRegister\Contract;

if (class_exists(RegisterSchemaSlugs::class) === false) {
	/**
	 * Stub for RegisterSchemaSlugs (mirrors openregister lib/Contract/RegisterSchemaSlugs.php).
	 *
	 * @category Tests
	 * @package  OCA\OpenRegister\Contract
	 * @author   Conduction B.V. <info@conduction.nl>
	 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
	 * @link     https://www.filinq.app
	 */
	final class RegisterSchemaSlugs {

		/**
		 * Constructor.
		 *
		 * @param string $register The register slug.
		 * @param string $schema   The schema slug.
		 */
		public function __construct(
			public readonly string $register,
			public readonly string $schema,
		) {
		}//end __construct()
	}//end class
}//end if
