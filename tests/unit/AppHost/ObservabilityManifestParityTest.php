<?php

/**
 * AppHost observability manifest parity test.
 *
 * Locks the declarative `observability` block in src/manifest.json against the
 * behaviour of the bespoke HealthController / MetricsController / MetricsCollector
 * that it replaced (deleted in build/adopt-apphost-2026-06-16). The generic
 * OpenRegister AppHost controllers consume this block to serve /api/health and
 * /api/metrics; this test guards the contract docudesk hands the engine so a
 * future manifest edit cannot silently drop a metric or health check.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\AppHost
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/adopt-apphost/tasks.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\AppHost;

use PHPUnit\Framework\TestCase;

/**
 * Parity guard for the manifest observability block.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\AppHost
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ObservabilityManifestParityTest extends TestCase {
	/**
	 * Decoded manifest.json.
	 *
	 * @var array<string, mixed>
	 */
	private array $manifest = [];

	/**
	 * Load and decode the bundled manifest.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$path = __DIR__ . '/../../../src/manifest.json';
		$this->assertFileExists($path, 'src/manifest.json must exist');
		$raw = file_get_contents($path);
		$this->assertIsString($raw);
		$decoded = json_decode($raw, associative: true);
		$this->assertIsArray($decoded, 'manifest.json must be valid JSON');
		$this->manifest = $decoded;

	}//end setUp()

	/**
	 * The observability block exists and exposes a health + metrics section.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/adopt-apphost/tasks.md
	 */
	public function testObservabilityBlockPresent(): void {
		$this->assertArrayHasKey('observability', $this->manifest);
		$observability = $this->manifest['observability'];
		$this->assertArrayHasKey('health', $observability);
		$this->assertArrayHasKey('metrics', $observability);

	}//end testObservabilityBlockPresent()

	/**
	 * Health parity: ADR-006 status-code policy + the database (critical) and
	 * openregister (degraded) checks the bespoke HealthController ran.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/adopt-apphost/tasks.md
	 */
	public function testHealthChecksMatchBespokeBehaviour(): void {
		$health = $this->manifest['observability']['health'];
		$this->assertSame('adr006', $health['statusCodePolicy']);

		$byId = [];
		foreach ($health['checks'] as $check) {
			$byId[$check['id']] = $check;
		}

		$this->assertArrayHasKey('database', $byId);
		$this->assertSame('database', $byId['database']['type']);
		$this->assertSame('critical', $byId['database']['severity']);

		// The old controller set status=degraded (not error) when OpenRegister
		// was missing — reproduced here as an orAvailable check at degraded
		// severity.
		$this->assertArrayHasKey('openregister', $byId);
		$this->assertSame('orAvailable', $byId['openregister']['type']);
		$this->assertSame('degraded', $byId['openregister']['severity']);

	}//end testHealthChecksMatchBespokeBehaviour()

	/**
	 * Metrics parity: the four declared metrics reproduce the bespoke
	 * MetricsController output (engine prepends implicit info/up). Names are
	 * unprefixed in the manifest; the engine renders them as docudesk_*.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/adopt-apphost/tasks.md
	 */
	public function testMetricsMatchBespokeOutput(): void {
		$metrics = $this->manifest['observability']['metrics'];

		$byName = [];
		foreach ($metrics as $metric) {
			$byName[$metric['name']] = $metric;
		}

		$expected = [
			'documents_total' => ['type' => 'gauge',   'kind' => 'objectCount', 'schema' => 'document'],
			'templates_total' => ['type' => 'gauge',   'kind' => 'objectCount', 'schema' => 'template'],
			'pdf_generations_total' => ['type' => 'counter', 'kind' => 'appConfig',   'key' => 'pdf_generations_total'],
			'anonymizations_total' => ['type' => 'counter', 'kind' => 'appConfig',   'key' => 'anonymizations_total'],
		];

		foreach ($expected as $name => $spec) {
			$this->assertArrayHasKey($name, $byName, $name . ' metric must be declared');
			$this->assertSame($spec['type'], $byName[$name]['type'], $name . ' type');
			$this->assertSame($spec['kind'], $byName[$name]['source']['kind'], $name . ' source kind');

			if (isset($spec['schema']) === true) {
				$this->assertSame($spec['schema'], $byName[$name]['source']['schema'], $name . ' schema slug');
			}

			if (isset($spec['key']) === true) {
				$this->assertSame($spec['key'], $byName[$name]['source']['key'], $name . ' appConfig key');
			}
		}

		// No extra metrics beyond the four that had bespoke parity.
		$this->assertCount(4, $byName, 'exactly the four parity metrics are declared');

	}//end testMetricsMatchBespokeOutput()
}//end class
