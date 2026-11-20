<?php

/**
 * Contract of FinalDocumentRepository
 *
 * The public surface of the FinalDocumentRepository service, so callers in other
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
 * Contract of FinalDocumentRepository.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface FinalDocumentRepositoryInterface {
	/**
	 * The OpenRegister register holding Filinq's schemas.
	 *
	 * `filinq`, not `document`: this app declares one register since the
	 * consolidation in descriptor v8.0.0.
	 *
	 * @var string
	 */
	public const REGISTER = 'filinq';

	/**
	 * The schema holding the finalisation records.
	 *
	 * @var string
	 */
	public const SCHEMA = 'documentVersion';

	/**
	 * The schema holding the dossiers.
	 *
	 * @var string
	 */
	public const DOSSIER_SCHEMA = 'dossier';

	/**
	 * The final state. Terminal: there is no transition out of it.
	 *
	 * @var string
	 */
	public const STATUS_FINAL = 'final';

	/**
	 * The draft state. Everything is draft until somebody makes it final.
	 *
	 * @var string
	 */
	public const STATUS_DRAFT = 'draft';

	/**
	 * Find every finalisation record for one document, newest last.
	 *
	 * 🔴 Returns null, not an empty array, when the register could not be read.
	 * The guard above this fails closed on null, and it can only do that if
	 * "no rows" and "no answer" arrive as different values. An unreachable
	 * register is exactly when an unnoticed write to a final document is most
	 * likely.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<int, array<string, mixed>>|null The records, or null when the register could not be read.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function findForFile(int $fileId): ?array;


	/**
	 * Find one record by its uuid.
	 *
	 * @param string $uuid The record uuid.
	 *
	 * @return array<string, mixed>|null The record, or null when there is none.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function findByUuid(string $uuid): ?array;


	/**
	 * Write a finalisation record, creating it when its uuid is not known yet.
	 *
	 * @param array<string, mixed> $record The record to store.
	 * @param string|null $uuid The uuid to write under, or null to create one.
	 *
	 * @return array<string, mixed> The stored record.
	 *
	 * @throws RuntimeException When the write fails.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function save(array $record, ?string $uuid = null): array;
}//end interface
