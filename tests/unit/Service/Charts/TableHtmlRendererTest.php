<?php

/**
 * Unit tests for TableHtmlRenderer.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service\Charts;

use OCA\Filinq\Service\Charts\TableHtmlRenderer;
use PHPUnit\Framework\TestCase;

/**
 * Tests column formatting/alignment/escaping and the empty-state fallback
 * of TableHtmlRenderer.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TableHtmlRendererTest extends TestCase {

	/**
	 * The renderer under test.
	 *
	 * @var TableHtmlRenderer
	 */
	private TableHtmlRenderer $renderer;

	/**
	 * Set up the test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->renderer = new TableHtmlRenderer();

	}//end setUp()

	/**
	 * An empty collection renders a single localised empty-state row rather
	 * than an empty or absent table (REQ-DDTCH-004).
	 *
	 * @return void
	 */
	public function testEmptyState(): void {
		$html = $this->renderer->render(
			collection: [],
			columns: [['key' => 'name', 'label' => 'Name']]
		);

		$this->assertStringContainsString('<table', $html);
		$this->assertStringContainsString('Geen gegevens beschikbaar', $html);
		// Exactly one data row (the empty-state row); the header row uses
		// <th>, not <td>, so this pattern only matches body rows.
		$this->assertSame(1, substr_count($html, '<tr><td'));

	}//end testEmptyState()

	/**
	 * An empty-state message can be overridden via options.
	 *
	 * @return void
	 */
	public function testEmptyStateCustomMessage(): void {
		$html = $this->renderer->render(
			collection: [],
			columns: [['key' => 'name', 'label' => 'Name']],
			options: ['emptyText' => 'Nothing here']
		);

		$this->assertStringContainsString('Nothing here', $html);

	}//end testEmptyStateCustomMessage()

	/**
	 * Selected columns render in order with NL-formatted date/currency
	 * values, and every cell is escaped.
	 *
	 * @return void
	 */
	public function testSelectedColumnsFormattedAndEscaped(): void {
		$collection = [
			[
				'name' => '<b>Acme</b>',
				'stars' => 1234,
				'joined' => '2026-03-05',
				'valuation' => 25000.5,
			],
		];

		$columns = [
			['key' => 'name', 'label' => 'Name'],
			['key' => 'joined', 'label' => 'Joined', 'format' => 'date'],
			['key' => 'valuation', 'label' => 'Valuation', 'format' => 'currency'],
		];

		$html = $this->renderer->render(collection: $collection, columns: $columns);

		$this->assertStringNotContainsString('<b>Acme</b>', $html);
		$this->assertStringContainsString('&lt;b&gt;Acme&lt;/b&gt;', $html);
		$this->assertStringContainsString('05-03-2026', $html);
		$this->assertStringContainsString('€ 25.000,50', $html);
		// 'stars' was not selected, so it must not leak into the output.
		$this->assertStringNotContainsString('1234', $html);
		// Header order matches the columns definition.
		$this->assertLessThan(
			strpos($html, 'Joined'),
			strpos($html, 'Name')
		);

	}//end testSelectedColumnsFormattedAndEscaped()

	/**
	 * A number-format column renders NL-style thousands separators.
	 *
	 * @return void
	 */
	public function testNumberFormatColumn(): void {
		$collection = [['count' => 12345]];
		$columns = [['key' => 'count', 'label' => 'Count', 'format' => 'number']];

		$html = $this->renderer->render(collection: $collection, columns: $columns);

		$this->assertStringContainsString('12.345', $html);

	}//end testNumberFormatColumn()

	/**
	 * A malformed column definition (missing 'key') is skipped rather than
	 * causing an error.
	 *
	 * @return void
	 */
	public function testMalformedColumnIsSkipped(): void {
		$collection = [['name' => 'Acme']];
		$columns = [
			['label' => 'No key here'],
			['key' => 'name', 'label' => 'Name'],
		];

		$html = $this->renderer->render(collection: $collection, columns: $columns);

		$this->assertStringContainsString('Acme', $html);
		$this->assertStringNotContainsString('No key here', $html);

	}//end testMalformedColumnIsSkipped()

	/**
	 * When no columns are supplied, columns are derived from the first
	 * row's keys (forgiving default) instead of an error.
	 *
	 * @return void
	 */
	public function testColumnsDerivedWhenOmitted(): void {
		$collection = [['name' => 'Acme', 'stars' => 10]];

		$html = $this->renderer->render(collection: $collection, columns: []);

		$this->assertStringContainsString('Acme', $html);
		$this->assertStringContainsString('10', $html);

	}//end testColumnsDerivedWhenOmitted()

	/**
	 * A missing value for a column renders as an empty cell, not an error.
	 *
	 * @return void
	 */
	public function testMissingValueRendersBlank(): void {
		$collection = [['name' => 'Acme']];
		$columns = [
			['key' => 'name', 'label' => 'Name'],
			['key' => 'missing', 'label' => 'Missing'],
		];

		$html = $this->renderer->render(collection: $collection, columns: $columns);

		$this->assertStringContainsString('<td', $html);
		$this->assertStringContainsString('Acme', $html);

	}//end testMissingValueRendersBlank()

	/**
	 * Rows beyond the configured maxRows guardrail are truncated with a
	 * visible note, never silently dropped without indication.
	 *
	 * @return void
	 */
	public function testMaxRowsGuardrail(): void {
		$collection = array_map(static fn ($i) => ['name' => 'Row' . $i], range(1, 10));
		$columns = [['key' => 'name', 'label' => 'Name']];

		$html = $this->renderer->render(collection: $collection, columns: $columns, options: ['maxRows' => 3]);

		$this->assertSame(3, substr_count($html, '<tr><td'));
		$this->assertStringContainsString('truncated', $html);

	}//end testMaxRowsGuardrail()

	/**
	 * A non-array, non-iterable collection value degrades to the
	 * empty-state row rather than throwing.
	 *
	 * @return void
	 */
	public function testNonIterableCollectionRendersEmptyState(): void {
		$html = $this->renderer->render(collection: null, columns: [['key' => 'name', 'label' => 'Name']]);

		$this->assertStringContainsString('Geen gegevens beschikbaar', $html);

	}//end testNonIterableCollectionRendersEmptyState()
}//end class
