<?php

/**
 * Broker Credential Resolver
 *
 * Resolves a `credentialRef` to the secret behind it, through OpenRegister's
 * credential broker, at the moment the secret is needed.
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

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * ADR-064 custody: filinq holds the reference, OpenRegister holds the secret.
 *
 * An identity broker is an arbitrary, self-hosted host that the broker's
 * host-locked proxy cannot serve, so this is the ADR's documented exception:
 * `CredentialBrokerService::resolveInjectable()` for a credential minted on
 * an `inject_only` provider. The secret enters the process for the one token
 * request and is never stored, logged or returned. The broker class is looked
 * up by name, the way the fleet's other consumers do, so filinq keeps working
 * (with the broker provider unusable) on an instance without it.
 *
 * The design first named the `document-waarmerk-certification` resolver as
 * the seam to reuse; that change is unbuilt, and ADR-064 forbids an app its
 * own broker, so this goes to OpenRegister directly.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class BrokerCredentialResolver {

	/**
	 * OpenRegister's credential broker.
	 *
	 * @var string
	 */
	public const BROKER_CLASS = 'OCA\\OpenRegister\\Service\\Credential\\CredentialBrokerService';

	/**
	 * The app id filinq presents to the broker (its Guard 2 allow-list).
	 *
	 * @var string
	 */
	public const APP_ID = 'filinq';

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container The server container the broker is resolved from.
	 * @param LoggerInterface $logger Logs which step failed, never the secret.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The secret behind a credential reference.
	 *
	 * @param string $credentialRef The credential object's UUID in OpenRegister.
	 *
	 * @return string The secret, for immediate use only.
	 *
	 * @throws RuntimeException When there is no reference, no broker, or the broker gives no secret.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function resolve(string $credentialRef): string {
		if ($credentialRef === '') {
			throw new RuntimeException('No credential reference is configured for the identity broker');
		}

		$broker = $this->broker();

		try {
			$secret = $broker->resolveInjectable($credentialRef, self::APP_ID);
		} catch (Throwable $e) {
			$this->logger->warning(
				'Filinq: the credential broker refused the identity broker credential',
				['credentialRef' => $credentialRef, 'reason' => get_class($e)]
			);
			throw new RuntimeException('The credential broker refused the identity broker credential');
		}

		if (is_string($secret) === false || $secret === '') {
			throw new RuntimeException('The identity broker credential is not an injectable secret');
		}

		return $secret;

	}//end resolve()

	/**
	 * The broker service, or a refusal when it is not installed.
	 *
	 * @return object The broker.
	 *
	 * @throws RuntimeException When OpenRegister's broker is not available.
	 */
	private function broker(): object {
		try {
			if ($this->container->has(self::BROKER_CLASS) === true) {
				$broker = $this->container->get(self::BROKER_CLASS);
				if (is_object($broker) === true && method_exists($broker, 'resolveInjectable') === true) {
					return $broker;
				}
			}
		} catch (Throwable $e) {
			$this->logger->warning('Filinq: OpenRegister credential broker could not be loaded', ['reason' => get_class($e)]);
		}

		throw new RuntimeException('OpenRegister\'s credential broker is not available');

	}//end broker()
}//end class
