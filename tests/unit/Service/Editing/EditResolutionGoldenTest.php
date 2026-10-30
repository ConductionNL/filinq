<?php

/**
 * Golden results of resolving an edit set against a document's block anchors.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\PackageCodec;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

/**
 * Each edit set's resolution or refusal, compared with JSON recorded from the
 * implementation before resolveEdits() was split.
 *
 * @covers \OCA\Filinq\Service\Editing\PackageCodec
 * @uses   \OCA\Filinq\Service\Editing\BlockStyleCodec
 * @uses   \OCA\Filinq\Service\Editing\XmlBlockScanner
 * @uses   \OCA\Filinq\Service\Editing\PackagePartIo
 */
class EditResolutionGoldenTest extends TestCase {

	/**
	 * The edit sets.
	 *
	 * @return array<string, array{0: array<int|string, mixed>}>
	 */
	public static function editSets(): array {
		$style = ['bold' => true];
		return [
			'every action' => [[
				['anchor' => 'b2', 'text' => 'Nieuw'],
				['anchor' => 'b1', 'action' => 'insertAfter', 'text' => 'Erna'],
				['anchor' => 'b3', 'action' => 'delete'],
				['anchor' => 'b3', 'action' => 'style', 'style' => $style],
				['anchor' => 'b1', 'action' => 'delete', 'text' => 'ignored', 'style' => 'x'],
			]],
			'no edits' => [[]],
			'an unknown action' => [[['anchor' => 'b1', 'text' => 'a'], ['anchor' => 'b1', 'action' => 'move', 'text' => 'b']]],
			'an unknown action and an unknown anchor' => [[['anchor' => 'zz', 'action' => 'move']]],
			'an unknown anchor' => [[['anchor' => 'zz', 'text' => 'a']]],
			'a missing anchor' => [[['text' => 'a']]],
			'an unknown anchor and no text' => [[['anchor' => 'zz']]],
			'a replace without text' => [[['anchor' => 'b1', 'text' => '']]],
			'an insert without text' => [[['anchor' => 'b1', 'action' => 'insertAfter']]],
			'a style without style' => [[['anchor' => 'b1', 'action' => 'style']]],
			'a style with an empty style' => [[['anchor' => 'b1', 'action' => 'style', 'style' => []]]],
			'a style with a string' => [[['anchor' => 'b1', 'action' => 'style', 'style' => 'bold']]],
			'a replace with a style string' => [[['anchor' => 'b2', 'text' => 'x', 'style' => 'bold']]],
			'string keys' => [['first' => ['anchor' => 'b1', 'text' => 'a'], 'second' => ['anchor' => 'b1', 'action' => 'insertAfter']]],
			'a numeric text' => [[['anchor' => 'b1', 'text' => 0]]],
		];
	}//end editSets()

	/**
	 * The resolution matches the recorded one.
	 *
	 * @param array<int|string, mixed> $edits The edit set.
	 *
	 * @dataProvider editSets
	 *
	 * @return void
	 */
	public function testResolutionMatchesTheRecordedOne(array $edits): void {
		$method = new ReflectionMethod(PackageCodec::class, 'resolveEdits');
		try {
			$result = ['resolved' => $method->invoke(new PackageCodec(), $edits, ['b1', 'b2', 'b3'])];
		} catch (RuntimeException $e) {
			$result = ['refused' => $e->getMessage()];
		}

		$path = __DIR__ . '/golden/edit-resolution.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $result;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $result);
	}//end testResolutionMatchesTheRecordedOne()
}//end class
