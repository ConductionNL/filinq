<?php

/**
 * Unit tests for OcsLibreSignClient
 *
 * The routes and payloads are LibreSign's own, read from its openapi.json
 * (LibreSign/libresign main, 16.0.0-dev, 29 Sep 2026).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/libresign-signing-provider/specs/libresign-signing-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Service\Signing\OcsLibreSignClient;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IAppConfig;
use OCP\IURLGenerator;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * The HTTP half of the LibreSign seam.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class OcsLibreSignClientTest extends TestCase {

	/**
	 * Every request the fake HTTP client saw: [method, url, options].
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $sent = [];

	/**
	 * The client, answering every request with the given body.
	 *
	 * @param string $body     The response body
	 * @param string $uid      The service account
	 * @param string $password The service account's app password
	 *
	 * @return OcsLibreSignClient
	 */
	private function client(string $body, string $uid = 'signbot', string $password = 'app-pass'): OcsLibreSignClient {
		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn($body);

		$http = $this->createMock(IClient::class);
		foreach (['get', 'post', 'delete'] as $verb) {
			$http->method($verb)->willReturnCallback(
				function (string $url, array $options) use ($verb, $response): IResponse {
					$this->sent[] = [$verb, $url, $options];
					return $response;
				}
			);
		}

		$service = $this->createMock(IClientService::class);
		$service->method('newClient')->willReturn($http);

		$urls = $this->createMock(IURLGenerator::class);
		$urls->method('getAbsoluteURL')->willReturnCallback(static fn (string $path): string => 'https://nc.example' . $path);

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => [
				'libresign_service_uid' => $uid,
				'libresign_service_app_password' => $password,
			][$key] ?? $default
		);

		return new OcsLibreSignClient(clientService: $service, urlGenerator: $urls, config: $config, logger: new NullLogger());

	}//end client()

	/**
	 * The request goes to LibreSign's request-signature route with its NewFile and NewSigner shapes.
	 *
	 * @return void
	 */
	public function testRequestSignatureUsesLibreSignsRoute(): void {
		$data = $this->client('{"ocs":{"meta":{"status":"ok"},"data":{"uuid":"ls-1","status":1}}}')->requestSignature(
			file: ['nodeId' => 12],
			name: 'Besluit',
			signers: [['identifyMethods' => [['method' => 'email', 'value' => 'a@example.org', 'requirement' => 'required']]]]
		);

		$this->assertSame('ls-1', $data['uuid']);
		[$verb, $url, $options] = $this->sent[0];
		$this->assertSame('post', $verb);
		$this->assertSame('https://nc.example/ocs/v2.php/apps/libresign/api/v1/request-signature', $url);
		$this->assertSame(['nodeId' => 12], $options['json']['file']);
		$this->assertSame('Besluit', $options['json']['name']);
		$this->assertSame(1, $options['json']['status']);
		$this->assertSame('a@example.org', $options['json']['signers'][0]['identifyMethods'][0]['value']);
		$this->assertSame(['signbot', 'app-pass'], $options['auth']);
		$this->assertSame('true', $options['headers']['OCS-APIRequest']);
		$this->assertTrue($options['nextcloud']['allow_local_address']);

	}//end testRequestSignatureUsesLibreSignsRoute()

	/**
	 * Validate, download and delete use LibreSign's routes.
	 *
	 * @return void
	 */
	public function testTheOtherThreeRoutes(): void {
		$client = $this->client('{"ocs":{"meta":{"status":"ok"},"data":{"uuid":"ls 1","status":3,"nodeId":77}}}');
		$this->assertSame(3, $client->validate(uuid: 'ls 1')['status']);
		$client->deleteRequest(nodeId: 77);
		$client->downloadSigned(uuid: 'ls 1');

		$this->assertSame(
			[
				['get', 'https://nc.example/ocs/v2.php/apps/libresign/api/v1/file/validate/uuid/ls%201'],
				['delete', 'https://nc.example/ocs/v2.php/apps/libresign/api/v1/sign/file_id/77'],
				['get', 'https://nc.example/index.php/apps/libresign/p/pdf/ls%201'],
			],
			array_map(static fn (array $call): array => [$call[0], $call[1]], $this->sent)
		);

	}//end testTheOtherThreeRoutes()

	/**
	 * Without service account credentials nothing is sent.
	 *
	 * @return void
	 */
	public function testNoCredentialsIsAClearError(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('libresign_service_uid');
		try {
			$this->client('{}', uid: '')->validate(uuid: 'ls-1');
		} finally {
			$this->assertSame([], $this->sent);
		}

	}//end testNoCredentialsIsAClearError()

	/**
	 * An answer without an OCS data envelope is an error, not an empty success.
	 *
	 * @return void
	 */
	public function testAnAnswerWithoutOcsDataIsAnError(): void {
		$this->expectException(RuntimeException::class);
		$this->client('<html>login</html>')->validate(uuid: 'ls-1');

	}//end testAnAnswerWithoutOcsDataIsAnError()
}//end class
