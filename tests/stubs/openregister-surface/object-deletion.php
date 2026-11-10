<?php

/**
 * OpenRegister ObjectDeletion (openregister #9099).
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

namespace OCA\OpenRegister\Service\Object;


/**
 * Stub for ObjectDeletion (mirrors lib/Service/Object/ObjectDeletion.php, openregister #9099).
 *
 * @category Tests
 * @package  OCA\OpenRegister\Service\Object
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
final class ObjectDeletion {
	/**
	 * Constructor.
	 *
	 * @param string           $uuid        The UUID of the object to delete.
	 * @param ObjectFindTarget $target      The register and schema scope.
	 * @param mixed            $currentUser The explicit acting user.
	 * @param bool             $permanent   Destroy the row rather than tombstone it.
	 */
	public function __construct(
		public readonly string $uuid,
		public readonly ObjectFindTarget $target = new ObjectFindTarget(),
		public readonly mixed $currentUser = null,
		public readonly bool $permanent = false,
	) {
	}//end __construct()
}//end class
