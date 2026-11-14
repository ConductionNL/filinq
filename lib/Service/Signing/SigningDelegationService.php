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
 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
 */
class SigningDelegationService {

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
	 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	public function declare(string $delegator, string $delegate, string $from, string $until, array $types): array {
		$delegator = trim($delegator);
		$delegate = trim($delegate);
		if ($delegator === '' || $delegate === '') {
			throw new InvalidArgumentException('A delegation names the signer and the colleague');
		}

		if ($delegator === $delegate) {
			throw new InvalidArgumentException('A signer cannot delegate to themselves');
		}

		if ($this->isDate(value: $from) === false || $this->isDate(value: $until) === false) {
			throw new InvalidArgumentException('A delegation needs a start and an end date (YYYY-MM-DD)');
		}

		if ($until < $from) {
			throw new InvalidArgumentException('A delegation cannot end before it starts');
		}

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
	 * Withdraw a delegation the signer gave.
	 *
	 * @param string $id        The delegation
	 * @param string $delegator The signer asking; only the giver may withdraw it
	 *
	 * @return bool True when it was withdrawn, false when the signer gave no such delegation.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	public function withdraw(string $id, string $delegator): bool {
		$kept = [];
		$found = false;
		foreach ($this->all() as $delegation) {
			if ($delegation['id'] === $id && $delegation['delegator'] === $delegator && $delegator !== '') {
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
	 * The delegations a user gave and received.
	 *
	 * @param string $userId The user
	 *
	 * @return array{given: list<array<string, mixed>>, received: list<array<string, mixed>>}
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	public function forUser(string $userId): array {
		$given = [];
		$received = [];
		foreach ($this->all() as $delegation) {
			if ($userId !== '' && $delegation['delegator'] === $userId) {
				$given[] = $delegation;
			}

			if ($userId !== '' && $delegation['delegate'] === $userId) {
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
	 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	public function refusal(string $delegator, string $delegate, ?string $typeReference): ?string {
		$today = $this->today();
		$reason = 'No delegation from ' . $delegator . ' to ' . $delegate;
		foreach ($this->all() as $delegation) {
			if ($delegation['delegator'] !== $delegator || $delegation['delegate'] !== $delegate) {
				continue;
			}

			if ($today > $delegation['until']) {
				$reason = 'The delegation from ' . $delegator . ' ended on ' . $delegation['until'];
				continue;
			}

			if ($today < $delegation['from']) {
				$reason = 'The delegation from ' . $delegator . ' starts on ' . $delegation['from'];
				continue;
			}

			if ($typeReference !== null && in_array($typeReference, $delegation['types'], true) === false) {
				$type = $typeReference;
				if ($type === '') {
					$type = 'documents without a type';
				}

				$reason = 'The delegation from ' . $delegator . ' does not cover ' . $type;
				continue;
			}

			return null;
		}//end foreach

		return $reason;

	}//end refusal()

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
	 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
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
	 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
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
	 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
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
			if (is_array($row) === true && isset($row['id'], $row['delegator'], $row['delegate'], $row['from'], $row['until']) === true) {
				$row['types'] = array_values(array_map('strval', (array) ($row['types'] ?? [])));
				$delegations[] = $row;
			}
		}

		return $delegations;

	}//end all()

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
