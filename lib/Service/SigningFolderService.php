<?php

/**
 * Signing Folder Service
 *
 * Gathers everything waiting for one signer into one folder, across every
 * record and every consuming app, and signs a selection from it through the
 * per-request path each document would take on its own.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
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

namespace OCA\Filinq\Service;

use Exception;
use OCA\Filinq\Exception\StepUpRequiredException;
use OCA\Filinq\Service\Signing\SigningDelegationService;
use OCA\Filinq\Kernel\Contracts\SigningFolderServiceInterface;
use RuntimeException;

/**
 * The signing folder: a query, not a list somebody keeps.
 *
 * A wethouder signing forty decisions on a Friday afternoon opens one folder,
 * not forty cases. The folder is read at the moment it is asked for and is
 * never stored, so a request cancelled elsewhere is gone the next time it is
 * opened without anything having to rebuild anything.
 *
 * What a signature means and what it proves is answered in one place already,
 * by SigningVerificationService (filinq#1121). The folder shows the documents
 * and reuses the signing path; it says nothing of its own about what the
 * resulting signature is worth.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
 */
class SigningFolderService implements SigningFolderServiceInterface {

	/**
	 * The statuses a request must be in to hold a signature still to be given.
	 *
	 * @var list<string>
	 */
	private const OPEN_STATUSES = ['PENDING', 'IN_PROGRESS'];

	/**
	 * Constructor.
	 *
	 * @param SigningService $signingService The per-request signing path, reused unchanged.
	 * @param SigningActorResolver $actorResolver Finds the caller's pending signer record.
	 * @param SigningMandateService $mandateService Applies the consuming app's mandate.
	 * @param SigningDelegationService|null $delegations Who may sign for whom, for a period (REQ-SCR-003).
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SigningService $signingService,
		private readonly SigningActorResolver $actorResolver,
		private readonly SigningMandateService $mandateService,
		private readonly ?SigningDelegationService $delegations = null,
	) {

	}//end __construct()

	/**
	 * Everything pending for one signer, in one folder.
	 *
	 * Ordered by deadline first and age second. A document with no deadline
	 * sorts after every document that has one: an undated request is not
	 * urgent, and putting it first would push the dated ones off the screen.
	 *
	 * @param string $userId The signer asking.
	 * @param int $limit Page size (1..200).
	 * @param int $offset Page offset.
	 *
	 * @return array{total: int, limit: int, offset: int, entries: list<array<string, mixed>>}
	 *
	 * @throws RuntimeException When there is no authenticated user.
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function folder(string $userId, int $limit = 50, int $offset = 0): array {
		if ($userId === '') {
			throw new RuntimeException('No authenticated user');
		}

		$limit = max(1, min(200, $limit));
		$offset = max(0, $offset);

		$entries = [];
		foreach ($this->signingService->listRequests(callerUserId: $userId) as $request) {
			$entry = $this->entryFor(request: $request, userId: $userId);
			if ($entry !== null) {
				$entries[] = $entry;
			}
		}

		$entries = array_merge($entries, $this->delegatedEntries(userId: $userId));

		usort($entries, [$this, 'compareEntries']);

		return [
			'total' => count($entries),
			'limit' => $limit,
			'offset' => $offset,
			'entries' => array_values(array_slice($entries, $offset, $limit)),
		];

	}//end folder()

	/**
	 * Sign a selection from the folder, one pass, one record per document.
	 *
	 * Every document goes through SigningService::sign(), so each gets its own
	 * signature, artifact and audit entry, and meets the same level floors and
	 * honest-completion gates it would meet as a single request. A refusal is
	 * reported against its own document and the pass carries on.
	 *
	 * The pass is resumable because it holds no state of its own: a document
	 * already signed no longer has a pending signer record, so a second pass
	 * over the same selection reports it as settled and signs the rest. No
	 * document is ever left half signed, because a document is one sign()
	 * call and that call either completes or throws.
	 *
	 * @param array<int, string> $requestIds The selected signing requests.
	 * @param string $userId The signer asking.
	 *
	 * @return array{signed: int, refused: int, results: list<array<string, mixed>>}
	 *
	 * @throws RuntimeException When there is no authenticated user.
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function signSelection(array $requestIds, string $userId): array {
		if ($userId === '') {
			throw new RuntimeException('No authenticated user');
		}

		$results = [];
		$signed = 0;
		foreach ($requestIds as $requestId) {
			$requestId = (string)$requestId;
			$result = $this->signOne(requestId: $requestId, userId: $userId);
			if (($result['signed'] ?? false) === true) {
				$signed++;
			}

			$results[] = $result;
		}

		return [
			'signed' => $signed,
			'refused' => (count($results) - $signed),
			'results' => $results,
		];

	}//end signSelection()

	/**
	 * Sign one document of the selection, reporting its own refusal.
	 *
	 * @param string $requestId The signing request.
	 * @param string $userId The signer asking.
	 *
	 * @return array<string, mixed> The per-document result.
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
	 */
	private function signOne(string $requestId, string $userId): array {
		$signerId = null;
		try {
			$request = $this->signingService->getRequest(requestId: $requestId, callerUserId: $userId);
			if ($request === null) {
				// A delegate is no party to the request; the delegation reaches it.
				return $this->delegatedOrRefused(requestId: $requestId, userId: $userId, reason: 'This signing request is not available to you');
			}

			$signerId = $this->actorResolver->findSignerForUser(
				signerIds: (array)($request['signerIds'] ?? []),
				userId: $userId
			);

			if ($signerId === null) {
				// Already signed, declined, or never this person's to sign.
				// A resumed pass lands here for everything the first pass
				// completed, which is what makes resuming safe.
				return $this->delegatedOrRefused(requestId: $requestId, userId: $userId, reason: 'No signature is pending from you on this document');
			}

			$this->mandateService->assertMaySign(request: $request, userId: $userId);

			$signer = $this->signingService->sign(requestId: $requestId, signerId: $signerId);

			return [
				'requestId' => $requestId,
				'documentName' => (string)($request['documentName'] ?? ''),
				'signed' => true,
				'signerId' => $signerId,
				'signedAt' => (string)($signer['signedAt'] ?? ''),
			];
		} catch (StepUpRequiredException $e) {
			// Identity rails (signer-identity-rails REQ-DDSIR-003): the folder
			// offers the step-up for this document, so it needs the hint and
			// the signer record the step-up is started for.
			return $this->refusal(requestId: $requestId, reason: 'This document asks for a stronger identity check')
				+ ['signerId' => (string)$signerId, 'stepUp' => $e->stepUp()];
		} catch (Exception $e) {
			return $this->refusal(requestId: $requestId, reason: $e->getMessage());
		}//end try

	}//end signOne()

	/**
	 * Sign for a signer who delegated to this user, or refuse the document with this reason.
	 *
	 * @param string $requestId The signing request.
	 * @param string $userId    The user asking.
	 * @param string $reason    The refusal when no delegation reaches the document.
	 *
	 * @return array<string, mixed> The per-document result.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	private function delegatedOrRefused(string $requestId, string $userId, string $reason): array {
		return $this->signDelegated(requestId: $requestId, userId: $userId)
			?? $this->refusal(requestId: $requestId, reason: $reason);

	}//end delegatedOrRefused()

	/**
	 * Sign one document for a signer who delegated to this user, or null when
	 * no signer who ever delegated to them has a signature pending on it.
	 *
	 * A delegation that has ended, has not started or does not cover the
	 * document's type refuses the signature, naming that delegation.
	 *
	 * @param string $requestId The signing request.
	 * @param string $userId    The delegate asking.
	 *
	 * @return array<string, mixed>|null The per-document result, or null.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	private function signDelegated(string $requestId, string $userId): ?array {
		if ($this->delegations === null) {
			return null;
		}

		foreach ($this->delegations->delegatorsOf(delegate: $userId) as $delegator) {
			$result = $this->signForDelegator(delegations: $this->delegations, requestId: $requestId, userId: $userId, delegator: $delegator);
			if ($result !== null) {
				return $result;
			}
		}

		return null;

	}//end signDelegated()

	/**
	 * Sign one document for one signer who delegated to this user, or null when
	 * that signer has no signature pending on it.
	 *
	 * @param SigningDelegationService $delegations The delegations
	 * @param string                   $requestId   The signing request.
	 * @param string                   $userId      The delegate asking.
	 * @param string                   $delegator   The signer who delegated.
	 *
	 * @return array<string, mixed>|null The per-document result, or null.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	private function signForDelegator(SigningDelegationService $delegations, string $requestId, string $userId, string $delegator): ?array {
		$request = $this->signingService->getRequest(requestId: $requestId, callerUserId: $delegator);
		if ($request === null) {
			return null;
		}

		$signerId = $this->actorResolver->findSignerForUser(signerIds: (array) ($request['signerIds'] ?? []), userId: $delegator);
		if ($signerId === null) {
			return null;
		}

		$refusal = $delegations->refusal(
			delegator: $delegator,
			delegate: $userId,
			typeReference: $this->mandateService->typeReference($request)
		);
		if ($refusal !== null) {
			return $this->refusal(requestId: $requestId, reason: 'Signing refused: ' . $refusal);
		}

		$signer = $this->signingService->signOnBehalfOf(requestId: $requestId, signerId: $signerId, delegator: $delegator);

		return [
			'requestId' => $requestId,
			'documentName' => (string) ($request['documentName'] ?? ''),
			'signed' => true,
			'signerId' => $signerId,
			'signedAt' => (string) ($signer['signedAt'] ?? ''),
			'onBehalfOf' => $delegator,
		];

	}//end signForDelegator()

	/**
	 * The folder entries of the signers who delegated to this user and whose
	 * delegation covers today and the document's type.
	 *
	 * @param string $userId The delegate asking.
	 *
	 * @return list<array<string, mixed>> The entries, each naming `onBehalfOf`.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	private function delegatedEntries(string $userId): array {
		if ($this->delegations === null) {
			return [];
		}

		$entries = [];
		foreach ($this->delegations->activeDelegators(delegate: $userId) as $delegator) {
			$entries = array_merge($entries, $this->coveredEntries(delegations: $this->delegations, delegator: $delegator, userId: $userId));
		}

		return $entries;

	}//end delegatedEntries()

	/**
	 * The folder entries of one signer who delegated to this user, for the
	 * documents whose type the delegation covers.
	 *
	 * @param SigningDelegationService $delegations The delegations
	 * @param string                   $delegator   The signer who delegated.
	 * @param string                   $userId      The delegate asking.
	 *
	 * @return list<array<string, mixed>> The entries, each naming `onBehalfOf`.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	private function coveredEntries(SigningDelegationService $delegations, string $delegator, string $userId): array {
		$entries = [];
		foreach ($this->signingService->listRequests(callerUserId: $delegator) as $request) {
			$typeReference = $this->mandateService->typeReference($request);
			if ($delegations->refusal(delegator: $delegator, delegate: $userId, typeReference: $typeReference) !== null) {
				continue;
			}

			$entry = $this->entryFor(request: $request, userId: $delegator);
			if ($entry !== null) {
				$entries[] = $entry + ['onBehalfOf' => $delegator];
			}
		}

		return $entries;

	}//end coveredEntries()

	/**
	 * A refusal against one document.
	 *
	 * @param string $requestId The signing request.
	 * @param string $reason Why this document was not signed.
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
	 */
	private function refusal(string $requestId, string $reason): array {
		return [
			'requestId' => $requestId,
			'signed' => false,
			'reason' => $reason,
		];

	}//end refusal()

	/**
	 * The folder entry for one request, or null when it does not belong in the folder.
	 *
	 * @param array<string, mixed> $request The signing request.
	 * @param string $userId The signer asking.
	 *
	 * @return array<string, mixed>|null
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
	 */
	private function entryFor(array $request, string $userId): ?array {
		if (in_array((string)($request['status'] ?? ''), self::OPEN_STATUSES, true) === false) {
			return null;
		}

		$signerId = $this->actorResolver->findSignerForUser(
			signerIds: (array)($request['signerIds'] ?? []),
			userId: $userId
		);

		if ($signerId === null) {
			return null;
		}

		if ($this->mandateService->maySign(request: $request, userId: $userId) === false) {
			// Outside the mandate the consuming app declared, so absent from
			// the folder rather than present and unsignable.
			return null;
		}

		return $this->project(request: $request, signerId: $signerId);

	}//end entryFor()

	/**
	 * Project one request onto a folder entry.
	 *
	 * The projection is an allow-list, deliberately. A consuming app that
	 * sends more than filinq asked for must not have its case fields copied
	 * into a filinq surface by accident: the entry holds a reference to the
	 * record and nothing out of it.
	 *
	 * @param array<string, mixed> $request The signing request.
	 * @param string $signerId The caller's pending signer record.
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
	 */
	private function project(array $request, string $signerId): array {
		$requestId = (string)($request['id'] ?? ($request['uuid'] ?? ''));

		return [
			'requestId' => $requestId,
			'signerId' => $signerId,
			'documentName' => (string)($request['documentName'] ?? ''),
			'documentFileId' => (string)($request['documentFileId'] ?? ''),
			'signatureLevel' => (string)($request['signatureLevel'] ?? ''),
			'provider' => (string)($request['provider'] ?? ''),
			'status' => (string)($request['status'] ?? ''),
			'requestedBy' => (string)($request['initiatorUserId'] ?? ''),
			'requestedAt' => $this->requestedAt(request: $request),
			'deadline' => (string)($request['deadline'] ?? ''),
			'fields' => $this->fieldsOf(request: $request, signerId: $signerId),
			'record' => [
				'app' => (string)($request['sourceApp'] ?? ''),
				'register' => (string)($request['subjectRegister'] ?? ''),
				'schema' => (string)($request['subjectSchema'] ?? ''),
				'id' => (string)($request['subjectId'] ?? ''),
				'label' => (string)($request['subjectLabel'] ?? ''),
				'reference' => (string)($request['externalReference'] ?? ''),
				'type' => $this->mandateService->typeReference($request),
			],
		];

	}//end project()

	/**
	 * The text fields this signer fills in, so the folder can open the "Fill in
	 * and sign" dialog for them and keep the document out of selection signing.
	 *
	 * Only the settings the dialog needs leave the request: where the field
	 * is (its placement position), its label, whether it is required and its
	 * check. No other signer's fields are included.
	 *
	 * @param array<string, mixed> $request  The signing request.
	 * @param string               $signerId The caller's pending signer record.
	 *
	 * @return list<array{placement: int, label: string, required: bool, validation: string|null}> The fields.
	 *
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-3.2
	 */
	private function fieldsOf(array $request, string $signerId): array {
		$placements = ($request['fieldPlacements'] ?? []);
		$position   = array_search($signerId, array_map('strval', array_values((array)($request['signerIds'] ?? []))), true);
		if (is_array($placements) === false || $position === false) {
			return [];
		}

		$fields = [];
		foreach ($placements as $index => $placement) {
			if (is_array($placement) === false
				|| ($placement['type'] ?? null) !== 'text'
				|| (int)($placement['signerIndex'] ?? -1) !== $position
			) {
				continue;
			}

			$label = ($placement['label'] ?? '');
			if (is_string($label) === false) {
				$label = '';
			}

			$validation = ($placement['validation'] ?? null);
			if (is_string($validation) === false) {
				$validation = null;
			}

			$fields[] = [
				'placement' => (int)$index,
				'label' => $label,
				'required' => ($placement['required'] ?? false) === true,
				'validation' => $validation,
			];
		}

		return $fields;

	}//end fieldsOf()

	/**
	 * When the signature was asked for.
	 *
	 * OpenRegister carries its own timestamps under `@self`; older rows read
	 * back with a flat `created`. Both are read, neither is required.
	 *
	 * @param array<string, mixed> $request The signing request.
	 *
	 * @return string An ISO 8601 timestamp, or ''.
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
	 */
	private function requestedAt(array $request): string {
		$self = ($request['@self'] ?? []);
		if (is_array($self) === true && ($self['created'] ?? '') !== '') {
			return (string)$self['created'];
		}

		return (string)($request['created'] ?? '');

	}//end requestedAt()

	/**
	 * Deadline first, age second.
	 *
	 * @param array<string, mixed> $left The first entry.
	 * @param array<string, mixed> $right The second entry.
	 *
	 * @return int
	 *
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod) Called as the callable array
	 * `[$this, 'compareEntries']` from usort() at line 109. PHPMD resolves only
	 * direct `$this->method()` calls, so a callable-array reference reads to it
	 * as no caller at all — a false positive, verified by grep.
	 *
	 * @psalm-suppress UnusedReturnValue usort() consumes the comparison; psalm
	 * reads the callable array no better than PHPMD does.
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
	 */
	private function compareEntries(array $left, array $right): int {
		$leftDeadline = (string)($left['deadline'] ?? '');
		$rightDeadline = (string)($right['deadline'] ?? '');

		if ($leftDeadline !== $rightDeadline) {
			if ($leftDeadline === '') {
				return 1;
			}

			if ($rightDeadline === '') {
				return -1;
			}

			return strcmp($leftDeadline, $rightDeadline);
		}

		// Same deadline, or neither has one: the one asked for longest ago
		// comes first. An entry with no request date sorts last for the same
		// reason an undated deadline does.
		$leftAsked = (string)($left['requestedAt'] ?? '');
		$rightAsked = (string)($right['requestedAt'] ?? '');

		if ($leftAsked === $rightAsked) {
			return 0;
		}

		if ($leftAsked === '') {
			return 1;
		}

		if ($rightAsked === '') {
			return -1;
		}

		return strcmp($leftAsked, $rightAsked);

	}//end compareEntries()
}//end class
