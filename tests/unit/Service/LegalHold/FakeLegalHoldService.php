<?php

/**
 * OpenRegister's LegalHoldService, as it behaves at development (ac7296b4).
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
use RuntimeException;

/**
 * The same writes as lib/Service/Archival/LegalHoldService.php: placeHold()
 * replaces the current hold and keeps the history; releaseHold() moves the
 * current hold into the history and sets active false.
 */
class FakeLegalHoldService {

	/** @var array<int, string> Every call, in order. */
	public array $calls = [];

	/** @var array<int, string> Object uuids whose placement throws. */
	public array $failOn = [];

	/**
	 * Place a hold.
	 *
	 * @param ObjectEntity $object The object.
	 * @param string       $reason The reason.
	 *
	 * @return ObjectEntity The object.
	 */
	public function placeHold(ObjectEntity $object, string $reason): ObjectEntity {
		$this->calls[] = 'place:' . $object->getUuid() . ':' . $reason;
		if (in_array($object->getUuid(), $this->failOn, true) === true) {
			throw new RuntimeException('database is locked');
		}

		$retention = $object->getRetention() ?? [];
		$retention['legalHold'] = [
			'active' => true,
			'reason' => $reason,
			'placedBy' => 'alice',
			'placedDate' => '2026-09-29T10:00:00+00:00',
			'history' => ($retention['legalHold']['history'] ?? []),
		];
		$object->setRetention($retention);

		return $object;

	}//end placeHold()

	/**
	 * Release a hold.
	 *
	 * @param ObjectEntity $object The object.
	 * @param string       $reason The release reason.
	 *
	 * @return ObjectEntity The object.
	 */
	public function releaseHold(ObjectEntity $object, string $reason): ObjectEntity {
		$this->calls[] = 'release:' . $object->getUuid();
		$retention = $object->getRetention() ?? [];
		$hold = ($retention['legalHold'] ?? []);
		$history = ($hold['history'] ?? []);
		$history[] = [
			'active' => true,
			'reason' => ($hold['reason'] ?? 'unknown'),
			'placedBy' => ($hold['placedBy'] ?? 'unknown'),
			'placedDate' => ($hold['placedDate'] ?? null),
			'releasedBy' => 'alice',
			'releasedDate' => '2026-09-29T11:00:00+00:00',
			'releaseReason' => $reason,
		];
		$retention['legalHold'] = [
			'active' => false,
			'reason' => ($hold['reason'] ?? null),
			'placedBy' => ($hold['placedBy'] ?? null),
			'placedDate' => ($hold['placedDate'] ?? null),
			'history' => $history,
		];
		$object->setRetention($retention);

		return $object;

	}//end releaseHold()
}//end class
