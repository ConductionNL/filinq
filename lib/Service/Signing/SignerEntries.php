<?php

/**
 * Signer Entries
 *
 * The signer entries of a new signing request with their guardian preparation.
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
 * The signer entries as the consumer sent them, the guardian fields per entry and the guardian-to-signer links.
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class SignerEntries {

	/**
	 * The guardian fields per entry.
	 *
	 * @var array<int|string, array<string, mixed>>
	 */
	public readonly array $fields;

	/**
	 * The guardian-to-signer links: guardian entry key => signer entry key.
	 *
	 * @var array<int|string, int|string>
	 */
	public readonly array $links;

	/**
	 * @param array<int|string, mixed> $entries  The signer entries as the consumer sent them
	 * @param array                    $prepared What GuardianConsentGuard::prepareSigners() returned: fields and links
	 */
	public function __construct(
		public readonly array $entries,
		array $prepared,
	) {
		$this->fields = $prepared['fields'];
		$this->links  = $prepared['links'];
	}//end __construct()
}//end class
