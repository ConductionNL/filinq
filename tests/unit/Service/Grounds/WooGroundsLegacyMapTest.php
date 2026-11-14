<?php

/**
 * Unit tests for WooGroundsLegacyMap
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Grounds
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/tasks.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Grounds;

use OCA\Filinq\Service\Grounds\WooGroundsLegacyMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The legacy map re-points exactly filinq's seeded slugs, to codes the snapshot carries.
 */
#[CoversClass(WooGroundsLegacyMap::class)]
class WooGroundsLegacyMapTest extends TestCase {

	/**
	 * A fixture file, removed after each test.
	 *
	 * @var string|null
	 */
	private ?string $fixture = null;

	/**
	 * Remove the fixture.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		if ($this->fixture !== null && is_file($this->fixture) === true) {
			unlink($this->fixture);
		}

	}//end tearDown()

	/**
	 * A map file with the given contents.
	 *
	 * @param string $contents The file contents.
	 *
	 * @return WooGroundsLegacyMap
	 */
	private function mapOf(string $contents): WooGroundsLegacyMap {
		$this->fixture = (string)tempnam(sys_get_temp_dir(), 'legacy-map');
		file_put_contents($this->fixture, $contents);
		return new WooGroundsLegacyMap($this->fixture);

	}//end mapOf()

	/**
	 * The shipped map holds exactly the 19 Woo slugs of the seed, and every mapped code is in the snapshot.
	 *
	 * @return void
	 */
	public function testTheMapCoversTheSeedAndTheSnapshot(): void {
		$register = json_decode((string)file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_register.json'), true);
		$seeded = [];
		foreach ($register['components']['objects'] as $object) {
			if (($object['@self']['schema'] ?? '') === 'base') {
				$seeded[] = $object['@self']['slug'];
			}
		}

		$snapshot = json_decode((string)file_get_contents(__DIR__ . '/../../../../lib/Settings/woo-refusal-grounds.snapshot.json'), true);
		$codes = array_column($snapshot['grounds'], 'code');

		$map = (new WooGroundsLegacyMap())->all();

		$this->assertCount(19, $map);
		$this->assertSame($seeded, array_keys($map));
		foreach ($map as $slug => $code) {
			if ($code === WooGroundsLegacyMap::UNMAPPED) {
				continue;
			}

			$this->assertContains($code, $codes, $slug . ' maps to a code the snapshot does not carry');
		}

		$this->assertSame('5.1.2.e', (new WooGroundsLegacyMap())->codeFor('art-5-1-2-e'));
		$this->assertSame('5.2.2', (new WooGroundsLegacyMap())->codeFor('art-5-2-2'));

	}//end testTheMapCoversTheSeedAndTheSnapshot()

	/**
	 * An unmapped slug is a legacy slug without a code; anything else is neither.
	 *
	 * @return void
	 */
	public function testAnUnmappedSlugHasNoCode(): void {
		$map = $this->mapOf('{"map": {"art-x": "unmapped", "art-y": "5.1.4", "bad": 3}}');

		$this->assertTrue($map->isLegacySlug('art-x'));
		$this->assertNull($map->codeFor('art-x'));
		$this->assertSame('5.1.4', $map->codeFor('art-y'));
		$this->assertFalse($map->isLegacySlug('bad'));
		$this->assertFalse($map->isLegacySlug('5.1.4'));
		$this->assertNull($map->codeFor('persoonsgegevens'));

	}//end testAnUnmappedSlugHasNoCode()

	/**
	 * An unreadable map throws rather than re-point nothing silently.
	 *
	 * @return void
	 */
	public function testAnUnreadableMapThrows(): void {
		$map = $this->mapOf('not json');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The Woo grounds legacy map cannot be read.');

		$map->all();

	}//end testAnUnreadableMapThrows()
}//end class
