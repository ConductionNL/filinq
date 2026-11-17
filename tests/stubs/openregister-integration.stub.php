<?php

/**
 * PHPStan / Psalm scan stub for OpenRegister's integration provider contract
 * (ADR-066), the data half of a data-provider leaf.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Analysis-only, never loaded at runtime: the real interface lives in the
 * openregister sibling app (lib/Service/Integration/IntegrationProvider.php),
 * which is not on the static-analysis path. PHPStan refuses to silence
 * "implements unknown interface", so TemplatesLeafProvider needs the contract
 * declared. The signatures mirror the real interface; keep them in sync. The
 * PHPUnit runtime declaration is in tests/stubs/OpenRegisterStubs.php.
 *
 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.1
 */

declare(strict_types=1);

namespace OCA\OpenRegister\Service\Integration;

interface IntegrationProvider {
	public function getId(): string;

	public function getLabel(): string;

	public function getIcon(): string;

	public function getGroup(): ?string;

	public function getRequiredApp(): ?string;

	public function getStorageStrategy(): string;

	public function getOpenConnectorSource(): ?string;

	public function isEnabled(): bool;

	public function requiresPermission(): ?string;

	/**
	 * @return array<string, mixed>
	 */
	public function authRequirements(): array;

	/**
	 * @return array<string, mixed>
	 */
	public function health(): array;
}
