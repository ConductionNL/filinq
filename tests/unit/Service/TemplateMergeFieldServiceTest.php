<?php

/**
 * Template merge fields: what a template prints, and what the data fills.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/template-merge-preview/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\TemplateMergeFieldService;
use PHPUnit\Framework\TestCase;

/**
 * Locks the extraction and the resolution of merge fields.
 */
class TemplateMergeFieldServiceTest extends TestCase {

	/**
	 * The service under test.
	 *
	 * @var TemplateMergeFieldService
	 */
	private TemplateMergeFieldService $service;

	/**
	 * A fresh service per test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->service = new TemplateMergeFieldService();
	}//end setUp()

	/**
	 * Printed paths are fields, in order; filters, functions and loop names are not.
	 *
	 * @return void
	 */
	public function testOnlyPrintedVariablesAreFields(): void {
		$content = '<p>{{ aanhef }}</p><p>{{ client.address }}, {{ title|upper }}</p>'
			. '{{ chart(data) }}{% for row in rows %}{{ row.name }}{% endfor %}{% set x = 1 %}{{ x }}{{ aanhef }}';

		$this->assertSame(
			expected: ['aanhef', 'client.address', 'title', 'data'],
			actual: $this->service->paths(content: $content)
		);
	}//end testOnlyPrintedVariablesAreFields()

	/**
	 * Templates and the paths they print, one shape of print tag each.
	 *
	 * @return array<string, array{0: string, 1: array<int, string>}>
	 */
	public static function printedPathProvider(): array {
		return [
			'empty'                                   => ['', []],
			'plain text'                              => ['Geen velden hier', []],
			'nested path'                             => ['{{ a.b.c }} {{ a.b }}', ['a.b.c', 'a.b']],
			'filter with argument'                    => ['{{ name|default(fallback) }}', ['name', 'fallback']],
			'filter chain then attribute'             => ['{{ (x|first).y }}', ['x']],
			'function call with attribute argument'   => ['{{ format(order.date, locale) }}', ['order.date', 'locale']],
			'for binds key and value'                 => ['{% for key, value in items %}{{ key }}{{ value.x }}{% endfor %}{{ items }}', ['items']],
			'set binds before use'                    => ['{{ total }}{% set total = 2 %}{{ total }}', []],
			'names inside block tags are not printed' => ['{% if visible %}{{ shown }}{% endif %}', ['shown']],
			'method call on path'                     => ['{{ user.name() }}{{ user.email }}', ['user.email']],
			'repeated across prints'                  => ['{{ a }}{{ b }}{{ a }}{{ b.c }}', ['a', 'b', 'b.c']],
			'subscript'                               => ["{{ list[0] }}{{ map['k'] }}", ['list', 'map']],
			'arithmetic'                              => ['{{ price * qty + tax.rate }}', ['price', 'qty', 'tax.rate']],
			'trailing dot'                            => ['{{ a. }}', ['a']],
		];
	}//end printedPathProvider()

	/**
	 * Each shape of print tag yields exactly these paths.
	 *
	 * @param string             $content  The template.
	 * @param array<int, string> $expected The paths it prints.
	 *
	 * @dataProvider printedPathProvider
	 *
	 * @return void
	 */
	public function testPrintedPathsPerShape(string $content, array $expected): void {
		$this->assertSame(expected: $expected, actual: $this->service->paths(content: $content));
	}//end testPrintedPathsPerShape()

	/**
	 * A template that does not lex lists no fields instead of failing.
	 *
	 * @return void
	 */
	public function testABrokenTemplateListsNoFields(): void {
		$this->assertSame(expected: [], actual: $this->service->paths(content: '{{ "unterminated }}'));
	}//end testABrokenTemplateListsNoFields()

	/**
	 * Every field filled: each has its value and the preview is complete.
	 *
	 * @return void
	 */
	public function testEveryFieldFilled(): void {
		$result = $this->service->resolve(
			template: ['content' => '{{ aanhef }} {{ client.address }}'],
			data: ['aanhef' => 'Geachte mevrouw De Vries,', 'client' => ['address' => 'Lindelaan 14']]
		);

		$this->assertTrue(condition: $result['complete']);
		$this->assertSame(expected: 'Lindelaan 14', actual: $result['fields'][1]['value']);
		$this->assertTrue(condition: $result['fields'][1]['filled']);
	}//end testEveryFieldFilled()

	/**
	 * An empty field is named and the preview is not complete.
	 *
	 * @return void
	 */
	public function testAnEmptyFieldIsNamed(): void {
		$result = $this->service->resolve(
			template: ['content' => '{{ aanhef }} {{ client.address }}'],
			data: ['aanhef' => 'Geachte mevrouw De Vries,', 'client' => ['address' => '']]
		);

		$this->assertFalse(condition: $result['complete']);
		$this->assertSame(expected: 'client.address', actual: $result['fields'][1]['path']);
		$this->assertFalse(condition: $result['fields'][1]['filled']);
	}//end testAnEmptyFieldIsNamed()

	/**
	 * Declared fields come first with their labels and sources; the rest follow by path.
	 *
	 * @return void
	 */
	public function testDeclaredFieldsCarryTheirLabels(): void {
		$result = $this->service->resolve(
			template: [
				'content'     => '{{ kenmerk }} {{ aanhef }} {{ extra }}',
				'mergeFields' => [
					['path' => 'aanhef', 'label' => 'Aanhef', 'source' => 'op basis van het opgegeven geslacht'],
					['path' => 'kenmerk', 'label' => 'Kenmerk', 'source' => 'het open ticket'],
				],
			],
			data: ['aanhef' => 'Geachte heer Jansen,', 'kenmerk' => 'PQ-2026-0417', 'extra' => 'x']
		);

		$this->assertSame(expected: ['aanhef', 'kenmerk', 'extra'], actual: array_column($result['fields'], 'path'));
		$this->assertSame(expected: 'Aanhef', actual: $result['fields'][0]['label']);
		$this->assertSame(expected: 'op basis van het opgegeven geslacht', actual: $result['fields'][0]['source']);
		$this->assertSame(expected: 'extra', actual: $result['fields'][2]['label']);
	}//end testDeclaredFieldsCarryTheirLabels()
}//end class
