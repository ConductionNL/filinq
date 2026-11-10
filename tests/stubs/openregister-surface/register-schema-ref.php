<?php

/**
 * OpenRegister RegisterSchemaRef, which searchObjectsBySlug() takes after openregister #9103.
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


namespace OCA\OpenRegister\Kernel\Contracts;

/**
 * One register and schema, by id or slug.
 */
final class RegisterSchemaRef {
	/**
	 * Constructor.
	 *
	 * @param string|int $register The register id or slug.
	 * @param string|int $schema   The schema id or slug.
	 */
	public function __construct(
		public readonly string|int $register,
		public readonly string|int $schema,
	) {
	}//end __construct()
}//end class
