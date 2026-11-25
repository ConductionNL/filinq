<?php

/**
 * Delegated signing authority
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Signing;

use OCA\Filinq\Kernel\Contracts\SigningDelegationServiceInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;
use OCA\Filinq\Kernel\AppIdentity;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use RuntimeException;

/**
 * A signer hands their signing authority to a named colleague for a period
 * and for named document types.
 *
 * A delegation is declared and withdrawn by the signer who gives it, never by
 * the delegate. Inside its period, and for its types only, the delegate may
 * sign what waits for the signer; outside it every attempt is refused with
 * the reason. The period runs from the start date through the end date, both
 * whole days. The delegations are instance configuration kept beside the
 * mandate declarations, not records.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
 */
class SigningDelegationService implements SigningDelegationServiceInterface {

	/**
	 * App config key holding the delegations, as a JSON list.
	 */
	public const CONFIG_KEY = 'signingDelegations';

	/**
	 * Constructor.
	 *
	 * @param IAppConfig   $config The stored delegations
	 * @param ITimeFactory $clock  Today
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $config,
		private readonly ITimeFactory $clock,
	) {

	}//end __construct()

	/**
	 * Record a delegation from the signer to a colleague.
	 *
	 * @param string            $delegator The signer giving the authority
	 * @param string            $delegate  The colleague receiving it
	 * @param string            $from      First day, Y-m-d
	 * @param string            $until     Last day, Y-m-d
	 * @param array<int, mixed> $types     The `<app>/<schema>` document types it covers
	 *
	 * @return array{id: string, delegator: string, delegate: string, from: string, until: string, types: list<string>} The stored delegation.
	 *
	 * @throws InvalidArgumentException When a party, a date or the types are missing or wrong.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	public function declare(string $delegator, string $delegate, string $from, string $until, array $types): array {
		$delegator = trim($delegator);
		$delegate = trim($delegate);
		$this->assertParties(delegator: $delegator, delegate: $delegate);
		$this->assertPeriod(from: $from, until: $until);
		$cleanTypes = $this->cleanTypes(types: $types);

		$delegation = [
			'id' => bin2hex(random_bytes(8)),
			'delegator' => $delegator,
			'delegate' => $delegate,
			'from' => $from,
			'until' => $until,
			'types' => array_values(array_unique($cleanTypes)),
		];

		$all = $this->all();
		$all[] = $delegation;
		$this->store(delegations: $all);

		return $delegation;

	}//end declare()

	/**
	 * Refuse a delegation that does not name two different parties.
	 *
	 * @param string $delegator The signer giving the authority, trimmed
	 * @param string $delegate  The colleague receiving it, trimmed
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When a party is missing or both are the same.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	private function assertParties(string $delegator, string $delegate): void {
		if ($delegator === '' || $delegate === '') {
			throw new InvalidArgumentException('A delegation names the signer and the colleague');
		}

		if ($delegator === $delegate) {
			throw new InvalidArgumentException('A signer cannot delegate to themselves');
		}

	}//end assertParties()

	/**
	 * Refuse a delegation period that is not two real dates in order.
	 *
	 * @param string $from  First day, Y-m-d
	 * @param string $until Last day, Y-m-d
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When a date is missing or wrong, or the period ends before it starts.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	private function assertPeriod(string $from, string $until): void {
		if ($this->isDate(value: $from) === false || $this->isDate(value: $until) === false) {
			throw new InvalidArgumentException('A delegation needs a start and an end date (YYYY-MM-DD)');
		}

		if ($until < $from) {
			throw new InvalidArgumentException('A delegation cannot end before it starts');
		}

	}//end assertPeriod()

	/**
	 * The delegated document types, trimmed, each an `<app>/<schema>` reference.
	 *
	 * @param array<int, mixed> $types The types as given
	 *
	 * @return list<string> The trimmed types, in the given order.
	 *
	 * @throws InvalidArgumentException When a type is not a reference, or none is given.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	private function cleanTypes(array $types): array {
		$cleanTypes = [];
		foreach ($types as $type) {
			$type = trim((string) $type);
			if (preg_match('#^[a-z0-9_-]+/[A-Za-z0-9_-]+$#', $type) !== 1) {
				throw new InvalidArgumentException('A delegated document type is an <app>/<schema> reference');
			}

			$cleanTypes[] = $type;
		}

		if ($cleanTypes === []) {
			throw new InvalidArgumentException('A delegation names at least one document type');
		}

		return $cleanTypes;

	}//end cleanTypes()

	/**
	 * Withdraw a delegation the signer gave.
	 *
	 * @param string $id        The delegation
	 * @param string $delegator The signer asking; only the giver may withdraw it
	 *
	 * @return bool True when it was withdrawn, false when the signer gave no such delegation.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	public function withdraw(string $id, string $delegator): bool {
		$kept = [];
		$found = false;
		foreach ($this->all() as $delegation) {
			if ($this->isGivenBy(delegation: $delegation, id: $id, delegator: $delegator) === true) {
				$found = true;
				continue;
			}

			$kept[] = $delegation;
		}

		if ($found === true) {
			$this->store(delegations: $kept);
		}

		return $found;

	}//end withdraw()

	/**
	 * Whether this is the delegation with this id, given by this signer.
	 *
	 * @param array<string, mixed> $delegation The stored delegation
	 * @param string               $id         The delegation id asked for
	 * @param string               $delegator  The signer asking; an empty one gave nothing
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	private function isGivenBy(array $delegation, string $id, string $delegator): bool {
		return $delegation['id'] === $id && $delegation['delegator'] === $delegator && $delegator !== '';

	}//end isGivenBy()

	/**
	 * The delegations a user gave and received.
	 *
	 * @param string $userId The user
	 *
	 * @return array{given: list<array<string, mixed>>, received: list<array<string, mixed>>}
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	public function forUser(string $userId): array {
		$given = [];
		$received = [];
		$all = $this->all();
		if ($userId === '') {
			$all = [];
		}

		foreach ($all as $delegation) {
			if ($delegation['delegator'] === $userId) {
				$given[] = $delegation;
			}

			if ($delegation['delegate'] === $userId) {
				$received[] = $delegation;
			}
		}

		return ['given' => $given, 'received' => $received];

	}//end forUser()

	/**
	 * Why the delegate may not sign for the signer today, or null when they may.
	 *
	 * @param string      $delegator     The signer
	 * @param string      $delegate      The colleague acting
	 * @param string|null $typeReference The document type, or null to check the period only
	 *
	 * @return string|null The refusal, naming the delegation; null when a delegation covers it.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	public function refusal(string $delegator, string $delegate, ?string $typeReference): ?string {
		$today = $this->today();
		$reason = 'No delegation from ' . $delegator . ' to ' . $delegate;
		foreach ($this->all() as $delegation) {
			if ($this->isBetween(delegation: $delegation, delegator: $delegator, delegate: $delegate) === false) {
				continue;
			}

			$why = $this->delegationRefusal(delegation: $delegation, today: $today, typeReference: $typeReference);
			if ($why === null) {
				return null;
			}

			$reason = $why;
		}//end foreach

		return $reason;

	}//end refusal()

	/**
	 * Why this one delegation does not cover the signature, or null when it does.
	 *
	 * @param array<string, mixed> $delegation    The delegation from the signer to the delegate
	 * @param string               $today         Today, Y-m-d
	 * @param string|null          $typeReference The document type, or null to check the period only
	 *
	 * @return string|null The refusal, naming the delegation; null when it covers the signature.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	private function delegationRefusal(array $delegation, string $today, ?string $typeReference): ?string {
		$delegator = $delegation['delegator'];
		if ($today > $delegation['until']) {
			return 'The delegation from ' . $delegator . ' ended on ' . $delegation['until'];
		}

		if ($today < $delegation['from']) {
			return 'The delegation from ' . $delegator . ' starts on ' . $delegation['from'];
		}

		return $this->typeRefusal(delegation: $delegation, typeReference: $typeReference);

	}//end delegationRefusal()

	/**
	 * Why this delegation does not cover the document type, or null when it does.
	 *
	 * @param array<string, mixed> $delegation    The delegation
	 * @param string|null          $typeReference The document type, or null when only the period counts
	 *
	 * @return string|null The refusal naming the type; null when the type is covered or not asked.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	private function typeRefusal(array $delegation, ?string $typeReference): ?string {
		if ($typeReference === null || in_array($typeReference, $delegation['types'], true) === true) {
			return null;
		}

		$type = $typeReference;
		if ($type === '') {
			$type = 'documents without a type';
		}

		return 'The delegation from ' . $delegation['delegator'] . ' does not cover ' . $type;

	}//end typeRefusal()

	/**
	 * Whether this delegation runs from this signer to this delegate.
	 *
	 * @param array<string, mixed> $delegation The delegation
	 * @param string               $delegator  The signer
	 * @param string               $delegate   The colleague acting
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	private function isBetween(array $delegation, string $delegator, string $delegate): bool {
		return $delegation['delegator'] === $delegator && $delegation['delegate'] === $delegate;

	}//end isBetween()

	/**
	 * Refuse a delegated signature the delegation does not cover.
	 *
	 * @param string      $delegator     The signer
	 * @param string      $delegate      The colleague acting
	 * @param string|null $typeReference The document type, or null to check the period only
	 *
	 * @return void
	 *
	 * @throws RuntimeException Naming the delegation, when it does not cover the signature.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	public function assertMayActFor(string $delegator, string $delegate, ?string $typeReference): void {
		$refusal = $this->refusal(delegator: $delegator, delegate: $delegate, typeReference: $typeReference);
		if ($refusal !== null) {
			throw new RuntimeException('Signing refused: ' . $refusal);
		}

	}//end assertMayActFor()

	/**
	 * The signers a delegate may act for today, whatever the type.
	 *
	 * @param string $delegate The colleague
	 *
	 * @return list<string> The signers' user ids.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	public function activeDelegators(string $delegate): array {
		$delegators = [];
		foreach ($this->forUser(userId: $delegate)['received'] as $delegation) {
			if ($this->refusal(delegator: $delegation['delegator'], delegate: $delegate, typeReference: null) === null) {
				$delegators[] = $delegation['delegator'];
			}
		}

		return array_values(array_unique($delegators));

	}//end activeDelegators()

	/**
	 * The signers who ever delegated to this colleague, active or not.
	 *
	 * @param string $delegate The colleague
	 *
	 * @return list<string> The signers' user ids.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	public function delegatorsOf(string $delegate): array {
		return array_values(array_unique(array_column($this->forUser(userId: $delegate)['received'], 'delegator')));

	}//end delegatorsOf()

	/**
	 * Every stored delegation.
	 *
	 * @return list<array{id: string, delegator: string, delegate: string, from: string, until: string, types: list<string>}>
	 *
	 * @throws RuntimeException When the stored value cannot be read.
	 */
	private function all(): array {
		$raw = $this->config->getValueString(AppIdentity::APP_ID, self::CONFIG_KEY, '');
		if ($raw === '') {
			return [];
		}

		try {
			$decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			// Unreadable delegations grant nothing, and are reported.
			throw new RuntimeException('Signing delegations could not be read');
		}

		$delegations = [];
		foreach ((array) $decoded as $row) {
			$delegation = $this->storedDelegation(row: $row);
			if ($delegation !== null) {
				$delegations[] = $delegation;
			}
		}

		return $delegations;

	}//end all()

	/**
	 * A stored row as a delegation, or null when it is not one.
	 *
	 * @param mixed $row The decoded row
	 *
	 * @return array<string, mixed>|null The delegation with its types as strings, or null.
	 */
	private function storedDelegation(mixed $row): ?array {
		if (is_array($row) === false || isset($row['id'], $row['delegator'], $row['delegate'], $row['from'], $row['until']) === false) {
			return null;
		}

		$row['types'] = array_values(array_map('strval', (array) ($row['types'] ?? [])));

		return $row;

	}//end storedDelegation()

	/**
	 * Write the delegations back.
	 *
	 * @param list<array<string, mixed>> $delegations All delegations
	 *
	 * @return void
	 */
	private function store(array $delegations): void {
		$this->config->setValueString(AppIdentity::APP_ID, self::CONFIG_KEY, (string) json_encode(array_values($delegations)));

	}//end store()

	/**
	 * Whether a value is a real Y-m-d date.
	 *
	 * @param string $value The value
	 *
	 * @return bool
	 */
	private function isDate(string $value): bool {
		$date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

		return $date !== false && $date->format('Y-m-d') === $value;

	}//end isDate()

	/**
	 * Today, Y-m-d.
	 *
	 * @return string
	 */
	private function today(): string {
		return (new DateTimeImmutable())->setTimestamp($this->clock->getTime())->format('Y-m-d');

	}//end today()
}//end class
