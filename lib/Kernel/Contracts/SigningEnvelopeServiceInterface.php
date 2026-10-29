<?php

/**
 * Contract of SigningEnvelopeService
 *
 * The public surface of the SigningEnvelopeService service, so callers in other
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
 * Contract of SigningEnvelopeService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SigningEnvelopeServiceInterface {
	/**
	 * Most documents one envelope may hold.
	 */
	public const MAX_DOCUMENTS = 25;

	/**
	 * Create an envelope: one request per document, one signer roster for all.
	 *
	 * The envelope's uuid is chosen first, so every member request and signer
	 * record carries it from its first save. The envelope itself is stored
	 * last, when every member exists: its `created` rule is the signers' one
	 * notification. When a member cannot be created, the members made so far
	 * are cancelled and no envelope is stored.
	 *
	 * @param array  $input  title, documents [{documentFileId, documentName}], signers, signatureLevel, signingMode, provider, requiredAssurance
	 * @param string $userId The initiator (the session user)
	 *
	 * @return array The envelope with its members
	 *
	 * @throws RuntimeException 400 when the input or a member request is invalid
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	public function create(array $input, string $userId): array;

	/**
	 * Read one envelope with its members, rolled up.
	 *
	 * The initiator, a signer the envelope names, or an admin may read it;
	 * anybody else gets null, as if it did not exist.
	 *
	 * @param string $id      The envelope uuid
	 * @param string $userId  The caller
	 * @param bool   $isAdmin Whether the caller is an admin
	 *
	 * @return array|null The envelope with `members`, or null
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	public function get(string $id, string $userId, bool $isAdmin): ?array;

	/**
	 * List the caller's envelopes (the ones they started), or all for an admin.
	 *
	 * A signer finds an envelope through its member requests and the
	 * notification; the list is the initiator's overview.
	 *
	 * @param string $userId  The caller
	 * @param bool   $isAdmin Whether the caller is an admin
	 *
	 * @return list<array>
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	public function listFor(string $userId, bool $isAdmin): array;

	/**
	 * Sign every member the caller still has to sign, each through the ordinary sign().
	 *
	 * Each document is signed on its own with all its gates; a refusal on one
	 * (its turn has not come, a stronger identity is needed, it was declined)
	 * is reported for that document and the others still go ahead.
	 *
	 * @param string $id     The envelope uuid
	 * @param string $userId The caller, who must be a signer the envelope names
	 *
	 * @return array|null `{envelope, results}` with a result per member signed or refused, or null
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	public function signAll(string $id, string $userId): ?array;

	/**
	 * Cancel an envelope and every member that can still be cancelled.
	 *
	 * Members already signed or declined stay as they are: each document is
	 * its own legal record.
	 *
	 * @param string $id      The envelope uuid
	 * @param string $userId  The caller, the initiator or an admin
	 * @param bool   $isAdmin Whether the caller is an admin
	 *
	 * @return array|null The envelope with `cancelledRequests`, or null
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	public function cancel(string $id, string $userId, bool $isAdmin): ?array;
}//end interface
