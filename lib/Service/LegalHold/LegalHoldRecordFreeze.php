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
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCA\OpenRegister\Service\Archival\LegalHoldServiceInterface;
use OCA\OpenRegister\Service\HoldOrder;
use Throwable;

/**
 * Places and lifts OpenRegister legal holds for hold cases.
 *
 * OpenRegister keeps one hold per matter (`retention.legalHold.holds`), and
 * its DestructionCheckJob leaves every held object off the destruction list.
 * Filinq places a single hold per object under its own matter
 * (OWNER_KEY); the reason names the case that holds it
 * (`filinq-hold-case:<case uuid>`). Handing the hold to another case restates
 * that same hold, so it never lapses. This class never overwrites a hold
 * another party placed, and never lifts one: every release names our matter.
 */
class LegalHoldRecordFreeze {

	/**
	 * The prefix of every hold reason a case writes.
	 *
	 * @var string
	 */
	public const REASON_PREFIX = 'filinq-hold-case:';

	/**
	 * The matter every filinq hold is placed under.
	 *
	 * @var string
	 */
	public const OWNER_KEY = 'filinq:legalHoldCase';

	/**
	 * The matter OpenRegister files a hold under that names none, as every
	 * hold placed before holds were kept per matter does.
	 *
	 * @var string
	 */
	private const LEGACY_OWNER = 'openregister:manual';

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
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.1
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
		$entity = $this->objectResolver->resolve()->find(id: $ref, options: new ObjectFindOptions(render: false), _rbac: false);
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
			$entity = $this->objectResolver->resolve()->find(id: $ref, options: new ObjectFindOptions(render: false));
		} catch (Throwable) {
			return null;
		}

		if (is_object($entity) === false || method_exists($entity, 'getRetention') === false) {
			return null;
		}

		return $this->hasActiveHold(entity: $entity);

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
		$own = $this->ownHold(entity: $entity);
		if ($own !== null) {
			// Another of our cases already holds it: covered, and the reason
			// stays with the case that placed it.
			return 'held';
		}

		if ($this->hasActiveHold(entity: $entity) === true) {
			// Another party's hold is left exactly as it is.
			return 'held_by_other';
		}

		$held = $this->holds()->placeHold($entity, new HoldOrder(reason: self::reasonFor(caseUuid: $caseUuid), ownerKey: self::OWNER_KEY));
		if ($this->ownHold(entity: $held) === null) {
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
		$own = $this->ownHold(entity: $entity);
		if ($own === null) {
			if ($this->hasActiveHold(entity: $entity) === true) {
				return 'left_to_other_party';
			}

			return 'released';
		}

		$holds = $this->holds();
		if ($survivorUuid !== null) {
			// Restate in place: the same matter placing again keeps its hold
			// active the whole time, where a release followed by a new hold
			// would leave a gap a destruction run could fall into.
			if ($own['ownerKey'] !== self::OWNER_KEY || $own['reason'] !== self::reasonFor(caseUuid: $survivorUuid)) {
				$holds->placeHold($entity, new HoldOrder(reason: self::reasonFor(caseUuid: $survivorUuid), ownerKey: self::OWNER_KEY));
			}

			if ($own['ownerKey'] !== self::OWNER_KEY) {
				// A hold placed before holds were kept per matter: now that ours
				// is in place, lift the old one, and only that one.
				$holds->releaseHold($entity, new HoldOrder(reason: $releaseReason, ownerKey: $own['ownerKey']));
			}

			return 'kept_for_other_case';
		}

		// Always name the matter: a release without one lifts every hold.
		$holds->releaseHold($entity, new HoldOrder(reason: $releaseReason, ownerKey: $own['ownerKey']));

		return 'released';

	}//end release()

	/**
	 * OpenRegister's legal hold service.
	 *
	 * @return LegalHoldServiceInterface The service.
	 */
	private function holds(): LegalHoldServiceInterface {
		return $this->locator->get(className: LegalHoldServiceInterface::class);

	}//end holds()

	/**
	 * The active holds on the object, one per matter.
	 *
	 * A hold written before holds were kept per matter has no `holds` list:
	 * it counts as one hold of the legacy matter, as OpenRegister reads it.
	 *
	 * @param object $entity The object.
	 *
	 * @return array<int, array<string, mixed>> The holds.
	 */
	private function activeHolds(object $entity): array {
		$hold = ((array) ($entity->getRetention() ?? []))['legalHold'] ?? [];
		if (is_array($hold) === false) {
			return [];
		}

		if (is_array($hold['holds'] ?? null) === true) {
			return array_values(array_filter($hold['holds'], 'is_array'));
		}

		if (($hold['active'] ?? false) !== true) {
			return [];
		}

		return [['ownerKey' => self::LEGACY_OWNER, 'reason' => ($hold['reason'] ?? null)]];

	}//end activeHolds()

	/**
	 * Whether the object has any active hold, ours or another party's.
	 *
	 * @param object $entity The object.
	 *
	 * @return bool True when held.
	 */
	private function hasActiveHold(object $entity): bool {
		return $this->activeHolds(entity: $entity) !== [];

	}//end hasActiveHold()

	/**
	 * Our hold on the object, or null when we hold none.
	 *
	 * Ours is the one under our matter, or a hold of the legacy matter whose
	 * reason names one of our cases.
	 *
	 * @param object $entity The object.
	 *
	 * @return array{ownerKey: string, reason: string}|null The hold.
	 */
	private function ownHold(object $entity): ?array {
		$legacy = null;
		foreach ($this->activeHolds(entity: $entity) as $hold) {
			$ownerKey = (string) ($hold['ownerKey'] ?? '');
			$reason   = (string) ($hold['reason'] ?? '');
			if ($ownerKey === self::OWNER_KEY) {
				return ['ownerKey' => $ownerKey, 'reason' => $reason];
			}

			if ($ownerKey === self::LEGACY_OWNER && str_starts_with($reason, self::REASON_PREFIX) === true) {
				$legacy = ['ownerKey' => $ownerKey, 'reason' => $reason];
			}
		}

		return $legacy;

	}//end ownHold()
}//end class
