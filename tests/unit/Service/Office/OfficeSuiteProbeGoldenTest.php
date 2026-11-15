<?php

/**
 * Golden test for the two WOPI probes of OfficeSuiteCapabilityService.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Office
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/office-suite-portability/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Office;

use OCA\Filinq\Service\Office\OfficeSuiteCapabilityService;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;

/**
 * Every answer through probe() and probeDiscovery(), compared with JSON
 * recorded from the implementation before the shared fetch was split out.
 *
 * @covers \OCA\Filinq\Service\Office\OfficeSuiteCapabilityService
 */
class OfficeSuiteProbeGoldenTest extends TestCase {

	/**
	 * The probe, the URL, and the answer: [status, body], or 'throws'.
	 *
	 * @return array<string, array{0: string, 1: string, 2: mixed}>
	 */
	public static function scenarios(): array {
		$info = 'https://office.example.nl/wopi/files/1';
		$disc = 'https://office.example.nl/hosting/discovery';
		$xml = '<wopi-discovery><net-zone><app name="Euro-Office"><action name="edit"/></app></net-zone></wopi-discovery>';
		$cases = [
			'info: a full answer' => ['probe', $info, [200, '{"BaseFileName":"a.docx","Size":3,"BreadcrumbBrandName":"Collabora"}']],
			'info: a host edit url' => ['probe', $info, [200, '{"BaseFileName":"a.docx","Size":3,"HostEditUrl":"https://x"}']],
			'info: no brand' => ['probe', $info, [204, '{"BaseFileName":"a.docx","Size":0,"BreadcrumbBrandName":""}']],
			'info: no size' => ['probe', $info, [200, '{"BaseFileName":"a.docx"}']],
			'info: no name' => ['probe', $info, [200, '{"Size":3}']],
			'info: a null field' => ['probe', $info, [200, '{"BaseFileName":null,"Size":null}']],
			'info: not json' => ['probe', $info, [200, '<html>login</html>']],
			'info: a json scalar' => ['probe', $info, [200, '7']],
			'info: 404' => ['probe', $info, [404, '']],
			'info: 199' => ['probe', $info, [199, '{}']],
			'info: 300' => ['probe', $info, [300, '{}']],
			'info: 299' => ['probe', $info, [299, '{"BaseFileName":"a","Size":1}']],
			'info: refused' => ['probe', $info, 'throws'],
			'info: blank url' => ['probe', '  ', [200, '{}']],
			'discovery: served' => ['probeDiscovery', $disc, [200, $xml]],
			'discovery: no app name' => ['probeDiscovery', $disc, [200, '<wopi-discovery><action name="view"/></wopi-discovery>']],
			'discovery: no actions' => ['probeDiscovery', $disc, [200, '<wopi-discovery></wopi-discovery>']],
			'discovery: an error page' => ['probeDiscovery', $disc, [200, '<html>error</html>']],
			'discovery: 404' => ['probeDiscovery', $disc, [404, 'not found']],
			'discovery: 302' => ['probeDiscovery', $disc, [302, $xml]],
			'discovery: 200 edge' => ['probeDiscovery', $disc, [200, '<wopi-discovery><action']],
			'discovery: refused' => ['probeDiscovery', $disc, 'throws'],
			'discovery: blank url' => ['probeDiscovery', '', [200, $xml]],
			'discovery: a whitespace url' => ['probeDiscovery', '   ', [200, $xml]],
		];
		return $cases;
	}//end scenarios()

	/**
	 * The verdict, the request and the log match the recorded ones.
	 *
	 * @param string $method The probe.
	 * @param string $url The URL.
	 * @param mixed $answer The answer.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testVerdict(string $method, string $url, mixed $answer): void {
		$logger = new class extends AbstractLogger {
			/**
			 * @var array<int, array<int, mixed>>
			 */
			public array $lines = [];

			/**
			 * Record one line.
			 *
			 * @param mixed $level The level.
			 * @param string|Stringable $message The message.
			 * @param array<mixed> $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|Stringable $message, array $context = []): void {
				$this->lines[] = [$level, (string)$message, $context];
			}
		};

		$requests = [];
		$client = $this->createMock(IClient::class);
		$client->method('get')->willReturnCallback(function (string $uri, array $options) use (&$requests, $answer): IResponse {
			$requests[] = [$uri, $options];
			if ($answer === 'throws') {
				throw new RuntimeException('connection refused');
			}

			$response = $this->createMock(IResponse::class);
			$response->method('getStatusCode')->willReturn($answer[0]);
			$response->method('getBody')->willReturn($answer[1]);
			return $response;
		});
		$clients = $this->createMock(IClientService::class);
		$clients->method('newClient')->willReturn($client);

		$service = new OfficeSuiteCapabilityService($clients, $logger);
		$trace = ['verdict' => $service->$method($url), 'requests' => $requests, 'logs' => $logger->lines];

		$path = __DIR__ . '/golden/office-suite-probe.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);
	}//end testVerdict()
}//end class
