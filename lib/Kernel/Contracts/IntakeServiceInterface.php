<?php

/**
 * Contract of IntakeService
 *
 * The public surface of the IntakeService service, so callers in other
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

use OCA\Filinq\Event\IntakeDocumentReceivedEvent;
use OCA\Filinq\Exception\IntakeRefusedException;

/**
 * Contract of IntakeService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface IntakeServiceInterface {
	/**
	 * Take in one delivered document and put it in the inbox.
	 *
	 * A second delivery under the same source reference does NOT make a second
	 * row: the first one is returned unchanged, whatever state it is in. A
	 * feeder that retries would otherwise fill the inbox with duplicates that
	 * no clerk can tell apart.
	 *
	 * @param IntakeDocumentReceivedEvent $event What the channel delivered.
	 *
	 * @return array<string, mixed> The stored intake document.
	 *
	 * @throws IntakeRefusedException When the channel is not one this app accepts.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 * @spec openspec/changes/archive/2026-09-29-intake-ocr-on-arrival/tasks.md#task-1.3
	 */
	public function receive(IntakeDocumentReceivedEvent $event): array;

	/**
	 * Take in a message and everything that came attached to it.
	 *
	 * Each attachment is an intake document of its own naming the message it
	 * arrived with. Folding attachments into the message would lose exactly the
	 * case that matters: one attachment often belongs to a different record
	 * from the letter it came with, and a folded attachment has no way to say
	 * so.
	 *
	 * @param IntakeDocumentReceivedEvent $message What the channel delivered as the message.
	 * @param array<int, IntakeDocumentReceivedEvent> $attachments What came with it.
	 *
	 * @return array<int, array<string, mixed>> The message first, then its attachments.
	 *
	 * @throws IntakeRefusedException When a channel is not one this app accepts.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function receiveMessage(IntakeDocumentReceivedEvent $message, array $attachments = []): array;

	/**
	 * Everything that arrived with one message, the message excluded.
	 *
	 * @param string $uuid The message's intake document.
	 *
	 * @return array<int, array<string, mixed>> The attachments.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function attachmentsOf(string $uuid): array;

	/**
	 * The worklist of documents taken back off a record.
	 *
	 * @return array<int, array<string, mixed>> The detached documents.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function listDetached(): array;

	/**
	 * Everything waiting for a clerk.
	 *
	 * @return array<int, array<string, mixed>> The waiting documents.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function listWaiting(): array;

	/**
	 * Assign one waiting document to a record.
	 *
	 * @param string $uuid The intake document.
	 * @param array<string, mixed> $target The record, as `register`, `schema` and `id`,
	 *                                     optionally with the `declaringApp` and
	 *                                     `typeReference` the routing is declared against.
	 * This assigns the one document. To take everything that arrived with it
	 * along, call {@see assignWithAttachments()}.
	 *
	 * @return array<string, mixed> The assigned document.
	 *
	 * @throws IntakeRefusedException When the target is incomplete, the document
	 *                                has already left the inbox, or the clerk may
	 *                                not write one of the two schemas.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function assign(string $uuid, array $target): array;

	/**
	 * Assign one waiting document and everything that arrived with it.
	 *
	 * The message is assigned first, so a refusal on the message itself stops
	 * before any attachment moves. An attachment that has already left the
	 * inbox is skipped rather than assigned twice.
	 *
	 * @param string $uuid The intake document of the message.
	 * @param array<string, mixed> $target The record, as `register`, `schema` and `id`,
	 *                                     optionally with the `declaringApp` and
	 *                                     `typeReference` the routing is declared against.
	 *
	 * @return array<string, mixed> The assigned message.
	 *
	 * @throws IntakeRefusedException When the message itself cannot be assigned.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function assignWithAttachments(string $uuid, array $target): array;

	/**
	 * Reject one waiting document, with the reason it was rejected for.
	 *
	 * @param string $uuid The intake document.
	 * @param string $reason Why it does not belong here.
	 *
	 * @return array<string, mixed> The rejected document.
	 *
	 * @throws IntakeRefusedException When there is no reason, the document has
	 *                                already left the inbox, or the clerk may not
	 *                                write the intake register.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function reject(string $uuid, string $reason): array;
}//end interface
