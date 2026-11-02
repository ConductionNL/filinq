<?php

/**
 * Unit tests for SignerAuthProviderFactory
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SignerAuth;

require_once __DIR__ . '/OidcBrokerHarness.php';

use OCA\Filinq\Service\SignerAuth\NextcloudSessionProvider;
use OCA\Filinq\Service\SignerAuth\SignerAuthProviderFactory;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Strict provider resolution (REQ-DDSIR-001): an unknown provider fails loudly.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SignerAuthProviderFactoryTest extends TestCase {
	use OidcBrokerHarness;

	/**
	 * The factory over the two shipped providers.
	 *
	 * @return SignerAuthProviderFactory
	 */
	private function factory(): SignerAuthProviderFactory {
		return new SignerAuthProviderFactory(
			settings: $this->settings(),
			sessionProvider: new NextcloudSessionProvider(
				userSession: $this->createMock(IUserSession::class),
				pseudonymiser: $this->pseudonymiser()
			),
			brokerProvider: $this->broker()
		);

	}//end factory()

	/**
	 * With nothing configured the Nextcloud session is the provider: no behaviour change.
	 *
	 * @return void
	 */
	public function testTheSessionProviderIsTheDefault(): void {
		$this->assertSame('nextcloud-session', $this->factory()->configured()->getIdentifier());

	}//end testTheSessionProviderIsTheDefault()

	/**
	 * The broker resolves by its identifier.
	 *
	 * @return void
	 */
	public function testTheBrokerResolvesByItsIdentifier(): void {
		$this->appConfig['signer_auth_provider'] = 'oidc-broker';

		$this->assertSame('oidc-broker', $this->factory()->configured()->getIdentifier());
		$this->assertSame(['nextcloud-session', 'oidc-broker'], $this->factory()->identifiers());

	}//end testTheBrokerResolvesByItsIdentifier()

	/**
	 * An unknown configured provider throws, naming it, and nothing stands in for it.
	 *
	 * @return void
	 */
	public function testAnUnknownConfiguredProviderFailsLoudly(): void {
		$this->appConfig['signer_auth_provider'] = 'signicat-direct';

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('signicat-direct');

		$this->factory()->configured();

	}//end testAnUnknownConfiguredProviderFailsLoudly()

	/**
	 * Asking for an unregistered identifier throws too.
	 *
	 * @return void
	 */
	public function testAnUnknownIdentifierFailsLoudly(): void {
		$factory = $this->factory();

		$this->assertFalse($factory->has('eudi-wallet'));
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('eudi-wallet');

		$factory->get('eudi-wallet');

	}//end testAnUnknownIdentifierFailsLoudly()
}//end class
