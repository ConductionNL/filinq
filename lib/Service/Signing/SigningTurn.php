<?php

/**
 * Signing Turn
 *
 * Whose turn it is on a sequential signing request.
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

use RuntimeException;

/**
 * Refuses a sequential signer whose turn has not come.
 *
 * In sequential mode a signer may sign once every signer ordered before them
 * has signed. In parallel mode anybody may sign at any time. A request that
 * names no mode is sequential, which is the schema's default: reading an
 * absent mode as parallel would let the last signer sign first on every
 * request written before the mode was stored.
 *
 * @spec openspec/changes/archive/2026-11-20-finish-half-built-capabilities/tasks.md#4-signing
 */
final class SigningTurn {

	/**
	 * The mode in which signers sign in any order.
	 *
	 * @var string
	 */
	public const PARALLEL = 'parallel';

	/**
	 * Refuse the signature when an earlier signer has not signed yet.
	 *
	 * A signer's place is its `order`, else its position in the request's
	 * `signerIds`. A signer placed before this one whose record is given and
	 * is anything but SIGNED (pending, declined, unreadable) holds the turn.
	 * A signer whose record is not given is not judged.
	 *
	 * @param array<string, mixed>                $request  The signing request.
	 * @param string                              $signerId The signer who wants to sign.
	 * @param array<string, array<string, mixed>> $signers  Every signer record of the request, by id.
	 *
	 * @return void
	 *
	 * @throws RuntimeException Naming the signers whose turn comes first.
	 *
	 * @spec openspec/changes/archive/2026-11-20-finish-half-built-capabilities/tasks.md#4-signing
	 */
	public static function assertTurn(array $request, string $signerId, array $signers): void {
		if ((string)($request['signingMode'] ?? '') === self::PARALLEL) {
			return;
		}

		$positions = self::positions(request: $request, signers: $signers);
		if (isset($positions[$signerId]) === false) {
			return;
		}

		$waitingFor = [];
		foreach ($positions as $otherId => $position) {
			if ($position >= $positions[$signerId] || (string)$otherId === $signerId || isset($signers[$otherId]) === false) {
				continue;
			}

			if ((string)($signers[$otherId]['status'] ?? '') !== 'SIGNED') {
				$waitingFor[] = self::nameOf(signerId: (string)$otherId, signer: ($signers[$otherId] ?? []));
			}
		}

		if ($waitingFor !== []) {
			throw new RuntimeException(
				'It is not this signer\'s turn yet: this request is signed in order, and '
				. implode(', ', $waitingFor) . ' must sign first.'
			);
		}
	}//end assertTurn()

	/**
	 * The signers whose turn it is now, so each is told once.
	 *
	 * A parallel request has every pending signer's turn at once, so only its
	 * creation tells anyone. A request signed in order has the turn of the
	 * pending signers at the first position someone still has to act on; after
	 * a signature only those placed after the one who signed are new. A signer
	 * on an envelope is told by the envelope, and one who is done or declined is
	 * told nothing.
	 *
	 * @param array<string, mixed>                $request The signing request.
	 * @param array<string, array<string, mixed>> $signers Every signer record, by id.
	 * @param string|null                         $after   The signer who just acted, or null at creation.
	 *
	 * @return list<string> The signer ids to tell.
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) RecipientRole is the stateless rule for a recipient's role.
	 *
	 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-1-signerinitiator-notifications-via-the-email-leaf-m
	 */
	public static function due(array $request, array $signers, ?string $after = null): array {
		$parallel = (string)($request['signingMode'] ?? '') === self::PARALLEL;
		if ($parallel === true && $after !== null) {
			return [];
		}

		$positions = self::positions(request: $request, signers: $signers);
		$first     = self::firstOpenPosition(positions: $positions, signers: $signers);
		$floor     = ($positions[(string)$after] ?? null);

		$due = [];
		foreach ($positions as $signerId => $position) {
			$atTurn = $parallel === true || ($position === $first && ($floor === null || $position > $floor));
			if ($atTurn === true && self::waits(signer: ($signers[$signerId] ?? [])) === true) {
				$due[] = (string)$signerId;
			}
		}

		return $due;
	}//end due()

	/**
	 * The lowest position a signer who is not done yet holds.
	 *
	 * @param array<string, int>                  $positions The position per signer id.
	 * @param array<string, array<string, mixed>> $signers   The signer records, by id.
	 *
	 * @return integer|null The position, or null when everyone is done.
	 */
	private static function firstOpenPosition(array $positions, array $signers): ?int {
		$first = null;
		foreach ($positions as $signerId => $position) {
			if (RecipientRole::isDone(recipient: ($signers[$signerId] ?? [])) === false && ($first === null || $position < $first)) {
				$first = $position;
			}
		}

		return $first;
	}//end firstOpenPosition()

	/**
	 * Whether a signer waits on this request itself: pending, and not on an envelope.
	 *
	 * @param array<string, mixed> $signer The signer record.
	 *
	 * @return bool
	 */
	private static function waits(array $signer): bool {
		return (string)($signer['status'] ?? '') === 'PENDING' && (string)($signer['envelopeRef'] ?? '') === '';
	}//end waits()

	/**
	 * Each signer's place in the order.
	 *
	 * @param array<string, mixed>                $request The signing request.
	 * @param array<string, array<string, mixed>> $signers The signer records, by id.
	 *
	 * @return array<string, int> Signer id => place.
	 */
	private static function positions(array $request, array $signers): array {
		$positions = [];
		foreach (array_values((array)($request['signerIds'] ?? [])) as $index => $signerId) {
			$order = ($signers[(string)$signerId]['order'] ?? null);
			if (is_int($order) === false && (is_string($order) === false || ctype_digit($order) === false)) {
				$order = $index;
			}

			$positions[(string)$signerId] = (int)$order;
		}

		return $positions;
	}//end positions()

	/**
	 * A signer's name for a refusal or a mail: display name, else email, else the fallback.
	 *
	 * @param string               $signerId The fallback: the id, or another name.
	 * @param array<string, mixed> $signer   The record.
	 *
	 * @return string The name.
	 *
	 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-1-signerinitiator-notifications-via-the-email-leaf-m
	 */
	public static function nameOf(string $signerId, array $signer): string {
		foreach (['displayName', 'name', 'email'] as $key) {
			$value = trim((string)($signer[$key] ?? ''));
			if ($value !== '') {
				return $value;
			}
		}

		return $signerId;
	}//end nameOf()
}//end class
