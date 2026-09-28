<?php

/**
 * Signer Auth Context
 *
 * The signing act an authentication is started for.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SignerAuth
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

namespace OCA\Filinq\Service\SignerAuth;

/**
 * Binds an authentication to one signer on one request.
 *
 * A provider that redirects binds its `state` to both ids, so evidence
 * gathered for one signing act can never be replayed onto another.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
final class SignerAuthContext {

	/**
	 * Constructor.
	 *
	 * @param string $requestId The signing request id.
	 * @param string $signerId The signer record id.
	 * @param string $requiredAssurance The assurance the act needs.
	 * @param string $userId The Nextcloud user starting the authentication, '' for none.
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $requestId,
		public readonly string $signerId,
		public readonly string $requiredAssurance,
		public readonly string $userId = '',
	) {

	}//end __construct()
}//end class
