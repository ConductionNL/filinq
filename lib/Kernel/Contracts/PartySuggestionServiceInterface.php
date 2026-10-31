<?php

/**
 * Contract of PartySuggestionService
 *
 * The public surface of the PartySuggestionService service, so callers in other
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

/**
 * Contract of PartySuggestionService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PartySuggestionServiceInterface {
	/**
	 * The schema holding the corrections corpus.
	 *
	 * @var string
	 */
	public const CORRECTION_SCHEMA = 'intakePartyCorrection';

	/**
	 * Read one party out of the entities already detected on a file.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<int, array<string, mixed>> Zero or one suggestion, each with its confidence and source span.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function suggestFor(int $fileId): array;

	/**
	 * Record what a clerk did with a suggestion.
	 *
	 * @param string $sender The sender the suggestion was made for.
	 * @param string $decision One of `accepted`, `edited`, `rejected`.
	 * @param array<string, mixed> $suggested What was proposed.
	 * @param array<string, mixed> $accepted What was filed, empty on a rejection.
	 * @param string $intakeDocument The intake document it was shown on.
	 *
	 * @return array<string, mixed> The stored correction.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function recordDecision(
		string $sender,
		string $decision,
		array $suggested,
		array $accepted = [],
		string $intakeDocument = '',
	): array;
}//end interface
