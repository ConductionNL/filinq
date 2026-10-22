<?php

/**
 * Auth Challenge
 *
 * What the signer must do next to establish their identity.
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
 * A redirect to an identity broker, or nothing to do.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
final class AuthChallenge {

	/**
	 * The signer is sent to a URL and comes back through the callback.
	 *
	 * @var string
	 */
	public const TYPE_REDIRECT = 'redirect';

	/**
	 * The signer is already authenticated; nothing to do.
	 *
	 * @var string
	 */
	public const TYPE_NONE = 'none';

	/**
	 * Constructor.
	 *
	 * @param string $provider The provider identifier that issued the challenge.
	 * @param string $type `redirect` or `none`.
	 * @param string $url The URL to send the signer to, '' for `none`.
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $provider,
		public readonly string $type,
		public readonly string $url = '',
	) {

	}//end __construct()

	/**
	 * The challenge as the step-up endpoint returns it.
	 *
	 * The OIDC `state` and `nonce` travel inside the URL and stay in the
	 * server-side session; they are not repeated here.
	 *
	 * @return array{provider: string, type: string, url: string}
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function toArray(): array {
		return ['provider' => $this->provider, 'type' => $this->type, 'url' => $this->url];

	}//end toArray()
}//end class
