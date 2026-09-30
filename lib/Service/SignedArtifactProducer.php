<?php

/**
 * Signed Artifact Producer
 *
 * Produces and stores the verifiable signed artifact for a completing signing
 * request, and resolves the two inputs it needs: the target document's `File`
 * node and a human label for the completing signer.
 *
 * Extracted verbatim from SigningService, which had grown past the
 * class-length threshold. The honesty rules it enforces are unchanged:
 *
 *  - A request with no document file id, an unreadable document, or a failed
 *    write throws — a request is never marked COMPLETED without a real signed
 *    document (signing-trust-rebuild REQ-DDSTR-002).
 *  - The request's named provider is resolved STRICTLY. An unknown provider
 *    name fails the completion loudly and is never silently substituted with
 *    `getActiveProvider()` / the native provider.
 *  - Portal evidence is sourced ONLY from the already-verified actor, never
 *    the request body, and is folded into the same MAC as the rest of the
 *    assertion (portal-signing-surface REQ-DDPSS-004).
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
 *
 * @spec openspec/specs/document-signing/spec.md
 * @spec openspec/specs/portal-signing-surface/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\Filinq\Exception\DocumentFinalException;
use InvalidArgumentException;
use OCA\Filinq\Service\Signing\FieldPlacementRenderer;
use OCA\Filinq\Service\Signing\FieldPlacements;
use OCA\Filinq\Service\Signing\LibreSignProvider;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUserSession;
use RuntimeException;

/**
 * Produces and stores the signed artifact for a completing signing request.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-signing/spec.md
 */
class SignedArtifactProducer {
	/**
	 * Constructor.
	 *
	 * @param SigningProviderFactory $providerFactory Provider factory (strict resolution).
	 * @param IUserSession $userSession User session (signer label + folder fallback).
	 * @param IRequest $request HTTP request (client IP for the evidence context).
	 * @param IRootFolder $rootFolder Root folder (reads the document, stores the signed version).
	 * @param FinalDocumentService $finalDocuments The final-document guard.
	 * @param FieldPlacements $placementRules The field placement rules.
	 * @param FieldPlacementRenderer $placementRenderer Reads the page count a placement must stay within.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SigningProviderFactory $providerFactory,
		private readonly IUserSession $userSession,
		private readonly IRequest $request,
		private readonly IRootFolder $rootFolder,
		private readonly FinalDocumentService $finalDocuments,
		private readonly FieldPlacements $placementRules = new FieldPlacements(),
		private readonly FieldPlacementRenderer $placementRenderer = new FieldPlacementRenderer(),
	) {

	}//end __construct()

	/**
	 * Produce the signed artifact and store it as a new file version.
	 *
	 * @param array<string, mixed> $request The completing signing-request array.
	 * @param array<string, mixed>|null $verifiedActor The verified external actor completing
	 *                                                 this act, when portal-originated —
	 *                                                 folded into the produced artifact's
	 *                                                 evidence binding (portal-signing-surface
	 *                                                 REQ-DDPSS-004).
	 * @param array<string, array<string, mixed>> $signers The request's signer records, keyed by id
	 *                                                     (names for placed fields).
	 *
	 * @return string The stored signed-artifact reference (file id + version).
	 *
	 * @throws DocumentFinalException When the document's current version is already final.
	 * @throws RuntimeException When no verifiable artifact can be produced/stored,
	 *                          or when the request names an unregistered provider.
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 * @spec openspec/specs/portal-signing-surface/spec.md
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function produce(array $request, ?array $verifiedActor = null, array $signers = []): string {
		$fileId = (int)($request['documentFileId'] ?? 0);
		if ($fileId <= 0) {
			throw new RuntimeException('Cannot produce a signed artifact: the request has no document file id');
		}

		// Signing writes the signed bytes over the document, so it is a write
		// path like the editors and it asks the same service. A signature is a
		// common reason a version becomes final; once it is, the next signature
		// goes on a new version rather than over the frozen one.
		$this->finalDocuments->assertWritable(
			fileId: $fileId,
			action: 'store a signed version of this document'
		);

		$file = $this->resolveDocumentFile(fileId: $fileId, request: $request);

		try {
			$originalContent = $file->getContent();
		} catch (\Throwable $e) {
			throw new RuntimeException('Cannot read the document to sign: ' . $e->getMessage());
		}

		// Provider/level honesty (REQ-DDSTR-002 point 2): resolve the request's
		// named provider strictly. An unknown provider name MUST fail the
		// completion loudly — no fallback to getActiveProvider()/native. This
		// is the honest-completion gate closing the #304 residual where a
		// request naming a misconfigured/unregistered provider silently
		// completed with a substituted (native) artifact.
		$providerName = (string)($request['provider'] ?? 'native');
		$provider = $this->providerFactory->getProvider(identifier: $providerName);

		$context = $this->buildContext(request: $request, verifiedActor: $verifiedActor);
		$context += $this->placementContext(request: $request, signers: $signers);

		$signedBytes = $provider->produceSignedArtifact(documentContent: $originalContent, context: $context);

		// Writing new content to the existing file creates a new Nextcloud file
		// version of the prior (unsigned) content automatically (files_versions).
		try {
			$file->putContent($signedBytes);
		} catch (\Throwable $e) {
			throw new RuntimeException('Cannot store the signed artifact as a new file version: ' . $e->getMessage());
		}

		// The signed-artifact reference is the file id plus a content-derived
		// version tag identifying this specific signed version — never the bare
		// original file id.
		return $fileId . ':signed:' . substr(hash('sha256', $signedBytes), 0, 16);
	}//end produce()

	/**
	 * Hand a new request to a provider that runs the signer flow itself.
	 *
	 * Only LibreSign does today: it gets the document's bytes and the
	 * signers, notifies them, and its request uuid becomes the request's
	 * `externalId`. Other providers sign inside Filinq and get the request
	 * unchanged. Runs before the request is stored, so a LibreSign that
	 * refuses leaves nothing behind.
	 *
	 * @param array<string, mixed>     $request The request about to be stored
	 * @param array<string, mixed>     $signers The signers as the caller sent them (the provider contract's type)
	 *
	 * @return array<string, mixed> The request, with externalId when delegated.
	 *
	 * @throws RuntimeException When the document cannot be read or LibreSign refuses.
	 *
	 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-3.1
	 */
	public function delegate(array $request, array $signers): array {
		if (($request['provider'] ?? '') !== LibreSignProvider::IDENTIFIER) {
			return $request;
		}

		$file = $this->resolveDocumentFile(fileId: (int)($request['documentFileId'] ?? 0), request: $request);
		$result = $this->providerFactory->getProvider(identifier: LibreSignProvider::IDENTIFIER)->initiateSigning(
			documentPath: '',
			documentName: (string)($request['documentName'] ?? ''),
			signers: $signers,
			level: (string)($request['signatureLevel'] ?? 'SES'),
			options: ['content' => $file->getContent()]
		);
		$request['externalId'] = (string)$result['externalId'];

		return $request;

	}//end delegate()

	/**
	 * Check a new request's field placements and put them on the request.
	 *
	 * The rules come from FieldPlacements; on top of them every placement must
	 * name a page the document has. LibreSign's request-signature call has no
	 * field input (its visible elements need the sign-request ids it creates),
	 * so a LibreSign request with placements is refused instead of being
	 * signed without them. Runs before the request is stored.
	 *
	 * @param array<string, mixed> $request     The request about to be stored.
	 * @param mixed                $placements  The `fieldPlacements` the caller sent.
	 * @param int                  $signerCount How many signers the request names.
	 *
	 * @return array<string, mixed> The request, with `fieldPlacements` when there are any.
	 *
	 * @throws RuntimeException 400 when a placement breaks a rule, names a page the document lacks, or the provider cannot carry placements.
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/tasks.md#task-3.1
	 */
	public function withPlacements(array $request, mixed $placements, int $signerCount): array {
		try {
			$normalised = $this->placementRules->normalise(placements: $placements, signerCount: $signerCount);
		} catch (InvalidArgumentException $e) {
			throw new RuntimeException(message: $e->getMessage(), code: 400, previous: $e);
		}

		if ($normalised === []) {
			return $request;
		}

		if (($request['provider'] ?? '') === LibreSignProvider::IDENTIFIER) {
			throw new RuntimeException(message: 'LibreSign places its own fields: send this request without field placements', code: 400);
		}

		$file = $this->resolveDocumentFile(fileId: (int) ($request['documentFileId'] ?? 0), request: $request);
		try {
			$pages = $this->placementRenderer->pageCount(pdf: $file->getContent());
		} catch (RuntimeException $e) {
			throw new RuntimeException(message: $e->getMessage(), code: 400, previous: $e);
		}
		foreach ($normalised as $placement) {
			if ($placement['page'] > $pages) {
				throw new RuntimeException(message: 'A field is placed on page '.$placement['page'].' of a document with '.$pages.' pages', code: 400);
			}
		}

		$request['fieldPlacements'] = $normalised;

		return $request;

	}//end withPlacements()

	/**
	 * The placements and signer names a provider draws, when the request has placements.
	 *
	 * A placement names its signer by position in `signerIds`; the name is the
	 * signer record's display name, else its e-mail address, else its user id.
	 *
	 * @param array<string, mixed>                $request The completing request.
	 * @param array<string, array<string, mixed>> $signers The signer records, keyed by id.
	 *
	 * @return array<string, mixed> `fieldPlacements` and `signerLabels`, or nothing.
	 */
	private function placementContext(array $request, array $signers): array {
		$placements = ($request['fieldPlacements'] ?? []);
		if (is_array($placements) === false || $placements === []) {
			return [];
		}

		$labels = [];
		foreach ((array) ($request['signerIds'] ?? []) as $signerId) {
			$record = ($signers[(string) $signerId] ?? []);
			$labels[] = (string) (($record['displayName'] ?? '') ?: (($record['email'] ?? '') ?: ($record['userId'] ?? '')));
		}

		return ['fieldPlacements' => $placements, 'signerLabels' => $labels];

	}//end placementContext()

	/**
	 * Build the provider's evidence context.
	 *
	 * @param array<string, mixed> $request The completing signing-request array.
	 * @param array<string, mixed>|null $verifiedActor The verified external actor, when portal-originated.
	 *
	 * @return array<string, mixed> The provider context.
	 */
	private function buildContext(array $request, ?array $verifiedActor): array {
		$context = [
			'signer' => $this->resolveSignerLabel(verifiedActor: $verifiedActor),
			'signers' => ($request['signerIds'] ?? []),
			'timestamp' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
			'ip' => $this->request->getRemoteAddress(),
			'level' => (string)($request['signatureLevel'] ?? 'SES'),
		];

		// Guardian consent (signer-identity-rails REQ-DDSIR-010): a signer who
		// signed under the age of consent, and the guardian who acted for them,
		// are recorded in the artifact itself. SigningService computed the basis
		// from the stored signer records; it never comes from request input.
		if (empty($request['consentBasis']) === false && is_array($request['consentBasis']) === true) {
			$context['consentBasis'] = $request['consentBasis'];
		}

		// Identity rails (signer-identity-rails REQ-DDSIR-004): each signer's
		// recorded identity evidence, from the stored signer records.
		if (empty($request['signerEvidence']) === false && is_array($request['signerEvidence']) === true) {
			$context['signerEvidence'] = $request['signerEvidence'];
		}

		// A provider that ran the signer flow itself (LibreSign) hands back
		// the file it signed for this request.
		if ((string)($request['externalId'] ?? '') !== '') {
			$context['externalId'] = (string)$request['externalId'];
		}

		if ($verifiedActor === null) {
			return $context;
		}

		// Portal-signature evidence binding (portal-signing-surface
		// REQ-DDPSS-004): fold the verified assertion's portal subject
		// claims into the provider context so they land inside the SAME
		// MAC as the rest of the assertion. Sourced ONLY from the
		// already-verified actor (never the request body).
		$portalFieldMap = [
			'subjectRef' => 'portalSubjectRef',
			'identityRef' => 'portalIdentityRef',
			'trust' => 'portalTrust',
			'jti' => 'portalJti',
		];

		foreach ($portalFieldMap as $actorKey => $contextKey) {
			if (empty($verifiedActor[$actorKey]) === false) {
				$context[$contextKey] = (string)$verifiedActor[$actorKey];
			}
		}

		return $context;
	}//end buildContext()

	/**
	 * Resolve the document File node for the signing request.
	 *
	 * Resolves through the initiator's user folder (the request owner), falling
	 * back to the current signer's folder — either way a node that is not a
	 * readable/writeable file throws rather than silently skipping the artifact.
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param array<string, mixed> $request The signing-request array.
	 *
	 * @return File The resolved file node.
	 *
	 * @throws RuntimeException When the file cannot be resolved.
	 */
	private function resolveDocumentFile(int $fileId, array $request): File {
		$candidates = [];
		$initiator = (string)($request['initiatorUserId'] ?? '');
		if ($initiator !== '') {
			$candidates[] = $initiator;
		}

		$current = $this->userSession->getUser();
		if ($current !== null) {
			$candidates[] = $current->getUID();
		}

		foreach (array_unique($candidates) as $uid) {
			try {
				$nodes = $this->rootFolder->getUserFolder($uid)->getById($fileId);
			} catch (\Throwable $e) {
				continue;
			}

			foreach ($nodes as $node) {
				if ($node instanceof File) {
					return $node;
				}
			}
		}

		throw new RuntimeException('Cannot resolve the document file to sign: ' . $fileId);
	}//end resolveDocumentFile()

	/**
	 * Resolve a human label for the completing signer.
	 *
	 * @param array<string, mixed>|null $verifiedActor The verified external actor completing
	 *                                                 this act, when portal-originated.
	 *
	 * @return string The signer display name, verified portal email, UID, or 'Unknown'.
	 */
	private function resolveSignerLabel(?array $verifiedActor = null): string {
		if ($verifiedActor !== null) {
			$email = (string)($verifiedActor['email'] ?? '');
			if ($email !== '') {
				return $email;
			}

			return 'External signer';
		}

		$user = $this->userSession->getUser();
		if ($user === null) {
			return 'Unknown';
		}

		// IUser::getDisplayName() is guaranteed non-empty by contract — it
		// falls back to the UID itself when no display name is set.
		return $user->getDisplayName();
	}//end resolveSignerLabel()
}//end class
