<?php

/**
 * Golden test for TemplateMergeFieldService::resolve over declared and printed fields.
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

use OCA\Filinq\Service\TemplateMergeFieldService;
use PHPUnit\Framework\TestCase;

/**
 * Every template and data pair through resolve(), compared with JSON recorded
 * from the implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\TemplateMergeFieldService
 */
class TemplateMergeFieldResolveGoldenTest extends TestCase {

	/**
	 * The template and the data.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: array<string, mixed>}>
	 */
	public static function scenarios(): array {
		$data = ['zaak' => ['nummer' => 'Z-1', 'leeg' => ''], 'naam' => 'Anna', 'lijst' => [['x' => 1]]];
		return [
			'declared and printed' => [[
				'content' => '<p>{{ zaak.nummer }} {{ naam }} {{ onbekend.veld }}</p>',
				'mergeFields' => [['path' => 'naam', 'label' => 'Naam', 'source' => 'klant'], ['path' => 'zaak.nummer']],
			], $data],
			'only printed' => [['content' => '{{ naam|upper }} {{ zaak.leeg }}'], $data],
			'only declared' => [['mergeFields' => [['path' => ' naam ', 'label' => ' Naam ', 'source' => ' crm ']]], $data],
			'a declared duplicate' => [['mergeFields' => [['path' => 'naam', 'label' => 'Eerste'], ['path' => 'naam', 'label' => 'Tweede']]], $data],
			'a blank declared path' => [['mergeFields' => [['path' => '  ', 'label' => 'Leeg'], ['label' => 'Geen pad'], 'naam']], $data],
			'declared then printed again' => [['content' => '{{ naam }} {{ naam }} {{ zaak.nummer }}', 'mergeFields' => [['path' => 'zaak.nummer', 'label' => 'Nummer']]], $data],
			'a loop variable' => [['content' => '{% for r in lijst %}{{ r.x }}{% endfor %}{{ naam }}'], $data],
			'nothing filled' => [['content' => '{{ a }} {{ b.c }}', 'mergeFields' => [['path' => 'd', 'label' => 'D']]], []],
			'an empty template' => [[], $data],
			'a gap before a filled field' => [['content' => '{{ zaak.leeg }} {{ naam }}'], $data],
			'mergeFields not a list' => [['mergeFields' => 'naam', 'content' => '{{ naam }}'], $data],
			'a numeric label' => [['mergeFields' => [['path' => 'naam', 'label' => 12, 'source' => 3]]], $data],
		];
	}//end scenarios()

	/**
	 * The fields and the completeness match the recorded ones.
	 *
	 * @param array<string, mixed> $template The template.
	 * @param array<string, mixed> $data The data.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testResolve(array $template, array $data): void {
		$trace = (new TemplateMergeFieldService())->resolve($template, $data);

		$path = __DIR__ . '/golden/template-merge-field-resolve.json';
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
	}//end testResolve()
}//end class
