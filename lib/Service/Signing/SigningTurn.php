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
 * @spec openspec/changes/finish-half-built-capabilities/tasks.md#4-signing
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
	 * @spec openspec/changes/finish-half-built-capabilities/tasks.md#4-signing
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
	 * A signer's name for the refusal: display name, else email, else id.
	 *
	 * @param string               $signerId The id.
	 * @param array<string, mixed> $signer   The record.
	 *
	 * @return string The name.
	 */
	private static function nameOf(string $signerId, array $signer): string {
		foreach (['displayName', 'name', 'email'] as $key) {
			$value = trim((string)($signer[$key] ?? ''));
			if ($value !== '') {
				return $value;
			}
		}

		return $signerId;
	}//end nameOf()
}//end class
