<?php

/**
 * Unit tests for TableDataNormalizer.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Charts;

use ArrayIterator;
use OCA\Filinq\Service\Charts\TableDataNormalizer;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Collections, derived columns and column normalisation.
 *
 * @covers \OCA\Filinq\Service\Charts\TableDataNormalizer
 */
class TableDataNormalizerTest extends TestCase {

	/**
	 * Arrays stay, objects are cast to arrays, scalars are dropped.
	 *
	 * @return void
	 */
	public function testNormalizeCollectionKeepsRowShapedItems(): void {
		$object = new stdClass();
		$object->name = 'Ada';

		$rows = (new TableDataNormalizer())->normalizeCollection(collection: [['a' => 1], 'scalar', 7, null, $object]);

		$this->assertSame([['a' => 1], ['name' => 'Ada']], $rows);
	}//end testNormalizeCollectionKeepsRowShapedItems()

	/**
	 * A Traversable collection is iterated.
	 *
	 * @return void
	 */
	public function testNormalizeCollectionAcceptsTraversable(): void {
		$rows = (new TableDataNormalizer())->normalizeCollection(collection: new ArrayIterator([['x' => 1], ['x' => 2]]));

		$this->assertSame([['x' => 1], ['x' => 2]], $rows);
	}//end testNormalizeCollectionAcceptsTraversable()

	/**
	 * Anything that is not a collection gives no rows.
	 *
	 * @return void
	 */
	public function testNormalizeCollectionRejectsNonCollections(): void {
		$normalizer = new TableDataNormalizer();

		$this->assertSame([], $normalizer->normalizeCollection(collection: 'rows'));
		$this->assertSame([], $normalizer->normalizeCollection(collection: null));
		$this->assertSame([], $normalizer->normalizeCollection(collection: new stdClass()));
	}//end testNormalizeCollectionRejectsNonCollections()

	/**
	 * Only string keys of the first row become columns, labelled by key.
	 *
	 * @return void
	 */
	public function testDeriveColumnsUsesStringKeys(): void {
		$columns = (new TableDataNormalizer())->deriveColumns(firstRow: ['name' => 'a', 0 => 'b', 'age' => 3]);

		$this->assertSame(
			[
				['key' => 'name', 'label' => 'name'],
				['key' => 'age', 'label' => 'age'],
			],
			$columns
		);
	}//end testDeriveColumnsUsesStringKeys()

	/**
	 * Columns without a string key are dropped; defaults are filled in.
	 *
	 * @return void
	 */
	public function testNormalizeColumnsFillsDefaultsAndDropsInvalid(): void {
		$columns = (new TableDataNormalizer())->normalizeColumns(
			columns: [
				'not an array',
				['label' => 'No key'],
				['key' => 5],
				['key' => 'name'],
				['key' => 'amount', 'label' => 'Bedrag', 'format' => 'currency'],
				['key' => 'count', 'format' => 'number', 'align' => 'center'],
				['key' => 'when', 'format' => 'date', 'align' => 'diagonal'],
				['key' => 'odd', 'format' => 'html', 'label' => 12],
			]
		);

		$this->assertSame(
			[
				['key' => 'name', 'label' => 'name', 'align' => 'left', 'format' => 'text'],
				['key' => 'amount', 'label' => 'Bedrag', 'align' => 'right', 'format' => 'currency'],
				['key' => 'count', 'label' => 'count', 'align' => 'center', 'format' => 'number'],
				['key' => 'when', 'label' => 'when', 'align' => 'left', 'format' => 'date'],
				['key' => 'odd', 'label' => '12', 'align' => 'left', 'format' => 'text'],
			],
			$columns
		);
	}//end testNormalizeColumnsFillsDefaultsAndDropsInvalid()
}//end class
