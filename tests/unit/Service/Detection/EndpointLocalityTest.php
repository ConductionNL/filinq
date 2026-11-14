<?php

/**
 * Tests for EndpointLocality: only loopback and private hosts are local.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Detection
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Detection;

use OCA\Filinq\Service\Detection\EndpointLocality;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\Detection\EndpointLocality
 */
class EndpointLocalityTest extends TestCase {

	/**
	 * A locality whose name resolution is fixed.
	 *
	 * @param array<string, list<string>> $dns Host name to addresses.
	 *
	 * @return EndpointLocality The locality.
	 */
	private function locality(array $dns=[]): EndpointLocality {
		return new class($dns) extends EndpointLocality {
			/**
			 * @param array<string, list<string>> $dns Host name to addresses.
			 */
			public function __construct(private array $dns) {
			}

			/**
			 * @param string $host The host.
			 *
			 * @return list<string> Its fixed addresses.
			 */
			protected function resolve(string $host): array {
				return ($this->dns[$host] ?? []);
			}
		};
	}

	/**
	 * Loopback and private literals are local; public, link-local and empty hosts are not.
	 *
	 * @return void
	 */
	public function testAddressLiterals(): void {
		$locality = $this->locality();

		foreach (['http://localhost:8080/run', 'http://127.0.0.1/run', 'http://[::1]/run', 'http://10.1.2.3/run', 'https://172.16.0.9/run', 'http://192.168.1.10/run', 'http://[fd00::1]/run'] as $url) {
			$this->assertTrue($locality->isLocal(url: $url), $url);
		}

		foreach (['https://8.8.8.8/run', 'http://169.254.169.254/latest', 'http://[2001:4860::8888]/run', 'not a url', 'http:///run'] as $url) {
			$this->assertFalse($locality->isLocal(url: $url), $url);
		}
	}

	/**
	 * A name is local only when every address it resolves to is local.
	 *
	 * @return void
	 */
	public function testNamesAreJudgedByEveryAddress(): void {
		$locality = $this->locality(dns: [
			'hermiq'       => ['172.18.0.5'],
			'mixed.test'   => ['10.0.0.2', '93.184.216.34'],
			'public.test'  => ['93.184.216.34'],
		]);

		$this->assertTrue($locality->isLocal(url: 'http://hermiq:8000/api/agentflow/run'));
		$this->assertFalse($locality->isLocal(url: 'http://mixed.test/run'));
		$this->assertFalse($locality->isLocal(url: 'http://public.test/run'));
		$this->assertFalse($locality->isLocal(url: 'http://unresolvable.test/run'));
	}
}
