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

use OCA\Filinq\Service\SigningService;
use RuntimeException;
use Symfony\Component\Uid\UuidV4;
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
class SigningEnvelopeService {

	/**
	 * Most documents one envelope may hold.
	 */
	public const MAX_DOCUMENTS = 25;

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
		$documents = $this->documents(value: ($input['documents'] ?? null));
		$uuid = (new UuidV4())->toRfc4122();

		$signers = [];
		foreach ((array) ($input['signers'] ?? []) as $signer) {
			$signers[] = ['envelopeRef' => $uuid] + (array) $signer;
		}

		$common = ['signers' => $signers, 'envelopeRef' => $uuid];
		foreach (['signatureLevel', 'signingMode', 'provider', 'requiredAssurance', 'deadline'] as $key) {
			if (isset($input[$key]) === true && $input[$key] !== '') {
				$common[$key] = $input[$key];
			}
		}

		$requestRefs = $this->createMembers(documents: $documents, common: $common);

		$title = trim((string) ($input['title'] ?? ''));
		if ($title === '') {
			$title = $documents[0]['documentName'];
		}

		$envelope = $this->repository->save(
			envelope: [
				'title' => mb_substr($title, 0, 255),
				'requestRefs' => $requestRefs,
				'initiatorUserId' => $userId,
				'signerUserIds' => $this->signerUserIds(signers: $signers),
				'documentCount' => count($requestRefs),
				'status' => 'pending',
				'createdAt' => gmdate(DATE_ATOM),
			],
			uuid: $uuid
		);

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
		$envelope = $this->reachable(id: $id, userId: $userId, isAdmin: $isAdmin, manage: false);
		if ($envelope === null) {
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
		$envelope = $this->reachable(id: $id, userId: $userId, isAdmin: false, manage: false);
		if ($envelope === null || in_array($userId, (array) ($envelope['signerUserIds'] ?? []), true) === false) {
			return null;
		}

		$open = [];
		foreach ($this->members(envelope: $envelope) as $member) {
			if (in_array($member['status'], SigningEnvelopeRollUp::OPEN, true) === true) {
				$open[] = $member['id'];
			}
		}

		$results = $this->signing->bulkSign(requestIds: $open);

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
		$envelope = $this->reachable(id: $id, userId: $userId, isAdmin: $isAdmin, manage: true);
		if ($envelope === null) {
			return null;
		}

		$cancelled = 0;
		foreach ($this->members(envelope: $envelope) as $member) {
			if (in_array($member['status'], SigningEnvelopeRollUp::OPEN, true) === true) {
				try {
					$this->signing->cancelRequest(requestId: $member['id']);
					$cancelled++;
				} catch (Throwable) {
					// Signed or declined in the meantime: left as it is.
				}
			}
		}

		$envelope['status'] = 'cancelled';
		$envelope['updatedAt'] = gmdate(DATE_ATOM);
		$envelope = $this->repository->save(envelope: $envelope, uuid: $id);

		return $this->withMembers(envelope: $envelope) + ['cancelledRequests' => $cancelled];

	}//end cancel()

	/**
	 * The envelope, when the caller may reach it.
	 *
	 * @param string $id      The envelope uuid
	 * @param string $userId  The caller
	 * @param bool   $isAdmin Whether the caller is an admin
	 * @param bool   $manage  True for cancelling: only the initiator or an admin
	 *
	 * @return array|null The envelope, or null when absent or out of reach
	 */
	private function reachable(string $id, string $userId, bool $isAdmin, bool $manage): ?array {
		$envelope = $this->repository->find(uuid: $id);
		if ($envelope === null || $userId === '') {
			return null;
		}

		$initiator = ($envelope['initiatorUserId'] ?? '') === $userId;
		$signer = in_array($userId, (array) ($envelope['signerUserIds'] ?? []), true);
		if ($isAdmin === true || $initiator === true || ($signer === true && $manage === false)) {
			return $envelope;
		}

		return null;

	}//end reachable()

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

	/**
	 * Validate the documents list.
	 *
	 * @param mixed $value The `documents` the caller sent
	 *
	 * @return list<array{documentFileId: string, documentName: string}>
	 *
	 * @throws RuntimeException 400 when it is not two to MAX_DOCUMENTS documents with a file id each
	 */
	private function documents(mixed $value): array {
		if (is_array($value) === false || count($value) < 2 || count($value) > self::MAX_DOCUMENTS) {
			throw new RuntimeException(message: 'An envelope holds 2 to ' . self::MAX_DOCUMENTS . ' documents', code: 400);
		}

		$documents = [];
		$seen = [];
		foreach (array_values($value) as $position => $document) {
			$fileId = trim((string) (((array) $document)['documentFileId'] ?? ''));
			if ($fileId === '' || isset($seen[$fileId]) === true) {
				throw new RuntimeException(message: 'Document ' . ($position + 1) . ' needs a file id of its own', code: 400);
			}

			$seen[$fileId] = true;
			$name = trim((string) (((array) $document)['documentName'] ?? ''));
			if ($name === '') {
				$name = $fileId;
			}

			$documents[] = ['documentFileId' => $fileId, 'documentName' => $name];
		}

		return $documents;

	}//end documents()

	/**
	 * The Nextcloud users among the signers, each once.
	 *
	 * @param list<array> $signers The signer entries
	 *
	 * @return list<string>
	 */
	private function signerUserIds(array $signers): array {
		$uids = [];
		foreach ($signers as $signer) {
			$uid = trim((string) ($signer['userId'] ?? ''));
			if ($uid !== '') {
				$uids[$uid] = true;
			}
		}

		return array_keys($uids);

	}//end signerUserIds()
}//end class
