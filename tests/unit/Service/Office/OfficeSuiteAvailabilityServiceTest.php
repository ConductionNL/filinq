<?php

/**
 * Unit tests for OfficeSuiteAvailabilityService.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Office
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/office-suite-portability
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Office;

use OCA\Filinq\Kernel\Contracts\OfficeSuiteCapabilityServiceInterface;
use OCA\Filinq\Service\Office\OfficeSuiteAvailabilityService;
use OCP\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Each suite is resolved on its own, cached, and absent unless WOPI answers.
 */
#[CoversClass(OfficeSuiteAvailabilityService::class)]
class OfficeSuiteAvailabilityServiceTest extends TestCase {

	/**
	 * The probe double.
	 *
	 * @var OfficeSuiteCapabilityServiceInterface&MockObject
	 */
	private OfficeSuiteCapabilityServiceInterface&MockObject $probe;

	/**
	 * The cache double.
	 *
	 * @var ICache&MockObject
	 */
	private ICache&MockObject $cache;

	/**
	 * Configured base URLs keyed by app id.
	 *
	 * @var array<string, string>
	 */
	private array $configured = [];

	/**
	 * Build the subject.
	 *
	 * @return OfficeSuiteAvailabilityService
	 */
	private function service(): OfficeSuiteAvailabilityService {
		$appConfig = $this->createMock(originalClassName: IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default): string => $this->configured[$app] ?? $default
		);

		$factory = $this->createMock(originalClassName: ICacheFactory::class);
		$factory->expects(self::once())->method('createDistributed')->with('filinq-office')->willReturn($this->cache);

		return new OfficeSuiteAvailabilityService(
			capability: $this->probe,
			appConfig: $appConfig,
			cacheFactory: $factory,
		);
	}//end service()

	/**
	 * Fresh doubles per test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->probe      = $this->createMock(originalClassName: OfficeSuiteCapabilityServiceInterface::class);
		$this->cache      = $this->createMock(originalClassName: ICache::class);
		$this->configured = [];
	}//end setUp()

	/**
	 * With nothing configured, nothing is probed and every suite is absent as
	 * "not configured" — distinguishable from a probe that failed.
	 *
	 * @return void
	 */
	public function testNothingConfiguredResolvesAbsentWithoutAProbe(): void {
		$this->cache->method('get')->willReturn(null);
		$this->probe->expects(self::never())->method('probeDiscovery');

		$verdict = $this->service()->resolve();

		self::assertFalse($verdict['available']);
		self::assertSame(['onlyoffice', 'eurooffice', 'collabora'], array_keys($verdict['suites']));
		self::assertSame(
			['available' => false, 'configured' => false, 'reason' => 'no wopi_url configured', 'suite' => null],
			$verdict['suites']['collabora']
		);
		self::assertSame('no DocumentServerInternalUrl configured', $verdict['suites']['onlyoffice']['reason']);
	}//end testNothingConfiguredResolvesAbsentWithoutAProbe()

	/**
	 * An installed suite whose WOPI is off is configured but absent, carrying the
	 * probe's own reason from its last path.
	 *
	 * @return void
	 */
	public function testConfiguredSuiteWithWopiOffIsAbsentWithTheProbeReason(): void {
		$this->configured = ['onlyoffice' => 'http://oo:80/'];
		$this->cache->method('get')->willReturn(null);

		$calls = [];
		$this->probe->method('probeDiscovery')->willReturnCallback(
			function (string $discoveryUrl) use (&$calls): array {
				$calls[] = $discoveryUrl;
				return ['available' => false, 'reason' => 'discovery returned 404 at ' . $discoveryUrl, 'suite' => null];
			}
		);

		$verdict = $this->service()->resolve();

		self::assertSame(['http://oo:80/hosting/discovery', 'http://oo:80/hosting/capabilities'], $calls);
		self::assertFalse($verdict['available']);
		self::assertSame(
			[
				'available' => false,
				'configured' => true,
				'reason' => 'discovery returned 404 at http://oo:80/hosting/capabilities',
				'suite' => null,
			],
			$verdict['suites']['onlyoffice']
		);
		self::assertFalse($verdict['suites']['eurooffice']['configured']);
	}//end testConfiguredSuiteWithWopiOffIsAbsentWithTheProbeReason()

	/**
	 * One available suite makes the capability available, and its verdict is not
	 * copied onto any other suite.
	 *
	 * @return void
	 */
	public function testOneAvailableSuiteIsReportedOnlyForItself(): void {
		$this->configured = ['richdocuments' => 'http://collabora:9980'];
		$this->cache->method('get')->willReturn(null);
		$this->probe->expects(self::once())->method('probeDiscovery')
			->with('http://collabora:9980/hosting/discovery')
			->willReturn(['available' => true, 'reason' => 'WOPI discovery served', 'suite' => 'Collabora Online']);

		$verdict = $this->service()->resolve();

		self::assertTrue($verdict['available']);
		self::assertSame(
			['available' => true, 'configured' => true, 'reason' => 'WOPI discovery served', 'suite' => 'Collabora Online'],
			$verdict['suites']['collabora']
		);
		self::assertFalse($verdict['suites']['onlyoffice']['available']);
		self::assertFalse($verdict['suites']['eurooffice']['available']);
	}//end testOneAvailableSuiteIsReportedOnlyForItself()

	/**
	 * The first answering path wins; later paths are not probed.
	 *
	 * @return void
	 */
	public function testTheFirstAnsweringPathStopsTheSuiteProbe(): void {
		$this->configured = ['eurooffice' => 'http://eo'];
		$this->cache->method('get')->willReturn(null);
		$this->probe->expects(self::once())->method('probeDiscovery')
			->with('http://eo/hosting/discovery')
			->willReturn(['available' => true, 'reason' => 'WOPI discovery served', 'suite' => null]);

		$verdict = $this->service()->resolve();

		self::assertTrue($verdict['suites']['eurooffice']['available']);
		self::assertNull($verdict['suites']['eurooffice']['suite']);
	}//end testTheFirstAnsweringPathStopsTheSuiteProbe()

	/**
	 * The resolved verdict is cached with a bounded lifetime.
	 *
	 * @return void
	 */
	public function testTheVerdictIsCached(): void {
		$this->cache->method('get')->willReturn(null);
		$this->cache->expects(self::once())->method('set')->with(
			'office-suite-availability',
			self::callback(static fn (array $v): bool => $v['available'] === false && count($v['suites']) === 3),
			300
		);

		$this->service()->resolve();
	}//end testTheVerdictIsCached()

	/**
	 * A cached verdict is returned without probing again.
	 *
	 * @return void
	 */
	public function testACachedVerdictSkipsTheProbe(): void {
		$cached = ['available' => true, 'suites' => ['collabora' => ['available' => true, 'configured' => true, 'reason' => 'x', 'suite' => null]]];
		$this->configured = ['richdocuments' => 'http://collabora'];
		$this->cache->method('get')->with('office-suite-availability')->willReturn($cached);
		$this->cache->expects(self::never())->method('set');
		$this->probe->expects(self::never())->method('probeDiscovery');

		self::assertSame($cached, $this->service()->resolve());
	}//end testACachedVerdictSkipsTheProbe()

	/**
	 * A malformed cache entry is ignored and the suites are probed afresh.
	 *
	 * @return void
	 */
	public function testAMalformedCacheEntryIsReprobed(): void {
		$this->configured = ['richdocuments' => 'http://collabora'];
		$this->cache->method('get')->willReturn(['available' => true]);
		$this->probe->expects(self::once())->method('probeDiscovery')
			->willReturn(['available' => false, 'reason' => 'discovery returned 503', 'suite' => null]);

		$verdict = $this->service()->resolve();

		self::assertFalse($verdict['available']);
		self::assertSame('discovery returned 503', $verdict['suites']['collabora']['reason']);
	}//end testAMalformedCacheEntryIsReprobed()
}//end class
