<?php

/**
 * Contract of SchemaFieldFillService
 *
 * The public surface of the SchemaFieldFillService service, so callers in other
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

use OCA\Filinq\Exception\FieldFillRefused;

/**
 * Contract of SchemaFieldFillService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SchemaFieldFillServiceInterface {
	/**
	 * Whether model filling is switched on, and which provider would read the documents.
	 *
	 * @return array{enabled: bool, provider: string|null, available: bool} The state.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function status(): array;

	/**
	 * Switch model filling on or off.
	 *
	 * @param bool $enabled The new state.
	 *
	 * @return array{enabled: bool, provider: string|null, available: bool} The state after the change.
	 *
	 * @throws FieldFillRefused 503 when it is switched on without a text model.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function setEnabled(bool $enabled): array;

	/**
	 * Ask the model for the declared fields of a record, read from one of its files.
	 *
	 * @param int    $fileId   The Nextcloud file id.
	 * @param string $register The record's register.
	 * @param string $schema   The record's schema.
	 * @param string $objectId The record.
	 * @param string $userId   The session user, who asked.
	 *
	 * @return array<string, mixed> The stored suggestion, with the record's current values.
	 *
	 * @throws FieldFillRefused 503 without a model, 404 for a record or file the user cannot read,
	 *                          409 without a profile, 422 when no text could be read.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function suggest(int $fileId, string $register, string $schema, string $objectId, string $userId): array;

	/**
	 * Take a person's decision on each suggested field and write the accepted and changed values.
	 *
	 * @param string               $id        The suggestion.
	 * @param array<string, mixed> $decisions Per field: `decision` (accept, change or reject) and, for change, `value`.
	 * @param string               $userId    The session user, who decides.
	 *
	 * @return array<string, mixed> The decided suggestion.
	 *
	 * @throws FieldFillRefused 404 for an unknown suggestion or record, 403 for someone else's suggestion
	 *                          or a record the user may not change, 409 when already decided,
	 *                          400 for a missing decision or a changed value that does not fit.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function decide(string $id, array $decisions, string $userId): array;
}//end interface
