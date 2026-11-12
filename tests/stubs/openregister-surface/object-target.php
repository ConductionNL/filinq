<?php

/**
 * OpenRegister ObjectFindTarget, shared by ObjectPatch (#9096), ObjectDeletion (#9099) and ObjectSaveOptions (#9123).
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
 * The register and schema a caller names for one object.
 */
final class ObjectFindTarget {
	/**
	 * Constructor.
	 *
	 * @param mixed $register The register scope.
	 * @param mixed $schema   The schema scope.
	 */
	public function __construct(
		public readonly mixed $register = null,
		public readonly mixed $schema = null,
	) {
	}//end __construct()
}//end class
