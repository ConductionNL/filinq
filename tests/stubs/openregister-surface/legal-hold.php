<?php

/**
 * OpenRegister legal holds: HoldOrder and LegalHoldServiceInterface (openregister #8908).
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

namespace OCA\OpenRegister\Service;

/**
 * Stub for HoldOrder: an order to place or release a legal hold.
 *
 * Mirrors openregister lib/Service/HoldOrder.php.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
final class HoldOrder {
	/**
	 * Build the order.
	 *
	 * @param string      $reason   Why the hold is placed or released.
	 * @param string|null $ownerKey The matter placing or releasing its own hold; null for a manual hold, or to release every hold.
	 */
	public function __construct(
		public readonly string $reason,
		public readonly ?string $ownerKey = null,
	) {
	}//end __construct()
}//end class

namespace OCA\OpenRegister\Service\Archival;

use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\HoldOrder;

/**
 * Stub for LegalHoldServiceInterface (the per-object part filinq calls).
 *
 * Mirrors openregister lib/Service/Archival/LegalHoldServiceInterface.php.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Service\Archival
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface LegalHoldServiceInterface {
	/**
	 * Place a legal hold on an object.
	 *
	 * @param ObjectEntity $object The object.
	 * @param HoldOrder    $order  The reason and the matter placing it.
	 *
	 * @return ObjectEntity The updated object.
	 */
	public function placeHold(ObjectEntity $object, HoldOrder $order): ObjectEntity;

	/**
	 * Release a legal hold on an object.
	 *
	 * @param ObjectEntity $object The object.
	 * @param HoldOrder    $order  The reason and the matter releasing its own hold; no matter lifts every hold.
	 *
	 * @return ObjectEntity The updated object.
	 */
	public function releaseHold(ObjectEntity $object, HoldOrder $order): ObjectEntity;
}//end interface
