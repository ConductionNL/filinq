<?php

/**
 * Signing Request Validator
 *
 * Validates a signing request's data at creation time, before anything is
 * persisted. Extracted verbatim from SigningService, which had grown past the
 * class-length threshold.
 *
 * Provider/level honesty at request creation (signing-trust-rebuild
 * REQ-DDSTR-002 point 1): an unknown provider, or a provider that does not
 * support the requested signature level, is rejected with HTTP 400 before
 * anything is persisted — so the completion path (REQ-DDSTR-002 point 2)
 * never has to silently substitute a provider or level, because an invalid
 * pair can never be created in the first place.
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
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Service\Signing\FieldPlacementCheck;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use RuntimeException;

/**
 * Validates signing request data and the provider/level pair.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-signing/spec.md
 */
class SigningRequestValidator {
	/**
	 * Constructor.
	 *
	 * @param SigningProviderFactory $providerFactory Provider factory (strict resolution).
	 * @param FieldPlacementCheck    $placementCheck  The field placement rules and page check.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SigningProviderFactory $providerFactory,
		private readonly FieldPlacementCheck $placementCheck = new FieldPlacementCheck(),
	) {

	}//end __construct()

	/**
	 * Validate signing request data.
	 *
	 * @param array<string, mixed> $data The request data.
	 *
	 * @return void
	 *
	 * @throws RuntimeException If validation fails.
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 */
	public function validateRequestData(array $data): void {
		if (empty($data['documentFileId']) === true) {
			throw new RuntimeException('Document file ID is required', 400);
		}

		if (empty($data['documentName']) === true) {
			throw new RuntimeException('Document name is required', 400);
		}

		if (in_array($data['signatureLevel'] ?? '', ['SES', 'AdES', 'QES'], true) === false) {
			throw new RuntimeException('Invalid signature level', 400);
		}

		if (in_array($data['signingMode'] ?? '', ['sequential', 'parallel'], true) === false) {
			throw new RuntimeException('Invalid signing mode', 400);
		}

	}//end validateRequestData()

	/**
	 * Validate that a request names at least one signer who can be reached.
	 *
	 * A signer is reachable through a Nextcloud user id or an e-mail address;
	 * a name alone reaches nobody. Every entry must be reachable, and there
	 * must be at least one, so a request can never be stored as PENDING with
	 * nobody able to sign it (issue #1209, REQ-SAO-001).
	 *
	 * @param array<int|string, mixed> $signers The `signers` entries of the request.
	 *
	 * @return void
	 *
	 * @throws RuntimeException With code 400 when no signer, or an unreachable one, is given.
	 *
	 * @spec openspec/changes/signing-accept-only-recipient/specs/signing-accept-only/spec.md
	 */
	public function validateSigners(array $signers): void {
		if ($signers === []) {
			throw new RuntimeException('A signing request needs at least one signer', 400);
		}

		foreach ($signers as $signer) {
			$signer = (array) $signer;
			$userId = trim((string) ($signer['userId'] ?? ''));
			$email  = trim((string) ($signer['email'] ?? ''));
			if ($userId === '' && $email === '') {
				throw new RuntimeException('Every signer needs a user or an e-mail address', 400);
			}
		}

	}//end validateSigners()

	/**
	 * Validate that the requested provider actually supports the requested level.
	 *
	 * Provider/level honesty at request creation (signing-trust-rebuild
	 * REQ-DDSTR-002 point 1): an unknown provider, or a provider that does not
	 * support the requested signature level (via
	 * `SigningProviderInterface::supportsLevel()`), is rejected with HTTP 400
	 * before anything is persisted — the completion path (REQ-DDSTR-002 point
	 * 2) never has to silently substitute a provider or level because an
	 * invalid pair can never be created in the first place.
	 *
	 * @param string $provider The requested provider identifier.
	 * @param string $level The requested signature level.
	 *
	 * @return void
	 *
	 * @throws RuntimeException With HTTP code 400 when the provider is unknown
	 *                          or does not support the requested level.
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 */
	public function validateProviderLevelPair(string $provider, string $level): void {
		try {
			$providerInstance = $this->providerFactory->getProvider(identifier: $provider);
		} catch (\Throwable $e) {
			throw new RuntimeException('Unknown signing provider: ' . $provider, 400);
		}

		if ($providerInstance->supportsLevel(level: $level) === false) {
			throw new RuntimeException(
				'Signing provider "' . $provider . '" does not support signature level "' . $level . '"',
				400
			);
		}

	}//end validateProviderLevelPair()

	/**
	 * Check a new request's field placements and put them on the request.
	 *
	 * @param array<string, mixed>   $request     The request about to be stored.
	 * @param mixed                  $placements  The `fieldPlacements` the caller sent.
	 * @param int                    $signerCount How many signers the request names.
	 * @param SignedArtifactProducer $producer    Reads the document's bytes, only when there are placements.
	 *
	 * @return array<string, mixed> The request, with `fieldPlacements` when there are any.
	 *
	 * @throws RuntimeException 400 when a placement breaks a rule, names a page the document lacks, or the provider cannot carry placements.
	 *
	 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.1
	 */
	public function withPlacements(array $request, mixed $placements, int $signerCount, SignedArtifactProducer $producer): array {
		return $this->placementCheck->apply(request: $request, placements: $placements, signerCount: $signerCount, producer: $producer);

	}//end withPlacements()
}//end class
