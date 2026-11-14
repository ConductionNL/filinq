<?php

/**
 * Signing Envelope Service
 *
 * One ceremony over several documents: create the envelope and its member
 * requests, read it with its members and roll-up, sign every member at once,
 * cancel it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SigningEnvelope
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SigningEnvelope;

use OCA\Filinq\Kernel\Contracts\SigningEnvelopeServiceInterface;
use OCA\Filinq\Service\SigningService;
use RuntimeException;
use Throwable;

/**
 * Envelopes group ordinary signing requests; they never sign anything themselves.
 *
 * Every member is a normal request created through SigningService, so it
 * keeps its own gates, audit trail and artifact. The envelope adds one
 * notification per signer (its own `created` rule; member signer records
 * carry `envelopeRef`, which the per-document rule skips), one place to sign
 * everything, and a status rolled up from its members.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SigningEnvelope
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 */
class SigningEnvelopeService implements SigningEnvelopeServiceInterface {

	/**
	 * Constructor.
	 *
	 * @param SigningService            $signing    Creates, reads, signs and cancels the member requests
	 * @param SigningEnvelopeRepository $repository Envelope storage
	 * @param SigningEnvelopeRollUp     $rollUp     The status rules
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	public function __construct(
		private readonly SigningService $signing,
		private readonly SigningEnvelopeRepository $repository,
		private readonly SigningEnvelopeRollUp $rollUp = new SigningEnvelopeRollUp(),
	) {

	}//end __construct()

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
	public function create(array $input, string $userId): array {
		$draft = new SigningEnvelopeDraft(input: $input);
		$requestRefs = $this->createMembers(documents: $draft->documents, common: $draft->shared());
		$envelope = $this->repository->save(envelope: $draft->record(requestRefs: $requestRefs, userId: $userId), uuid: $draft->uuid);

		return $this->withMembers(envelope: $envelope);

	}//end create()

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
	public function get(string $id, string $userId, bool $isAdmin): ?array {
		$caller = new EnvelopeCaller(userId: $userId, isAdmin: $isAdmin);
		$envelope = $this->found(id: $id, caller: $caller);
		if ($envelope === null || $caller->mayRead(envelope: $envelope) === false) {
			return null;
		}

		return $this->withMembers(envelope: $envelope);

	}//end get()

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
	public function listFor(string $userId, bool $isAdmin): array {
		$initiator = $userId;
		if ($isAdmin === true) {
			$initiator = null;
		}

		return $this->repository->list(initiator: $initiator);

	}//end listFor()

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
	public function signAll(string $id, string $userId): ?array {
		$caller = new EnvelopeCaller(userId: $userId, isAdmin: false);
		$envelope = $this->found(id: $id, caller: $caller);
		if ($envelope === null || $caller->isSignerOf(envelope: $envelope) === false) {
			return null;
		}

		$results = $this->signing->bulkSign(requestIds: $this->openMemberIds(envelope: $envelope));

		return ['envelope' => $this->withMembers(envelope: $envelope), 'results' => $results];

	}//end signAll()

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
	public function cancel(string $id, string $userId, bool $isAdmin): ?array {
		$caller = new EnvelopeCaller(userId: $userId, isAdmin: $isAdmin);
		$envelope = $this->found(id: $id, caller: $caller);
		if ($envelope === null || $caller->mayManage(envelope: $envelope) === false) {
			return null;
		}

		$cancelled = $this->cancelOpen(requestIds: $this->openMemberIds(envelope: $envelope));

		$envelope['status'] = 'cancelled';
		$envelope['updatedAt'] = gmdate(DATE_ATOM);
		$envelope = $this->repository->save(envelope: $envelope, uuid: $id);

		return $this->withMembers(envelope: $envelope) + ['cancelledRequests' => $cancelled];

	}//end cancel()

	/**
	 * The stored envelope, when there is one and a caller to ask for it.
	 *
	 * Whether the caller may reach it is the caller's question
	 * ({@see EnvelopeCaller::mayRead()} and the like).
	 *
	 * @param string         $id     The envelope uuid
	 * @param EnvelopeCaller $caller Who is asking
	 *
	 * @return array|null The envelope, or null when absent or asked for by nobody
	 */
	private function found(string $id, EnvelopeCaller $caller): ?array {
		$envelope = $this->repository->find(uuid: $id);
		if ($envelope === null || $caller->userId === '') {
			return null;
		}

		return $envelope;

	}//end found()

	/**
	 * The ids of the members that can still be signed or cancelled, in envelope order.
	 *
	 * @param array $envelope The envelope
	 *
	 * @return list<string>
	 */
	private function openMemberIds(array $envelope): array {
		$open = [];
		foreach ($this->members(envelope: $envelope) as $member) {
			if (in_array($member['status'], SigningEnvelopeRollUp::OPEN, true) === true) {
				$open[] = $member['id'];
			}
		}

		return $open;

	}//end openMemberIds()

	/**
	 * Cancel each open member, counting the ones that were cancelled.
	 *
	 * @param list<string> $requestIds The open member request ids
	 *
	 * @return int How many were cancelled.
	 */
	private function cancelOpen(array $requestIds): int {
		$cancelled = 0;
		foreach ($requestIds as $requestId) {
			try {
				$this->signing->cancelRequest(requestId: $requestId);
				$cancelled++;
			} catch (Throwable) {
				// Signed or declined in the meantime: left as it is.
			}
		}

		return $cancelled;

	}//end cancelOpen()

	/**
	 * The envelope with its members, its status rolled up and stored when it moved.
	 *
	 * @param array $envelope The stored envelope
	 *
	 * @return array The envelope with `members`
	 */
	private function withMembers(array $envelope): array {
		$members = $this->members(envelope: $envelope);
		$status = $this->rollUp->statusOf(
			memberStatuses: array_column($members, 'status'),
			current: (string) ($envelope['status'] ?? 'pending')
		);

		if ($status !== ($envelope['status'] ?? '')) {
			$envelope['status'] = $status;
			$envelope['updatedAt'] = gmdate(DATE_ATOM);
			$envelope = $this->repository->save(envelope: $envelope, uuid: (string) $envelope['uuid']);
		}

		$envelope['members'] = $members;

		return $envelope;

	}//end withMembers()

	/**
	 * The member requests, in envelope order.
	 *
	 * The requests are read unscoped: the caller was checked against the
	 * envelope, and a member signer is a signer of every member.
	 *
	 * @param array $envelope The envelope
	 *
	 * @return list<array{id: string, documentName: string, documentFileId: string, status: string}>
	 */
	private function members(array $envelope): array {
		$members = [];
		foreach ((array) ($envelope['requestRefs'] ?? []) as $ref) {
			try {
				$request = ($this->signing->getRequest(requestId: (string) $ref) ?? []);
			} catch (Throwable) {
				$request = ['status' => 'MISSING'];
			}

			$members[] = [
				'id' => (string) $ref,
				'documentName' => (string) ($request['documentName'] ?? ''),
				'documentFileId' => (string) ($request['documentFileId'] ?? ''),
				'status' => (string) ($request['status'] ?? 'MISSING'),
			];
		}

		return $members;

	}//end members()

	/**
	 * Create the member requests, or cancel the ones made and refuse.
	 *
	 * @param list<array{documentFileId: string, documentName: string}> $documents The documents
	 * @param array                                                     $common    What every request shares
	 *
	 * @return list<string> The member request ids, in document order
	 *
	 * @throws RuntimeException 400 naming the document that could not become a request
	 */
	private function createMembers(array $documents, array $common): array {
		$refs = [];
		foreach ($documents as $position => $document) {
			try {
				$created = $this->signing->createRequest(data: $document + $common);
				$refs[] = (string) ($created['id'] ?? $created['uuid'] ?? '');
			} catch (Throwable $e) {
				foreach ($refs as $ref) {
					try {
						$this->signing->cancelRequest(requestId: $ref);
					} catch (Throwable) {
						// Nothing to undo beyond what cancel can reach.
					}
				}

				throw new RuntimeException(
					message: 'Document ' . ($position + 1) . ' (' . $document['documentName'] . '): ' . $e->getMessage(),
					code: 400,
					previous: $e
				);
			}//end try
		}//end foreach

		return $refs;

	}//end createMembers()

}//end class
