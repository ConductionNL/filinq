<?php

/**
 * OpenRegister save value objects: ObjectSaveOptions, SavePolicy and SaveActor (openregister #9123).
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
 * How a save treats the write.
 */
final class SavePolicy {
	/**
	 * Constructor.
	 *
	 * @param bool $silent        Skip events.
	 * @param bool $validation    Validate against the schema.
	 * @param bool $failIfExists  Refuse an existing uuid.
	 * @param bool $dedupOverride Skip deduplication.
	 */
	public function __construct(
		public readonly bool $silent = false,
		public readonly bool $validation = true,
		public readonly bool $failIfExists = false,
		public readonly bool $dedupOverride = false,
	) {
	}//end __construct()
}//end class

/**
 * Who a save acts as and who owns a created object.
 */
final class SaveActor {
	/**
	 * Constructor.
	 *
	 * @param mixed       $currentUser The explicit acting user.
	 * @param string|null $owner       An explicit owner for a create.
	 * @param bool        $unowned     Stamp the system identity as owner on a create.
	 */
	public function __construct(
		public readonly mixed $currentUser = null,
		public readonly ?string $owner = null,
		public readonly bool $unowned = false,
	) {
	}//end __construct()
}//end class

/**
 * Everything a save takes besides the object.
 */
final class ObjectSaveOptions {
	/**
	 * Constructor.
	 *
	 * @param ObjectFindTarget $target        The register and schema.
	 * @param string|null      $uuid          The uuid to update or create under.
	 * @param array|null       $extend        Properties to extend.
	 * @param array|null       $uploadedFiles Uploaded files.
	 * @param SavePolicy       $policy        How the write is treated.
	 * @param SaveActor        $actor         Who acts and who owns.
	 */
	public function __construct(
		public readonly ObjectFindTarget $target = new ObjectFindTarget(),
		public readonly ?string $uuid = null,
		public readonly ?array $extend = [],
		public readonly ?array $uploadedFiles = null,
		public readonly SavePolicy $policy = new SavePolicy(),
		public readonly SaveActor $actor = new SaveActor(),
	) {
	}//end __construct()
}//end class
