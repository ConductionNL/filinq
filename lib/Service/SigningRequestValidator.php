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
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCA\DocuDesk\Service\Signing\SigningProviderFactory;
use RuntimeException;

/**
 * Validates signing request data and the provider/level pair.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/document-signing/spec.md
 */
class SigningRequestValidator {
	/**
	 * Constructor.
	 *
	 * @param SigningProviderFactory $providerFactory Provider factory (strict resolution).
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SigningProviderFactory $providerFactory,
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
}//end class
