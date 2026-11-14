<?php

/**
 * Filinq RecipientRole
 *
 * Whether a signing request's recipient signs or only accepts
 * (signing-accept-only-recipient D1). `signerRecord.role` already tells a
 * signer from a guardian (signer-identity-rails); an accept-only recipient
 * is the third value, `accept`. Every other value, and no value, signs, so
 * no existing record changes. An entry may also say `sign`, which stores as
 * `signer`.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/signing-accept-only-recipient/tasks.md#2-service-and-routes
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Signing;

use InvalidArgumentException;

/**
 * The sign-or-accept role of a recipient.
 *
 * @spec openspec/changes/signing-accept-only-recipient/tasks.md#2-service-and-routes
 */
final class RecipientRole {

	/**
	 * The role of a recipient who accepts without signing.
	 */
	public const ACCEPT = 'accept';

	/**
	 * The status of a recipient who accepted.
	 */
	public const ACCEPTED = 'ACCEPTED';

	/**
	 * Whether a stored recipient or a request entry only accepts.
	 *
	 * @param array<string, mixed> $recipient The signer record or the `signers` entry.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/signing-accept-only-recipient/tasks.md#2-service-and-routes
	 */
	public static function accepts(array $recipient): bool {
		return strtolower(trim((string)($recipient['role'] ?? ''))) === self::ACCEPT;
	}//end accepts()

	/**
	 * Whether a recipient has done what was asked: signed, or accepted.
	 *
	 * @param array<string, mixed> $recipient The signer record.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/signing-accept-only-recipient/tasks.md#2-service-and-routes
	 */
	public static function isDone(array $recipient): bool {
		$status = (string)($recipient['status'] ?? '');
		if (self::accepts(recipient: $recipient) === true) {
			return $status === self::ACCEPTED;
		}

		return $status === 'SIGNED';
	}//end isDone()

	/**
	 * Refuse a request whose recipients only accept: nothing would be signed.
	 *
	 * @param array<int|string, mixed> $entries The request's `signers` entries.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException With code 400 when no entry signs.
	 *
	 * @spec openspec/changes/signing-accept-only-recipient/tasks.md#2-service-and-routes
	 */
	public static function assertSomeoneSigns(array $entries): void {
		foreach ($entries as $entry) {
			if (is_array($entry) === true && self::accepts(recipient: $entry) === false) {
				return;
			}
		}

		throw new InvalidArgumentException('A signing request needs at least one recipient who signs', 400);
	}//end assertSomeoneSigns()
}//end class
