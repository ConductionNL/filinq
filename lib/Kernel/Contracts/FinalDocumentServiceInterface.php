<?php

/**
 * Contract of FinalDocumentService
 *
 * The public surface of the FinalDocumentService service, so callers in other
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

use OCA\Filinq\Exception\DocumentFinalException;
use OCP\Files\File;
use RuntimeException;

/**
 * Contract of FinalDocumentService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface FinalDocumentServiceInterface {
	/**
	 * Make a document's current version final.
	 *
	 * Records who made it final, when, the reason or the act that did so, and
	 * the checksum of the file at that moment. A version that is already final
	 * is refused rather than re-finalised: the first moment is the one the
	 * archive needs, and overwriting it would lose it.
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param string $reason The reason or the act that makes it final.
	 * @param string|null $actorId The acting user id, or null for the session user.
	 * @param string|null $actorName The acting user's display name, or null to resolve it.
	 *
	 * @return array<string, mixed> The stored final record.
	 *
	 * @throws DocumentFinalException When the version is already final.
	 * @throws RuntimeException When the document cannot be read or the record cannot be stored.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function finalise(int $fileId, string $reason, ?string $actorId = null, ?string $actorName = null): array;

	/**
	 * The refusal for a write to this document, or null when it is free to change.
	 *
	 * 🔴 Fails closed. An unreadable register answers "refuse", never "allow":
	 * the whole point of the state is that a besluit cannot change quietly, and
	 * a guard that opens when its store is down is a guard that is off exactly
	 * when it matters.
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param string $action What the caller is about to do, named in the sentence.
	 *
	 * @return string|null The refusal sentence, or null when the write may proceed.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function refusalFor(int $fileId, string $action = 'change this document'): ?string;

	/**
	 * Refuse the write, loudly, when the document is final.
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param string $action What the caller is about to do, named in the sentence.
	 *
	 * @return void
	 *
	 * @throws DocumentFinalException When the document's current version is final.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function assertWritable(int $fileId, string $action = 'change this document'): void;

	/**
	 * The finalisation record of a document's current version, if it has one.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<string, mixed>|null The record, or null when the version was never finalised.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function statusOf(int $fileId): ?array;

	/**
	 * Report whether a final version's file still holds the bytes it was frozen on.
	 *
	 * Freezing the record does not freeze the filesystem. Somebody with shell
	 * access can still change the file, and this is how that becomes visible.
	 * It is detection, not prevention, and it says so rather than implying more.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<string, mixed> `{final, matches, recordedChecksum, currentChecksum, message}`.
	 *
	 * @throws RuntimeException When the document cannot be read.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function verifyChecksum(int $fileId): array;

	/**
	 * Whether a record sits in the final state.
	 *
	 * @param array<string, mixed> $record The finalisation record.
	 *
	 * @return bool True when the record is final.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function isFinal(array $record): bool;

	/**
	 * The sentence a refused caller shows, naming the version, its state, who made it final and when.
	 *
	 * "Cannot edit" with no reason is a support ticket, so the four facts the
	 * reader needs to act are all in it: which document, that it is final, who
	 * made it final, and when.
	 *
	 * @param array<string, mixed> $record The finalisation record.
	 * @param string $action What the caller was about to do.
	 *
	 * @return string The refusal sentence.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function refusalSentence(array $record, string $action = 'change this document'): string;

	/**
	 * The sha256 of a file's current content.
	 *
	 * @param File $file The file.
	 *
	 * @return string The checksum.
	 *
	 * @throws RuntimeException When the file cannot be read.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function checksumOf(File $file): string;

	/**
	 * Resolve a file through the acting user's folder.
	 *
	 * A file the user cannot read is indistinguishable from one that is not
	 * there, which is the same posture the version endpoints already take
	 * (ADR-005).
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return File The resolved file.
	 *
	 * @throws RuntimeException When the file cannot be resolved.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function resolveFile(int $fileId): File;

	/**
	 * Resolve the acting person, preferring what the caller passed.
	 *
	 * @param string|null $actorId The acting user id, or null for the session user.
	 * @param string|null $actorName The acting user's display name, or null to resolve it.
	 *
	 * @return array{id: string, name: string} The acting person.
	 *
	 * @throws RuntimeException When there is no acting person at all.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function resolveActor(?string $actorId, ?string $actorName): array;
}//end interface
