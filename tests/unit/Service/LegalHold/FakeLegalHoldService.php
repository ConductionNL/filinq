<?php

/**
 * OpenRegister's LegalHoldService, as it behaves on main (6d85d5f3).
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\LegalHold
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\LegalHold;

use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\Archival\LegalHoldServiceInterface;
use OCA\OpenRegister\Service\HoldOrder;
use RuntimeException;

/**
 * The same writes as lib/Service/Archival/LegalHoldLedger.php: one hold per
 * matter. placeHold() adds the matter's hold, or restates its reason when the
 * matter already holds the object; releaseHold() moves the matter's hold (every
 * hold when the order names no matter) into the history. The top-level
 * `active` / `reason` mirror the latest remaining hold.
 */
class FakeLegalHoldService implements LegalHoldServiceInterface {

	/**
	 * The matter OpenRegister files a hold under that names none.
	 *
	 * @var string
	 */
	public const LEGACY_OWNER = 'openregister:manual';

	/** @var array<int, string> Every call, in order. */
	public array $calls = [];

	/** @var array<int, HoldOrder> Every order, in order. */
	public array $orders = [];

	/** @var array<int, string> Object uuids whose placement throws. */
	public array $failOn = [];

	/**
	 * Place a hold.
	 *
	 * @param ObjectEntity $object The object.
	 * @param HoldOrder    $order  The reason and the matter.
	 *
	 * @return ObjectEntity The object.
	 */
	public function placeHold(ObjectEntity $object, HoldOrder $order): ObjectEntity {
		$this->calls[]  = 'place:' . $object->getUuid() . ':' . $order->reason;
		$this->orders[] = $order;
		if (in_array($object->getUuid(), $this->failOn, true) === true) {
			throw new RuntimeException('database is locked');
		}

		$owner     = $this->owner(ownerKey: $order->ownerKey);
		$retention = $object->getRetention() ?? [];
		$holds     = $this->holds(retention: $retention);
		foreach ($holds as $index => $hold) {
			if ($hold['ownerKey'] === $owner) {
				$holds[$index]['reason'] = $order->reason;
				$object->setRetention($this->write(retention: $retention, holds: $holds));
				return $object;
			}
		}

		$holds[] = ['id' => 'hold-' . count($this->orders), 'ownerKey' => $owner, 'reason' => $order->reason, 'placedBy' => 'alice', 'placedDate' => '2026-09-29T10:00:00+00:00'];
		$object->setRetention($this->write(retention: $retention, holds: $holds));

		return $object;

	}//end placeHold()

	/**
	 * Release a hold.
	 *
	 * @param ObjectEntity $object The object.
	 * @param HoldOrder    $order  The release reason and the matter; no matter lifts every hold.
	 *
	 * @return ObjectEntity The object.
	 */
	public function releaseHold(ObjectEntity $object, HoldOrder $order): ObjectEntity {
		$this->calls[]  = 'release:' . $object->getUuid();
		$this->orders[] = $order;
		$retention      = $object->getRetention() ?? [];
		$history        = ($retention['legalHold']['history'] ?? []);
		$kept           = [];
		foreach ($this->holds(retention: $retention) as $hold) {
			if ($order->ownerKey !== null && $hold['ownerKey'] !== $order->ownerKey) {
				$kept[] = $hold;
				continue;
			}

			$history[] = array_merge($hold, ['releasedBy' => 'alice', 'releasedDate' => '2026-09-29T11:00:00+00:00', 'releaseReason' => $order->reason]);
		}

		$retention['legalHold'] = ['history' => $history];
		$object->setRetention($this->write(retention: $retention, holds: $kept));

		return $object;

	}//end releaseHold()

	/**
	 * The active holds, a legacy single hold read as one of the legacy matter.
	 *
	 * @param array<string, mixed> $retention The retention.
	 *
	 * @return array<int, array<string, mixed>> The holds.
	 */
	private function holds(array $retention): array {
		$legalHold = ($retention['legalHold'] ?? null);
		if (is_array($legalHold) === false) {
			return [];
		}

		if (is_array($legalHold['holds'] ?? null) === true) {
			return array_values($legalHold['holds']);
		}

		if (($legalHold['active'] ?? false) !== true) {
			return [];
		}

		return [['id' => 'legacy', 'ownerKey' => self::LEGACY_OWNER, 'reason' => ($legalHold['reason'] ?? null), 'placedBy' => ($legalHold['placedBy'] ?? null), 'placedDate' => ($legalHold['placedDate'] ?? null)]];

	}//end holds()

	/**
	 * Write the holds back, the top level mirroring the latest one.
	 *
	 * @param array<string, mixed>             $retention The retention.
	 * @param array<int, array<string, mixed>> $holds     The holds.
	 *
	 * @return array<string, mixed> The retention.
	 */
	private function write(array $retention, array $holds): array {
		$latest = ($holds === [] ? null : $holds[count($holds) - 1]);
		$retention['legalHold'] = [
			'active' => ($holds !== []),
			'reason' => ($latest['reason'] ?? null),
			'placedBy' => ($latest['placedBy'] ?? null),
			'placedDate' => ($latest['placedDate'] ?? null),
			'holds' => array_values($holds),
			'history' => ($retention['legalHold']['history'] ?? []),
		];

		return $retention;

	}//end write()

	/**
	 * The matter a hold is filed under.
	 *
	 * @param string|null $ownerKey The order's matter.
	 *
	 * @return string The matter.
	 */
	private function owner(?string $ownerKey): string {
		$ownerKey = trim((string) $ownerKey);
		return ($ownerKey === '' ? self::LEGACY_OWNER : $ownerKey);

	}//end owner()
}//end class
