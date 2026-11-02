<?php

/**
 * Contract of SignerAuthSettings
 *
 * The public surface of the SignerAuthSettings service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
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

namespace OCA\Filinq\Kernel\Contracts;

use InvalidArgumentException;

/**
 * Contract of SignerAuthSettings.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SignerAuthSettingsInterface {
	/**
	 * The providers an admin can select.
	 *
	 * @var list<string>
	 */
	public const SELECTABLE_PROVIDERS = ['nextcloud-session', 'oidc-broker'];

	/**
	 * The settings as the admin panel shows them.
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function toArray(): array;

	/**
	 * Validate every given field, then write them all.
	 *
	 * Nothing is written when any field is invalid.
	 *
	 * @param array<string, mixed> $data Settings fields; unknown fields are ignored.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When a field is invalid; the message names it.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function update(array $data): void;

	/**
	 * The provider an admin selected.
	 *
	 * @return string The identifier, as stored; resolution is strict in the factory.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function providerId(): string;

	/**
	 * A broker parameter.
	 *
	 * @param string $field `issuer`, `clientId`, `authorizationEndpoint`, `tokenEndpoint`,
	 *                      `redirectUri`, `scopes` or `credentialRef`.
	 *
	 * @return string The value, '' when unset.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function broker(string $field): string;

	/**
	 * The admin's acr mapping, decoded; [] when unset or unreadable.
	 *
	 * @return array<string, array{means: string, assurance: string}>
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function acrMapping(): array;

	/**
	 * Is every broker parameter present, over https.
	 *
	 * @return bool True when the broker can be used.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function isBrokerConfigured(): bool;

	/**
	 * How old identity evidence may be at the signing act.
	 *
	 * @return int Seconds; 15 minutes when unset or out of range.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function evidenceMaxAgeSeconds(): int;

	/**
	 * The assurance every guardian must hold at least, whatever the request asks.
	 *
	 * @return string `low` (no raise) unless an admin set more.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function guardianMinimumAssurance(): string;
}//end interface
