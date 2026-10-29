<?php

/**
 * Contract of SigningMandateService
 *
 * The public surface of the SigningMandateService service, so callers in other
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
use RuntimeException;

/**
 * Contract of SigningMandateService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SigningMandateServiceInterface {
	/**
	 * The type reference a signing request carries, or '' when it carries none.
	 *
	 * The reference is the consuming app plus the schema of the record the
	 * document belongs to. It is a reference to that app's type, never a copy
	 * of the record.
	 *
	 * @param array<string, mixed> $request The signing request.
	 *
	 * @return string `<app>/<schema>`, or '' when either half is missing.
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function typeReference(array $request): string;

	/**
	 * Every declaration on this instance, keyed by type reference.
	 *
	 * @return array<string, array{groups: list<string>, rule: string}>
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function declarations(): array;

	/**
	 * The declaration for one type reference, or null when the type carries none.
	 *
	 * @param string $typeReference The `<app>/<schema>` reference.
	 *
	 * @return array{groups: list<string>, rule: string}|null
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function declarationFor(string $typeReference): ?array;

	/**
	 * Record a consuming app's mandate for one of its record types.
	 *
	 * @param string $typeReference The `<app>/<schema>` reference the rule binds to.
	 * @param array<int, string> $groups The groups whose members hold the mandate.
	 * @param string $rule The rule in the consuming app's own words, quoted back on a refusal.
	 *
	 * @return array{groups: list<string>, rule: string} The stored declaration.
	 *
	 * @throws InvalidArgumentException When the reference, the groups or the rule is empty.
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function declareMandate(string $typeReference, array $groups, string $rule): array;

	/**
	 * Withdraw the declaration for one type reference.
	 *
	 * @param string $typeReference The `<app>/<schema>` reference.
	 *
	 * @return bool True when a declaration was withdrawn, false when there was none.
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function withdrawMandate(string $typeReference): bool;

	/**
	 * May this user sign this request's document.
	 *
	 * @param array<string, mixed> $request The signing request.
	 * @param string $userId The Nextcloud user asking.
	 *
	 * @return bool True when no declaration restricts the type, or the user holds the mandate.
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function maySign(array $request, string $userId): bool;

	/**
	 * Refuse a signature outside the mandate, naming the rule that refused it.
	 *
	 * @param array<string, mixed> $request The signing request.
	 * @param string $userId The Nextcloud user asking.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the user is outside the declared mandate.
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function assertMaySign(array $request, string $userId): void;
}//end interface
