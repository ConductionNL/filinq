<?php

/**
 * OpenRegister ObjectPatch (openregister #9096).
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
 * Stub for ObjectPatch (mirrors lib/Service/Object/ObjectPatch.php, openregister #9096).
 *
 * @category Tests
 * @package  OCA\OpenRegister\Service\Object
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
final class ObjectPatch {
	/**
	 * Constructor.
	 *
	 * @param string           $objectId    The object id, uuid or slug.
	 * @param array            $data        The partial data to merge.
	 * @param ObjectFindTarget $target      The register and schema scope.
	 * @param mixed            $currentUser The explicit acting user.
	 */
	public function __construct(
		public readonly string $objectId,
		public readonly array $data,
		public readonly ObjectFindTarget $target = new ObjectFindTarget(),
		public readonly mixed $currentUser = null,
	) {
	}//end __construct()
}//end class
