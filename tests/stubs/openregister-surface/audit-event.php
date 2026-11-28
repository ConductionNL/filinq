<?php

/**
 * OpenRegister object audit events: ObjectAuditEvent and its options (openregister #9058).
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

namespace OCA\OpenRegister\Db\AuditTrail;

use OCA\OpenRegister\Db\ObjectEntity;

/**
 * Stub for NamedActor (mirrors lib/Db/AuditTrail/NamedActor.php).
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db\AuditTrail
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
final class NamedActor {
	/**
	 * Constructor.
	 *
	 * @param string|null $id   Explicit actor id; null uses the session user.
	 * @param string|null $name Explicit actor display name.
	 */
	public function __construct(
		public readonly ?string $id = null,
		public readonly ?string $name = null,
	) {
	}//end __construct()
}//end class

/**
 * Stub for ObjectAuditEventOptions (mirrors lib/Db/AuditTrail/ObjectAuditEventOptions.php).
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db\AuditTrail
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
final class ObjectAuditEventOptions {
	/**
	 * Constructor.
	 *
	 * @param NamedActor  $actor     The explicit actor.
	 * @param string|null $ipAddress The calling address.
	 */
	public function __construct(
		public readonly NamedActor $actor = new NamedActor(),
		public readonly ?string $ipAddress = null,
	) {
	}//end __construct()
}//end class

/**
 * Stub for ObjectAuditEvent (mirrors lib/Db/AuditTrail/ObjectAuditEvent.php).
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db\AuditTrail
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
final class ObjectAuditEvent {
	/**
	 * Constructor.
	 *
	 * @param ObjectEntity            $object  The object the entry relates to.
	 * @param string                  $action  The action.
	 * @param array                   $context Additional context data.
	 * @param ObjectAuditEventOptions $options The explicit actor and address.
	 */
	public function __construct(
		public readonly ObjectEntity $object,
		public readonly string $action,
		public readonly array $context = [],
		public readonly ObjectAuditEventOptions $options = new ObjectAuditEventOptions(),
	) {
	}//end __construct()
}//end class
