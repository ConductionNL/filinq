<?php

/**
 * Unit tests for RefusalGroundsLegacyMap
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
 * @spec openspec/changes/archive/2026-11-24-grondslagen-read-from-dossiq/tasks.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Grounds;

use OCA\Filinq\Service\Grounds\RefusalGroundsLegacyMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The legacy map re-points exactly filinq's seeded slugs, to codes the snapshot carries.
 */
#[CoversClass(RefusalGroundsLegacyMap::class)]
class RefusalGroundsLegacyMapTest extends TestCase {

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
	 * @return RefusalGroundsLegacyMap
	 */
	private function mapOf(string $contents): RefusalGroundsLegacyMap {
		$this->fixture = (string)tempnam(sys_get_temp_dir(), 'legacy-map');
		file_put_contents($this->fixture, $contents);
		return new RefusalGroundsLegacyMap($this->fixture);

	}//end mapOf()

	/**
	 * The shipped map holds exactly the 19 Woo slugs of the former seed, none of which is still seeded, and every mapped code is in the snapshot.
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

		$map = (new RefusalGroundsLegacyMap())->all();

		$this->assertCount(19, $map);
		$this->assertSame(
			[
				'art-5-1-1-a', 'art-5-1-1-b', 'art-5-1-1-c', 'art-5-1-1-d', 'art-5-1-1-e',
				'art-5-1-2-a', 'art-5-1-2-b', 'art-5-1-2-c', 'art-5-1-2-d', 'art-5-1-2-e',
				'art-5-1-2-f', 'art-5-1-2-g', 'art-5-1-2-h', 'art-5-1-2-i',
				'art-5-1-4', 'art-5-1-5', 'art-5-1-6', 'art-5-2-1', 'art-5-2-2',
			],
			array_keys($map)
		);
		$this->assertSame([], array_intersect($seeded, array_keys($map)), 'A retired Woo slug is still seeded');
		foreach ($map as $slug => $code) {
			if ($code === RefusalGroundsLegacyMap::UNMAPPED) {
				continue;
			}

			$this->assertContains($code, $codes, $slug . ' maps to a code the snapshot does not carry');
		}

		$this->assertSame('5.1.2.e', (new RefusalGroundsLegacyMap())->codeFor('art-5-1-2-e'));
		$this->assertSame('5.2.2', (new RefusalGroundsLegacyMap())->codeFor('art-5-2-2'));

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
