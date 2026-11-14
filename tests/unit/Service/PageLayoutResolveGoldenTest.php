<?php

/**
 * Golden test for PageLayoutService::resolve: which version of a layout a template gets.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\PageLayoutService;
use PHPUnit\Framework\TestCase;

/**
 * Every name, version and stored version list through resolve(), compared
 * with JSON recorded from the implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\PageLayoutService
 */
class PageLayoutResolveGoldenTest extends TestCase {

	/**
	 * The name, the version asked for, and the stored versions (newest first, as versionsOf answers).
	 *
	 * @return array<string, array{0: string, 1: int|null, 2: array<int, array<string, mixed>>}>
	 */
	public static function scenarios(): array {
		$v3 = ['uuid' => 'c', 'layoutVersion' => 3, 'active' => false];
		$v2 = ['uuid' => 'b', 'layoutVersion' => 2, 'active' => true];
		$v1 = ['uuid' => 'a', 'layoutVersion' => '1', 'active' => false];
		return [
			'the active one' => ['brief', null, [$v3, $v2, $v1]],
			'version 2' => ['brief', 2, [$v3, $v2, $v1]],
			'version 1 as a string' => ['brief', 1, [$v3, $v2, $v1]],
			'a version that does not exist' => ['brief', 9, [$v3, $v2, $v1]],
			'version 0 and one without a number' => ['brief', 0, [$v3, ['uuid' => 'x']]],
			'nothing active' => ['brief', null, [$v3, $v1]],
			'active as a string' => ['brief', null, [$v3, ['uuid' => 's', 'active' => 'true']]],
			'two active' => ['brief', null, [$v3, $v2, ['uuid' => 'd', 'active' => true]]],
			'no versions' => ['brief', null, []],
			'no versions, a version asked' => ['brief', 2, []],
			'no name' => ['', null, [$v2]],
			'the first of two equal numbers' => ['brief', 3, [$v3, ['uuid' => 'e', 'layoutVersion' => 3]]],
		];
	}//end scenarios()

	/**
	 * The layout matches the recorded one.
	 *
	 * @param string $name The layout name.
	 * @param int|null $version The version asked for.
	 * @param array<int, array<string, mixed>> $versions The stored versions.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testResolve(string $name, ?int $version, array $versions): void {
		$asked = [];
		$service = $this->getMockBuilder(PageLayoutService::class)
			->disableOriginalConstructor()
			->onlyMethods(['versionsOf'])
			->getMock();
		$service->method('versionsOf')->willReturnCallback(static function (string $name) use (&$asked, $versions): array {
			$asked[] = $name;
			return $versions;
		});

		$trace = ['layout' => $service->resolve($name, $version), 'asked' => $asked];

		$path = __DIR__ . '/golden/page-layout-resolve.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$key = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$key] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$key] ?? null, $trace);
	}//end testResolve()
}//end class
