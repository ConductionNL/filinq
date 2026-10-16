<?php

/**
 * Unit tests for SignerIdentitySettingsController
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
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

namespace OCA\Filinq\Tests\Unit\Controller;

require_once __DIR__ . '/../Service/SignerAuth/OidcBrokerHarness.php';

use OCA\Filinq\Controller\SignerIdentitySettingsController;
use OCA\Filinq\Settings\FilinqAdmin;
use OCA\Filinq\Tests\Unit\Service\SignerAuth\OidcBrokerHarness;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * The admin panel's endpoint: admin-only, reference-only (REQ-DDSIR-005).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SignerIdentitySettingsControllerTest extends TestCase {
	use OidcBrokerHarness;

	/**
	 * The controller over the in-memory settings, with the given request params.
	 *
	 * @param array<string, mixed> $params The request params.
	 *
	 * @return SignerIdentitySettingsController
	 */
	private function controller(array $params = []): SignerIdentitySettingsController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn($params);

		return new SignerIdentitySettingsController(request: $request, settings: $this->settings());

	}//end controller()

	/**
	 * Both endpoints are gated as admin settings.
	 *
	 * @return void
	 */
	public function testBothEndpointsAreAdminSettings(): void {
		foreach (['index', 'update'] as $method) {
			$attributes = (new ReflectionMethod(SignerIdentitySettingsController::class, $method))->getAttributes(AuthorizedAdminSetting::class);
			$this->assertCount(1, $attributes, $method . ' must be an admin setting');
			$this->assertSame(FilinqAdmin::class, $attributes[0]->getArguments()[0] ?? $attributes[0]->getArguments()['settings'] ?? null);
		}

	}//end testBothEndpointsAreAdminSettings()

	/**
	 * The panel reads defaults: the session provider, 15 minutes, no guardian raise.
	 *
	 * @return void
	 */
	public function testTheDefaultsChangeNothing(): void {
		$data = $this->controller()->index()->getData();

		$this->assertSame('nextcloud-session', $data['provider']);
		$this->assertSame(15, $data['evidenceMaxAgeMinutes']);
		$this->assertSame('low', $data['guardianMinimumAssurance']);
		$this->assertSame('', $data['credentialRef']);
		$this->assertArrayHasKey('acrMapping', $data);

	}//end testTheDefaultsChangeNothing()

	/**
	 * A valid update is stored and echoed back.
	 *
	 * @return void
	 */
	public function testAValidUpdateIsStored(): void {
		$response = $this->controller(
			[
				'provider' => 'oidc-broker',
				'issuer' => 'https://broker.example.nl',
				'clientId' => 'filinq-client',
				'authorizationEndpoint' => 'https://broker.example.nl/authorize',
				'tokenEndpoint' => 'https://broker.example.nl/token',
				'redirectUri' => 'https://cloud.example.nl/apps/filinq/api/signing/identity/callback',
				'credentialRef' => $this->credentialRef,
				'evidenceMaxAgeMinutes' => 10,
				'guardianMinimumAssurance' => 'substantial',
			]
		)->update();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame('oidc-broker', $response->getData()['provider']);
		$this->assertSame('600', (string)((int)$this->appConfig['signer_auth_evidence_max_age_minutes'] * 60));
		$this->assertSame('substantial', $this->appConfig['signer_auth_guardian_min_assurance']);

	}//end testAValidUpdateIsStored()

	/**
	 * An invalid update answers 400 and stores nothing of it.
	 *
	 * @return void
	 */
	public function testAnInvalidUpdateAnswers400AndStoresNothing(): void {
		foreach (
			[
				['provider' => 'made-up'],
				['tokenEndpoint' => 'http://broker.example.nl/token'],
				['credentialRef' => 'not-a-reference'],
				['guardianMinimumAssurance' => 'extreme'],
				['evidenceMaxAgeMinutes' => 0],
				['acrMapping' => '{not json'],
			] as $params
		) {
			$this->appConfig = [];
			$response = $this->controller($params)->update();

			$this->assertSame(400, $response->getStatus(), json_encode($params));
			$this->assertSame([], $this->appConfig, json_encode($params));
		}

	}//end testAnInvalidUpdateAnswers400AndStoresNothing()
}//end class
