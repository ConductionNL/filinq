<?php

/**
 * OpenRegister ObjectFindOptions (openregister #9116).
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

namespace OCA\OpenRegister\Kernel\Contracts\Value;

/**
 * Stub for ObjectFindOptions (mirrors lib/Kernel/Contracts/Value/ObjectFindOptions.php, openregister #9116).
 *
 * @category Tests
 * @package  OCA\OpenRegister\Kernel\Contracts\Value
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
final class ObjectFindOptions {
	/**
	 * Constructor.
	 *
	 * @param mixed      $register Register.
	 * @param mixed      $schema   Schema.
	 * @param array|null $extend   Properties to extend.
	 * @param bool       $files    Include files.
	 * @param bool       $render   Render the object.
	 * @param bool       $audit    Write the read audit entry.
	 */
	public function __construct(
		public readonly mixed $register = null,
		public readonly mixed $schema = null,
		public readonly ?array $extend = [],
		public readonly bool $files = false,
		public readonly bool $render = true,
		public readonly bool $audit = true,
	) {
	}//end __construct()
}//end class
