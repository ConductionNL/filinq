<?php

/**
 * Unit tests for OfficeSuiteCapabilityService.
 *
 * openspec/changes/office-suite-portability.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service\Office
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Office;

use OCA\Filinq\Service\Office\OfficeSuiteCapabilityService;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Every case here is a way for a suite to look present and serve nothing.
 *
 * The 404 case is not hypothetical: it is what onlyoffice/documentserver returned
 * on 2026-08-16 with `wopi.enable` at its shipped default, while the container
 * reported healthy, `/healthcheck` returned `true` and `/` returned 302.
 */
class OfficeSuiteCapabilityServiceTest extends TestCase {

	/**
	 * Build a service whose client returns the given response.
	 *
	 * @param int    $status The HTTP status.
	 * @param string $body   The response body.
	 *
	 * @return OfficeSuiteCapabilityService The service.
	 */
	private function serviceReturning(int $status, string $body): OfficeSuiteCapabilityService {
		$response = $this->createMock(IResponse::class);
		$response->method('getStatusCode')->willReturn($status);
		$response->method('getBody')->willReturn($body);

		$client = $this->createMock(IClient::class);
		$client->method('get')->willReturn($response);

		$clientService = $this->createMock(IClientService::class);
		$clientService->method('newClient')->willReturn($client);

		return new OfficeSuiteCapabilityService(
			clientService: $clientService,
			logger: $this->createMock(LoggerInterface::class)
		);
	}//end serviceReturning()

	/**
	 * REQ: a genuine CheckFileInfo success resolves available.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md
	 */
	public function testGenuineCheckFileInfoResolvesAvailable(): void {
		$service = $this->serviceReturning(
			status: 200,
			body: json_encode(['BaseFileName' => 'a.docx', 'Size' => 1234])
		);

		$verdict = $service->probe(checkFileInfoUrl: 'http://suite/wopi/files/1');

		$this->assertTrue($verdict['available']);
	}//end testGenuineCheckFileInfoResolvesAvailable()

	/**
	 * REQ: an installed suite with WOPI disabled resolves ABSENT.
	 *
	 * The measured Euro-Office / ONLYOFFICE default. Container healthy, port
	 * answering, admin page green — and 404 on every WOPI path.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md
	 */
	public function testWopiDisabledResolvesAbsent(): void {
		$verdict = $this->serviceReturning(status: 404, body: 'Not Found')
			->probe(checkFileInfoUrl: 'http://suite/wopi/files/1');

		$this->assertFalse($verdict['available']);
		$this->assertStringContainsString('404', $verdict['reason']);
	}//end testWopiDisabledResolvesAbsent()

	/**
	 * REQ: a 2xx with a non-JSON body resolves absent.
	 *
	 * An error page or a login redirect body can carry a 200. Accepting the status
	 * alone would report available for a host that cannot serve a session.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md
	 */
	public function testNonJsonBodyResolvesAbsent(): void {
		$verdict = $this->serviceReturning(status: 200, body: '<html>Sign in</html>')
			->probe(checkFileInfoUrl: 'http://suite/wopi/files/1');

		$this->assertFalse($verdict['available']);
		$this->assertStringContainsString('non-JSON', $verdict['reason']);
	}//end testNonJsonBodyResolvesAbsent()

	/**
	 * REQ: a 2xx JSON body missing a required field resolves absent.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md
	 */
	public function testMissingRequiredFieldResolvesAbsent(): void {
		$verdict = $this->serviceReturning(status: 200, body: json_encode(['Size' => 10]))
			->probe(checkFileInfoUrl: 'http://suite/wopi/files/1');

		$this->assertFalse($verdict['available']);
		$this->assertStringContainsString('BaseFileName', $verdict['reason']);
	}//end testMissingRequiredFieldResolvesAbsent()

	/**
	 * REQ: a transport failure resolves absent rather than propagating.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md
	 */
	public function testTransportFailureResolvesAbsent(): void {
		$client = $this->createMock(IClient::class);
		$client->method('get')->willThrowException(new RuntimeException('connection refused'));

		$clientService = $this->createMock(IClientService::class);
		$clientService->method('newClient')->willReturn($client);

		$service = new OfficeSuiteCapabilityService(
			clientService: $clientService,
			logger: $this->createMock(LoggerInterface::class)
		);

		$verdict = $service->probe(checkFileInfoUrl: 'http://suite/wopi/files/1');

		$this->assertFalse($verdict['available']);
		$this->assertStringContainsString('connection refused', $verdict['reason']);
	}//end testTransportFailureResolvesAbsent()

	/**
	 * REQ: an unconfigured endpoint resolves absent without a request.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md
	 */
	public function testUnconfiguredEndpointResolvesAbsent(): void {
		$clientService = $this->createMock(IClientService::class);
		$clientService->expects($this->never())->method('newClient');

		$service = new OfficeSuiteCapabilityService(
			clientService: $clientService,
			logger: $this->createMock(LoggerInterface::class)
		);

		$this->assertFalse($service->probe(checkFileInfoUrl: '  ')['available']);
	}//end testUnconfiguredEndpointResolvesAbsent()

	/**
	 * A minimal but genuine WOPI discovery document.
	 *
	 * @param string $appName The app name the host claims, or '' for a document
	 *                        that names no app.
	 *
	 * @return string The discovery XML.
	 */
	private function discoveryDocument(string $appName = 'Word'): string {
		$app = ($appName === '') ? '<app>' : sprintf('<app name="%s">', $appName);

		return '<?xml version="1.0" encoding="utf-8"?>'
			. '<wopi-discovery><net-zone name="external-http">'
			. $app
			. '<action name="edit" ext="docx" urlsrc="http://suite/we/"/>'
			. '</app></net-zone></wopi-discovery>';
	}//end discoveryDocument()

	/**
	 * REQ: a served WOPI discovery document resolves AVAILABLE.
	 *
	 * Discovery, not CheckFileInfo, is what can be probed without a file and a
	 * token. This is the endpoint that flipped 404 -> 200 on ONLYOFFICE once
	 * `WOPI_ENABLED=true` was set.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md#requirement-wopi-availability-must-be-probed-never-inferred-from-installation
	 */
	public function testServedDiscoveryResolvesAvailable(): void {
		$verdict = $this->serviceReturning(status: 200, body: $this->discoveryDocument())
			->probeDiscovery(discoveryUrl: 'http://suite/hosting/discovery');

		$this->assertTrue($verdict['available']);
		$this->assertSame('WOPI discovery served', $verdict['reason']);
		$this->assertSame('Word', $verdict['suite']);
	}//end testServedDiscoveryResolvesAvailable()

	/**
	 * REQ: a discovery document that names no app is still available, unnamed.
	 *
	 * The suite name is REPORTING only — nothing branches on it — so a host that
	 * declines to identify itself must still resolve available rather than being
	 * failed for a field the probe does not need.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md#requirement-a-suite-must-not-be-claimed-as-supported-until-it-has-been-run
	 */
	public function testDiscoveryWithoutAppNameIsAvailableButUnnamed(): void {
		$verdict = $this->serviceReturning(status: 200, body: $this->discoveryDocument(appName: ''))
			->probeDiscovery(discoveryUrl: 'http://suite/hosting/discovery');

		$this->assertTrue($verdict['available']);
		$this->assertNull($verdict['suite']);
	}//end testDiscoveryWithoutAppNameIsAvailableButUnnamed()

	/**
	 * REQ: a 404 on discovery resolves ABSENT, naming the status.
	 *
	 * The measured ONLYOFFICE default on 2026-08-16: container healthy,
	 * `/healthcheck` true, `/` 302, `/hosting/discovery` 404.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md#requirement-wopi-availability-must-be-probed-never-inferred-from-installation
	 */
	public function testDiscoveryNotFoundResolvesAbsent(): void {
		$verdict = $this->serviceReturning(status: 404, body: 'Not Found')
			->probeDiscovery(discoveryUrl: 'http://suite/hosting/discovery');

		$this->assertFalse($verdict['available']);
		$this->assertStringContainsString('404', $verdict['reason']);
	}//end testDiscoveryNotFoundResolvesAbsent()

	/**
	 * REQ: a 200 that is not a discovery document resolves ABSENT.
	 *
	 * A login form and an error page both carry a 200. Accepting the status alone
	 * would report those hosts as usable.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md#requirement-wopi-availability-must-be-probed-never-inferred-from-installation
	 */
	public function testTwoHundredThatIsNotDiscoveryResolvesAbsent(): void {
		$verdict = $this->serviceReturning(status: 200, body: '<html>Sign in</html>')
			->probeDiscovery(discoveryUrl: 'http://suite/hosting/discovery');

		$this->assertFalse($verdict['available']);
		$this->assertStringContainsString('not a WOPI discovery document', $verdict['reason']);
	}//end testTwoHundredThatIsNotDiscoveryResolvesAbsent()

	/**
	 * REQ: a discovery document declaring no action resolves ABSENT.
	 *
	 * A host can serve the envelope while supporting nothing. The envelope is not
	 * the capability.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md#requirement-wopi-availability-must-be-probed-never-inferred-from-installation
	 */
	public function testDiscoveryWithoutActionsResolvesAbsent(): void {
		$verdict = $this->serviceReturning(
			status: 200,
			body: '<wopi-discovery><net-zone name="external-http"/></wopi-discovery>'
		)->probeDiscovery(discoveryUrl: 'http://suite/hosting/discovery');

		$this->assertFalse($verdict['available']);
		$this->assertStringContainsString('declares no actions', $verdict['reason']);
	}//end testDiscoveryWithoutActionsResolvesAbsent()

	/**
	 * REQ: a transport failure on discovery resolves absent rather than propagating.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md#requirement-wopi-availability-must-be-probed-never-inferred-from-installation
	 */
	public function testDiscoveryTransportFailureResolvesAbsent(): void {
		$client = $this->createMock(IClient::class);
		$client->method('get')->willThrowException(new RuntimeException('connection refused'));

		$clientService = $this->createMock(IClientService::class);
		$clientService->method('newClient')->willReturn($client);

		$verdict = (new OfficeSuiteCapabilityService(
			clientService: $clientService,
			logger: $this->createMock(LoggerInterface::class)
		))->probeDiscovery(discoveryUrl: 'http://suite/hosting/discovery');

		$this->assertFalse($verdict['available']);
		$this->assertStringContainsString('connection refused', $verdict['reason']);
	}//end testDiscoveryTransportFailureResolvesAbsent()

	/**
	 * REQ: an unconfigured discovery URL resolves absent WITHOUT a request.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md#requirement-wopi-availability-must-be-probed-never-inferred-from-installation
	 */
	public function testUnconfiguredDiscoveryUrlResolvesAbsentWithoutRequest(): void {
		$clientService = $this->createMock(IClientService::class);
		$clientService->expects($this->never())->method('newClient');

		$verdict = (new OfficeSuiteCapabilityService(
			clientService: $clientService,
			logger: $this->createMock(LoggerInterface::class)
		))->probeDiscovery(discoveryUrl: '  ');

		$this->assertFalse($verdict['available']);
		$this->assertStringContainsString('no WOPI endpoint configured', $verdict['reason']);
	}//end testUnconfiguredDiscoveryUrlResolvesAbsentWithoutRequest()
}//end class
