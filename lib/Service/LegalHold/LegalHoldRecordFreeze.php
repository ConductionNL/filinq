<?php

/**
 * The record freeze: OpenRegister's per-object legal hold.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\LegalHold
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\LegalHold;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use Throwable;

/**
 * Places and lifts OpenRegister legal holds for hold cases.
 *
 * OpenRegister keeps ONE hold per object (`retention.legalHold`), and its
 * DestructionCheckJob leaves every held object off the destruction list. This
 * class never overwrites a hold another party placed, and never lifts one:
 * the reason tells ours apart (`filinq-hold-case:<case uuid>`).
 */
class LegalHoldRecordFreeze {

	/**
	 * The prefix of every hold reason a case writes.
	 *
	 * @var string
	 */
	public const REASON_PREFIX = 'filinq-hold-case:';

	/**
	 * OpenRegister's legal hold service.
	 *
	 * @var string
	 */
	private const HOLD_SERVICE = 'OCA\OpenRegister\Service\Archival\LegalHoldService';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService.
	 * @param OpenRegisterServiceLocator    $locator        Resolves OpenRegister's LegalHoldService.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly OpenRegisterServiceLocator $locator,
	) {

	}//end __construct()

	/**
	 * The hold reason for a case.
	 *
	 * @param string $caseUuid The case.
	 *
	 * @return string The reason.
	 */
	public static function reasonFor(string $caseUuid): string {
		return self::REASON_PREFIX . $caseUuid;

	}//end reasonFor()

	/**
	 * Load the stored object behind a scope reference.
	 *
	 * @param string $ref The object uuid.
	 *
	 * @return object|null The entity, or null when there is none.
	 *
	 * @throws Throwable When OpenRegister cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.1
	 */
	public function load(string $ref): ?object {
		$entity = $this->objectResolver->resolve()->find(id: $ref, _rbac: false, _render: false);
		if (is_object($entity) === false || method_exists($entity, 'getRetention') === false) {
			return null;
		}

		return $entity;

	}//end load()

	/**
	 * Whether the record is under a legal hold, as the caller may see it.
	 *
	 * Read with the caller's rights: somebody who cannot open the record learns
	 * nothing about its hold.
	 *
	 * @param string $ref The object uuid.
	 *
	 * @return bool|null True when held, false when not, null when the caller cannot read it.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.2
	 */
	public function heldAsSeenBy(string $ref): ?bool {
		try {
			$entity = $this->objectResolver->resolve()->find(id: $ref, _render: false);
		} catch (Throwable) {
			return null;
		}

		if (is_object($entity) === false || method_exists($entity, 'getRetention') === false) {
			return null;
		}

		return $this->currentReason(entity: $entity) !== null;

	}//end heldAsSeenBy()

	/**
	 * Hold the object for a case, unless somebody already holds it.
	 *
	 * @param object $entity   The object.
	 * @param string $caseUuid The case.
	 *
	 * @return string held | held_by_other | failed.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.1
	 */
	public function place(object $entity, string $caseUuid): string {
		$current = $this->currentReason(entity: $entity);
		if ($current !== null) {
			// Another of our cases already holds it: covered, and the reason
			// stays with the case that placed it. Another party's hold is left
			// exactly as it is.
			if (str_starts_with($current, self::REASON_PREFIX) === true) {
				return 'held';
			}

			return 'held_by_other';
		}

		$held = $this->locator->get(className: self::HOLD_SERVICE)->placeHold($entity, self::reasonFor(caseUuid: $caseUuid));
		if ($this->currentReason(entity: $held) === null) {
			return 'failed';
		}

		return 'held';

	}//end place()

	/**
	 * Lift the case's hold, or hand it to the case that still needs it.
	 *
	 * @param object      $entity        The object.
	 * @param string|null $survivorUuid  An other active case covering the object, or null.
	 * @param string      $releaseReason Why the case was released.
	 *
	 * @return string released | kept_for_other_case | left_to_other_party.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.2
	 */
	public function release(object $entity, ?string $survivorUuid, string $releaseReason): string {
		$current = $this->currentReason(entity: $entity);
		if ($current === null) {
			return 'released';
		}

		if (str_starts_with($current, self::REASON_PREFIX) === false) {
			return 'left_to_other_party';
		}

		$holds = $this->locator->get(className: self::HOLD_SERVICE);
		if ($survivorUuid !== null) {
			// Re-stamp in place: placeHold keeps the hold active the whole
			// time, where a release followed by a new hold would leave a gap
			// a destruction run could fall into.
			if ($current !== self::reasonFor(caseUuid: $survivorUuid)) {
				$holds->placeHold($entity, self::reasonFor(caseUuid: $survivorUuid));
			}

			return 'kept_for_other_case';
		}

		$holds->releaseHold($entity, $releaseReason);

		return 'released';

	}//end release()

	/**
	 * The reason of the object's active hold, or null when it has none.
	 *
	 * @param object $entity The object.
	 *
	 * @return string|null The reason.
	 */
	private function currentReason(object $entity): ?string {
		$hold = ((array) ($entity->getRetention() ?? []))['legalHold'] ?? [];
		if (is_array($hold) === false || ($hold['active'] ?? false) !== true) {
			return null;
		}

		return (string) ($hold['reason'] ?? '');

	}//end currentReason()
}//end class
