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
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/document-signing/spec.md
 * @spec openspec/specs/portal-signing-surface/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\DocuDesk\Service\Signing\SigningProviderFactory;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUserSession;
use RuntimeException;

/**
 * Produces and stores the signed artifact for a completing signing request.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
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
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SigningProviderFactory $providerFactory,
		private readonly IUserSession $userSession,
		private readonly IRequest $request,
		private readonly IRootFolder $rootFolder,
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
	 *
	 * @return string The stored signed-artifact reference (file id + version).
	 *
	 * @throws RuntimeException When no verifiable artifact can be produced/stored,
	 *                          or when the request names an unregistered provider.
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 * @spec openspec/specs/portal-signing-surface/spec.md
	 */
	public function produce(array $request, ?array $verifiedActor = null): string {
		$fileId = (int)($request['documentFileId'] ?? 0);
		if ($fileId <= 0) {
			throw new RuntimeException('Cannot produce a signed artifact: the request has no document file id');
		}

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
