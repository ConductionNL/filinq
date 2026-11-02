<?php

/**
 * Contract of DocumentFinalityRuleService
 *
 * The public surface of the DocumentFinalityRuleService service, so callers in other
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

use RuntimeException;

/**
 * Contract of DocumentFinalityRuleService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface DocumentFinalityRuleServiceInterface {
	/**
	 * Store a consuming app's declaration for one record type.
	 *
	 * @param string $declaringApp App id of the consuming app, such as dossiq.
	 * @param string $typeReference That app's own reference for the record type.
	 * @param array<int, string> $finalStates The states that make its documents final.
	 * @param string $documentRole Which document of the record the rule covers, or '' for every one.
	 * @param string $reasonTemplate The reason written on the version, or '' to name the transition.
	 * @param string|null $declaredBy The user id storing the declaration.
	 *
	 * @return array<string, mixed> The stored declaration.
	 *
	 * @throws RuntimeException When the declaration is incomplete or cannot be stored.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function declareRule(
		string $declaringApp,
		string $typeReference,
		array $finalStates,
		string $documentRole = '',
		string $reasonTemplate = '',
		?string $declaredBy = null,
	): array;

	/**
	 * Find the declaration for one record type, if a consuming app made one.
	 *
	 * @param string $declaringApp App id of the consuming app.
	 * @param string $typeReference That app's reference for the record type.
	 * @param string $documentRole Which document of the record, or '' for every one.
	 *
	 * @return array<string, mixed>|null The declaration, or null when there is none.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function find(string $declaringApp, string $typeReference, string $documentRole = ''): ?array;

	/**
	 * Apply the declaration when a consuming app reports a state change.
	 *
	 * Nothing is frozen when no declaration covers the type: a record type with
	 * no declaration changes state and no document changes with it. A document
	 * that is already final is left alone rather than re-finalised, so a
	 * repeated state change is harmless.
	 *
	 * @param string $declaringApp App id of the consuming app.
	 * @param string $typeReference That app's reference for the record type.
	 * @param string $state The state the record has reached.
	 * @param array<int, int> $fileIds The documents of that record.
	 * @param string $documentRole Which document of the record, or '' for every one.
	 *
	 * @return array<int, array<string, mixed>> The versions this call made final, empty when none.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function applyStateChange(
		string $declaringApp,
		string $typeReference,
		string $state,
		array $fileIds,
		string $documentRole = '',
	): array;
}//end interface
