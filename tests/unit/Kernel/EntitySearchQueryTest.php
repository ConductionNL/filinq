<?php

/**
 * EntitySearchQuery: what a search request becomes once normalised.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Kernel
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Kernel;

use OCA\Filinq\Kernel\Contracts\EntitySearchServiceInterface;
use OCA\Filinq\Kernel\EntitySearchQuery;
use PHPUnit\Framework\TestCase;

/**
 * The filters are trimmed, the page size clamped to 1..max and the offset
 * to zero or more, without changing the original query.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Kernel
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Kernel\EntitySearchQuery
 */
class EntitySearchQueryTest extends TestCase {

	/**
	 * A query without arguments asks for the default page from the start.
	 *
	 * @return void
	 */
	public function testTheDefaultsAreAnUnfilteredFirstPage(): void {
		$query = new EntitySearchQuery();

		$this->assertSame(['', '', '', EntitySearchServiceInterface::DEFAULT_LIMIT, 0], $this->fields($query));
	}//end testTheDefaultsAreAnUnfilteredFirstPage()

	/**
	 * The text, type and category filters lose surrounding whitespace.
	 *
	 * @return void
	 */
	public function testTheFiltersAreTrimmed(): void {
		$query = (new EntitySearchQuery(text: '  Jan  ', type: "\tperson ", category: ' bsn'))->normalised(maxLimit: 100);

		$this->assertSame(['Jan', 'person', 'bsn', EntitySearchServiceInterface::DEFAULT_LIMIT, 0], $this->fields($query));
	}//end testTheFiltersAreTrimmed()

	/**
	 * The page size is clamped to at least one and at most the maximum.
	 *
	 * @return void
	 */
	public function testThePageSizeIsClampedToOneThroughTheMaximum(): void {
		$this->assertSame(1, (new EntitySearchQuery(limit: 0))->normalised(maxLimit: 100)->limit);
		$this->assertSame(1, (new EntitySearchQuery(limit: -5))->normalised(maxLimit: 100)->limit);
		$this->assertSame(100, (new EntitySearchQuery(limit: 100))->normalised(maxLimit: 100)->limit);
		$this->assertSame(100, (new EntitySearchQuery(limit: 101))->normalised(maxLimit: 100)->limit);
		$this->assertSame(10, (new EntitySearchQuery(limit: 50))->normalised(maxLimit: 10)->limit);
		$this->assertSame(7, (new EntitySearchQuery(limit: 7))->normalised(maxLimit: 10)->limit);
	}//end testThePageSizeIsClampedToOneThroughTheMaximum()

	/**
	 * A negative offset starts from the beginning; a positive one is kept.
	 *
	 * @return void
	 */
	public function testANegativeOffsetStartsFromTheBeginning(): void {
		$this->assertSame(0, (new EntitySearchQuery(offset: -3))->normalised(maxLimit: 100)->offset);
		$this->assertSame(40, (new EntitySearchQuery(offset: 40))->normalised(maxLimit: 100)->offset);
	}//end testANegativeOffsetStartsFromTheBeginning()

	/**
	 * Normalising returns a new query and leaves the original as it was.
	 *
	 * @return void
	 */
	public function testNormalisingLeavesTheOriginalUnchanged(): void {
		$original = new EntitySearchQuery(text: ' x ', limit: 500, offset: -1);

		$normalised = $original->normalised(maxLimit: 100);

		$this->assertNotSame($original, $normalised);
		$this->assertSame([' x ', '', '', 500, -1], $this->fields($original));
	}//end testNormalisingLeavesTheOriginalUnchanged()

	/**
	 * The query's fields in constructor order.
	 *
	 * @param EntitySearchQuery $query The query.
	 *
	 * @return array<int, string|int>
	 */
	private function fields(EntitySearchQuery $query): array {
		return [$query->text, $query->type, $query->category, $query->limit, $query->offset];
	}//end fields()
}//end class
