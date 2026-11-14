<?php

/**
 * Filinq OfficeSuiteAvailabilityService
 *
 * Resolves, per configured office suite, whether WOPI is usable on this instance,
 * so the result can be surfaced as a runtime capability instead of only through
 * `occ filinq:office:probe`.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Service
 * @package  OCA\Filinq\Service\Office
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

namespace OCA\Filinq\Service\Office;

use OCA\Filinq\Kernel\Contracts\OfficeSuiteAvailabilityServiceInterface;
use OCA\Filinq\Kernel\Contracts\OfficeSuiteCapabilityServiceInterface;
use OCP\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;

/**
 * Per-suite WOPI availability, probed on demand and cached.
 *
 * The probe never runs on app load: it runs only when a caller asks for the
 * capability, and the verdict is cached so a page that asks repeatedly costs at
 * most one bounded probe per suite per cache lifetime.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Office
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 *
 * @spec openspec/changes/office-suite-portability
 *
 * @suite-registry This class holds the list of known suites so each one can be
 *                 probed SEPARATELY, so it necessarily names their app ids. No
 *                 capability depends on any suite being present: an absent suite
 *                 is reported, never required (ADR-087 §5).
 */
class OfficeSuiteAvailabilityService implements OfficeSuiteAvailabilityServiceInterface {

	/**
	 * Suites we know how to locate, and the app-config key holding each one's
	 * server URL.
	 *
	 * Listing a suite here is NOT a claim that it is supported — only that we know
	 * where to look for it. The probe's verdict is the claim.
	 *
	 * @var array<string, array{app: string, key: string, paths: array<int, string>}>
	 */
	public const SUITES = [
		'onlyoffice' => [
			'app' => 'onlyoffice',
			'key' => 'DocumentServerInternalUrl',
			'paths' => ['/hosting/discovery', '/hosting/capabilities'],
		],
		'eurooffice' => [
			'app' => 'eurooffice',
			'key' => 'DocumentServerInternalUrl',
			'paths' => ['/hosting/discovery', '/hosting/capabilities'],
		],
		'collabora' => [
			'app' => 'richdocuments',
			'key' => 'wopi_url',
			'paths' => ['/hosting/discovery'],
		],
	];

	/**
	 * Cache key of the resolved verdict.
	 *
	 * @var string
	 */
	private const CACHE_KEY = 'office-suite-availability';

	/**
	 * Cache lifetime in seconds.
	 *
	 * Short enough that an operator who enables WOPI sees the capability appear
	 * within minutes, long enough that the probe stays off the hot path.
	 *
	 * @var int
	 */
	private const CACHE_TTL = 300;

	/**
	 * The cache holding the resolved verdict.
	 *
	 * @var ICache
	 */
	private ICache $cache;

	/**
	 * Constructor.
	 *
	 * @param OfficeSuiteCapabilityServiceInterface $capability   The WOPI probe.
	 * @param IAppConfig                            $appConfig    App configuration.
	 * @param ICacheFactory                         $cacheFactory Cache factory.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly OfficeSuiteCapabilityServiceInterface $capability,
		private readonly IAppConfig $appConfig,
		ICacheFactory $cacheFactory,
	) {
		$this->cache = $cacheFactory->createDistributed('filinq-office');
	}//end __construct()

	/**
	 * Resolve WOPI availability for every known suite.
	 *
	 * A cached verdict is returned when present. Otherwise each suite is probed on
	 * its own — no suite's verdict is derived from another's — and the result is
	 * cached. A suite with no URL configured resolves absent without a network
	 * call, and its reason says so, so "not installed" stays distinguishable from
	 * "installed but the probe failed".
	 *
	 * @return array{available: bool, suites: array<string, array{available: bool, configured: bool, reason: string, suite: string|null}>} The verdict.
	 *
	 * @spec openspec/changes/office-suite-portability
	 */
	public function resolve(): array {
		$cached = $this->cache->get(self::CACHE_KEY);
		if (is_array($cached) === true && isset($cached['suites']) === true && is_array($cached['suites']) === true) {
			return $cached;
		}

		$suites    = [];
		$available = false;
		foreach (self::SUITES as $name => $suite) {
			$suites[$name] = $this->resolveSuite(suite: $suite);
			if ($suites[$name]['available'] === true) {
				$available = true;
			}
		}

		$verdict = ['available' => $available, 'suites' => $suites];
		$this->cache->set(self::CACHE_KEY, $verdict, self::CACHE_TTL);

		return $verdict;
	}//end resolve()

	/**
	 * Probe one suite at each of its discovery paths.
	 *
	 * @param array{app: string, key: string, paths: array<int, string>} $suite The suite mapping.
	 *
	 * @return array{available: bool, configured: bool, reason: string, suite: string|null} The suite's verdict.
	 */
	private function resolveSuite(array $suite): array {
		$base = trim($this->appConfig->getValueString($suite['app'], $suite['key'], ''));
		if ($base === '') {
			return [
				'available' => false,
				'configured' => false,
				'reason' => sprintf('no %s configured', $suite['key']),
				'suite' => null,
			];
		}

		$reason = 'no path answered';
		foreach ($suite['paths'] as $path) {
			$verdict = $this->capability->probeDiscovery(discoveryUrl: rtrim($base, '/') . $path);
			if ($verdict['available'] === true) {
				return [
					'available' => true,
					'configured' => true,
					'reason' => $verdict['reason'],
					'suite' => $verdict['suite'],
				];
			}

			$reason = $verdict['reason'];
		}

		return [
			'available' => false,
			'configured' => true,
			'reason' => $reason,
			'suite' => null,
		];
	}//end resolveSuite()
}//end class
