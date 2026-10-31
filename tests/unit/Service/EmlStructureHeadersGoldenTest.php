<?php

/**
 * Golden test for EmlStructureReader::headers over every header shape.
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
 * @spec openspec/changes/archive/2026-06-14-eml-pdf-assembly/tasks.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\EmlStructureReader;
use PHPUnit\Framework\TestCase;

/**
 * Every header shape through headers(), compared with JSON recorded from the
 * implementation before its closures became methods.
 *
 * @covers \OCA\Filinq\Service\EmlStructureReader
 */
class EmlStructureHeadersGoldenTest extends TestCase {

	/**
	 * The structures: the headers property, or 'absent' for none.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public static function scenarios(): array {
		return [
			'every header' => [[
				'from' => 'Anna <anna@example.nl>',
				'replyTo' => 'balie@example.nl',
				'to' => ['  a@example.nl ', 'b@example.nl'],
				'cc' => ['c@example.nl'],
				'subject' => 'Uw aanvraag',
				'date' => '2026-10-31 10:15:00',
			]],
			'no headers property' => ['absent'],
			'headers not an array' => ['From: x'],
			'an empty map' => [[]],
			'to and cc as strings' => [['to' => '  a@example.nl, b@example.nl ', 'cc' => '']],
			'lists with blanks and non-strings' => [['to' => ['', '  ', 7, null, ['x'], 'a@example.nl'], 'cc' => [' ', 'c@example.nl ']]],
			'to and cc as numbers' => [['to' => 12, 'cc' => true]],
			'non-string scalars' => [['from' => 12, 'replyTo' => null, 'subject' => ['x']]],
			'empty strings' => [['from' => '', 'subject' => '']],
			'untrimmed from' => [['from' => '  Anna  ']],
			'an unparseable date' => [['date' => '[geanonimiseerd]']],
			'a blank date' => [['date' => '   ']],
			'a numeric date' => [['date' => 1700000000]],
			'keyed lists' => [['to' => ['x' => 'a@example.nl', 'y' => ' b@example.nl']]],
		];
	}//end scenarios()

	/**
	 * The flattened headers match the recorded ones.
	 *
	 * @param mixed $headers The headers property, or 'absent'.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testHeaders(mixed $headers): void {
		$structure = new \stdClass();
		if ($headers !== 'absent') {
			$structure->headers = $headers;
		}

		$trace = (new EmlStructureReader())->headers($structure);

		$path = __DIR__ . '/golden/eml-structure-headers.json';
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
	}//end testHeaders()
}//end class
