<?php

/**
 * Unit tests for SessionRowMatcher.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use ArrayIterator;
use JsonSerializable;
use OCA\Filinq\Service\Signing\SessionRowMatcher;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Finding the session row for an external id among mixed result shapes.
 *
 * @covers \OCA\Filinq\Service\Signing\SessionRowMatcher
 */
class SessionRowMatcherTest extends TestCase {

	/**
	 * Array rows match on their externalId, first match wins.
	 *
	 * @return void
	 */
	public function testMatchesArrayRows(): void {
		$rows = [
			['externalId' => 'a', 'n' => 1],
			['externalId' => 'b', 'n' => 2],
			['externalId' => 'b', 'n' => 3],
		];

		$this->assertSame(['externalId' => 'b', 'n' => 2], (new SessionRowMatcher())->matching(results: $rows, externalId: 'b'));
	}//end testMatchesArrayRows()

	/**
	 * Objects are read through jsonSerialize first, then getObject; others are skipped.
	 *
	 * @return void
	 */
	public function testReadsObjectsThroughAccessors(): void {
		$serialisable = new class implements JsonSerializable {
			/**
			 * Serialise.
			 *
			 * @return array<string, string>
			 */
			public function jsonSerialize(): array {
				return ['externalId' => 'json'];
			}//end jsonSerialize()
		};
		$entity = new class {
			/**
			 * The wrapped object.
			 *
			 * @return array<string, string>
			 */
			public function getObject(): array {
				return ['externalId' => 'entity', 'status' => 'open'];
			}//end getObject()
		};
		$results = new ArrayIterator(['scalar', null, new stdClass(), $serialisable, $entity]);

		$matcher = new SessionRowMatcher();
		$this->assertSame(['externalId' => 'json'], $matcher->matching(results: $results, externalId: 'json'));
		$this->assertSame(['externalId' => 'entity', 'status' => 'open'], $matcher->matching(results: [$serialisable, $entity], externalId: 'entity'));
	}//end testReadsObjectsThroughAccessors()

	/**
	 * An accessor that does not return an array falls through to the next one.
	 *
	 * @return void
	 */
	public function testNonArrayAccessorFallsThrough(): void {
		$entry = new class {
			/**
			 * Serialise to a scalar.
			 *
			 * @return string
			 */
			public function jsonSerialize(): string {
				return 'x';
			}//end jsonSerialize()

			/**
			 * The wrapped object.
			 *
			 * @return array<string, string>
			 */
			public function getObject(): array {
				return ['externalId' => 'z'];
			}//end getObject()
		};
		$bad = new class {
			/**
			 * Not an array.
			 *
			 * @return int
			 */
			public function getObject(): int {
				return 5;
			}//end getObject()
		};

		$matcher = new SessionRowMatcher();
		$this->assertSame(['externalId' => 'z'], $matcher->matching(results: [$bad, $entry], externalId: 'z'));
	}//end testNonArrayAccessorFallsThrough()

	/**
	 * No match, a missing key or a loosely equal id give null.
	 *
	 * @return void
	 */
	public function testNoMatchIsNull(): void {
		$matcher = new SessionRowMatcher();

		$this->assertNull($matcher->matching(results: [['externalId' => 1], ['other' => '1']], externalId: '1'));
		$this->assertNull($matcher->matching(results: [], externalId: 'a'));
	}//end testNoMatchIsNull()
}//end class
