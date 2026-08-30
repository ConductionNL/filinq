<?php

/**
 * Signature Assertion Canonicalizer
 *
 * Shared canonical-JSON encoding used by both the v2 assertion WRITER
 * (NativeSigningProvider::produceSignedArtifact) and the VERIFIER
 * (SigningVerificationService::verifyAssertion) so the two independently
 * agree on the exact bytes the MAC covers (signing-trust-rebuild REQ-DDSTR-001).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Signing
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

namespace OCA\Filinq\Service\Signing;

/**
 * Deterministic (sorted-key) JSON encoding of an assertion payload.
 *
 * The v2 MAC formula is `HMAC-SHA256(secret, sha256(canonical-document) . "\n"
 * . canonical-JSON(assertion-minus-mac))`. Both the writer and the verifier
 * must independently produce byte-identical JSON for the same logical data —
 * this class is the single source of truth for that encoding so the two
 * sides can never drift.
 *
 * Stateless but deliberately NOT static: the writer
 * ({@see \OCA\Filinq\Service\Signing\NativeSigningProvider}) and the verifier
 * ({@see \OCA\Filinq\Service\SigningVerificationService}) take it as an
 * injected collaborator, so the encoding is a substitutable dependency rather
 * than a hard-wired static call.
 *
 * @spec openspec/specs/document-signing/spec.md
 */
final class AssertionCanonicalizer {
	/**
	 * Canonical-JSON encode an assertion payload with recursively sorted keys.
	 *
	 * @param array<string, mixed> $data The assertion data (already excluding `mac`).
	 *
	 * @return string The canonical JSON string.
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 */
	public function canonicalJson(array $data): string {
		$sorted = $this->sortRecursive(data: $data);

		return (string)json_encode($sorted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}//end canonicalJson()

	/**
	 * Recursively sort array keys so nested maps encode deterministically.
	 *
	 * @param array<mixed, mixed> $data The data to sort.
	 *
	 * @return array<mixed, mixed> The recursively key-sorted data.
	 */
	private function sortRecursive(array $data): array {
		foreach ($data as $key => $value) {
			if (is_array($value) === true) {
				$data[$key] = $this->sortRecursive(data: $value);
			}
		}

		ksort($data);

		return $data;
	}//end sortRecursive()
}//end class
