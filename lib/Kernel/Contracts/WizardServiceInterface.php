<?php

/**
 * Contract of WizardService
 *
 * The public surface of the WizardService service, so callers in other
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

use OCA\Filinq\Exception\WizardRefused;

/**
 * Contract of WizardService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface WizardServiceInterface {
	/**
	 * Save a new or changed wizard.
	 *
	 * @param array<string, mixed> $wizard The definition.
	 * @param string               $userId The author.
	 * @param string|null          $uuid   The wizard to change, null for a new one.
	 *
	 * @return array{wizard: array<string, mixed>, warnings: string[]} The stored wizard and the binding warnings.
	 *
	 * @throws WizardRefused 404, 409, 422 or 423.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
	 */
	public function save(array $wizard, string $userId, ?string $uuid = null): array;

	/**
	 * One wizard.
	 *
	 * @param string $uuid The uuid.
	 *
	 * @return array<string, mixed> The wizard.
	 *
	 * @throws WizardRefused 404.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
	 */
	public function requireWizard(string $uuid): array;

	/**
	 * The active wizard of a template, if any.
	 *
	 * @param string $templateId The template uuid.
	 *
	 * @return array<string, mixed>|null The wizard.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
	 */
	public function activeFor(string $templateId): ?array;

	/**
	 * The active wizards that ask for an object of this register and schema.
	 *
	 * The object-driven entry point: a page showing such an object offers
	 * these wizards, started with the object as their entry.
	 *
	 * @param string $register The register.
	 * @param string $schema   The schema.
	 *
	 * @return array<int, array<string, mixed>> The wizards.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-3
	 */
	public function forObject(string $register, string $schema): array;

	/**
	 * Remove a wizard.
	 *
	 * @param string $uuid   The uuid.
	 * @param string $userId The caller.
	 *
	 * @return void
	 *
	 * @throws WizardRefused 404 or 423.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
	 */
	public function delete(string $uuid, string $userId): void;

	/**
	 * Suggested answers for a run started from a register object.
	 *
	 * The object is resolved through DataResolverService as the caller, so
	 * prefill sees only what the caller may read. A register object question
	 * on the entry object's register and schema gets its id; a scalar question
	 * whose `mapsTo` path resolves gets that value; every other question is
	 * unresolved and asked.
	 *
	 * @param string               $uuid  The wizard.
	 * @param array<string, mixed> $entry The entry object: register, schema, id.
	 *
	 * @return array{answers: array<string, mixed>, unresolved: string[]} The suggestions.
	 *
	 * @throws WizardRefused 404 for an unknown wizard, 422 for an entry without register, schema and id.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-5
	 */
	public function prefill(string $uuid, array $entry): array;
}//end interface
