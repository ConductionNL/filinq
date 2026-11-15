<?php

/**
 * Unit tests for ObjectResultExtractor
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use ArrayIterator;
use OCA\Filinq\Service\ObjectResultExtractor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Every result shape OpenRegister hands back becomes plain arrays; what
 * cannot be read is dropped, never invented.
 */
#[CoversClass(ObjectResultExtractor::class)]
class ObjectResultExtractorTest extends TestCase {

	/**
	 * The subject.
	 *
	 * @var ObjectResultExtractor
	 */
	private ObjectResultExtractor $extractor;

	/**
	 * Build the subject.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->extractor = new ObjectResultExtractor();
	}//end setUp()

	/**
	 * An entity-like object exposing getObject() and getUuid().
	 *
	 * @param mixed       $payload The object payload.
	 * @param string|null $uuid    The uuid.
	 *
	 * @return object
	 */
	private function entity(mixed $payload, ?string $uuid): object {
		return new class($payload, $uuid) {
			public function __construct(private mixed $payload, private ?string $uuid) {
			}

			public function getObject(): mixed {
				return $this->payload;
			}

			public function getUuid(): ?string {
				return $this->uuid;
			}
		};
	}//end entity()

	/**
	 * A JsonSerializable object.
	 *
	 * @param mixed $payload What jsonSerialize returns.
	 *
	 * @return object
	 */
	private function serializable(mixed $payload): object {
		return new class($payload) {
			public function __construct(private mixed $payload) {
			}

			public function jsonSerialize(): mixed {
				return $this->payload;
			}
		};
	}//end serializable()

	/**
	 * A paginated envelope yields its results; unusable rows are dropped.
	 *
	 * @return void
	 */
	public function testAPaginatedEnvelopeYieldsItsResults(): void {
		$rows = $this->extractor->extractRows(
			result: ['results' => [['a' => 1], 'scalar', null, ['b' => 2]], 'total' => 4]
		);

		$this->assertSame([['a' => 1], ['b' => 2]], $rows);
	}//end testAPaginatedEnvelopeYieldsItsResults()

	/**
	 * A plain list and a Traversable are both read.
	 *
	 * @return void
	 */
	public function testPlainListsAndTraversablesAreRead(): void {
		$this->assertSame([['x' => 1]], $this->extractor->extractRows(result: [['x' => 1]]));
		$this->assertSame([['y' => 2]], $this->extractor->extractRows(result: new ArrayIterator([['y' => 2]])));
	}//end testPlainListsAndTraversablesAreRead()

	/**
	 * A scalar or null result yields no rows.
	 *
	 * @return void
	 */
	public function testANonIterableResultYieldsNothing(): void {
		$this->assertSame([], $this->extractor->extractRows(result: null));
		$this->assertSame([], $this->extractor->extractRows(result: 'oops'));
		$this->assertNull($this->extractor->firstRow(result: 42));
	}//end testANonIterableResultYieldsNothing()

	/**
	 * An entity gains an @self envelope carrying its uuid.
	 *
	 * @return void
	 */
	public function testAnEntityGainsASelfEnvelope(): void {
		$rows = $this->extractor->extractRows(result: [$this->entity(['title' => 'T'], 'u-1')]);

		$this->assertSame([['title' => 'T', '@self' => ['id' => 'u-1']]], $rows);
	}//end testAnEntityGainsASelfEnvelope()

	/**
	 * An existing @self envelope is kept as is, and a null uuid adds none.
	 *
	 * @return void
	 */
	public function testAnExistingEnvelopeOrMissingUuidIsLeftAlone(): void {
		$withSelf = $this->entity(['title' => 'T', '@self' => ['id' => 'orig']], 'u-2');
		$noUuid = $this->entity(['title' => 'N'], null);

		$this->assertSame(['title' => 'T', '@self' => ['id' => 'orig']], $this->extractor->extractRow(candidate: $withSelf));
		$this->assertSame(['title' => 'N'], $this->extractor->extractRow(candidate: $noUuid));
	}//end testAnExistingEnvelopeOrMissingUuidIsLeftAlone()

	/**
	 * An object with getObject() but no getUuid() keeps its payload unchanged.
	 *
	 * @return void
	 */
	public function testAnAccessorWithoutUuidKeepsThePayload(): void {
		$candidate = new class {
			public function getObject(): array {
				return ['k' => 'v'];
			}
		};

		$this->assertSame(['k' => 'v'], $this->extractor->extractRow(candidate: $candidate));
	}//end testAnAccessorWithoutUuidKeepsThePayload()

	/**
	 * A non-array getObject() falls through to jsonSerialize().
	 *
	 * @return void
	 */
	public function testANonArrayAccessorFallsBackToJsonSerialize(): void {
		$candidate = new class {
			public function getObject(): string {
				return 'not an array';
			}

			public function jsonSerialize(): array {
				return ['from' => 'json'];
			}
		};

		$this->assertSame(['from' => 'json'], $this->extractor->extractRow(candidate: $candidate));
	}//end testANonArrayAccessorFallsBackToJsonSerialize()

	/**
	 * Objects that serialise to a non-array, or expose nothing, are dropped.
	 *
	 * @return void
	 */
	public function testUnreadableObjectsAreDropped(): void {
		$this->assertSame([], $this->extractor->extractRow(candidate: $this->serializable('str')));
		$this->assertSame([], $this->extractor->extractRow(candidate: new \stdClass()));
		$this->assertSame([], $this->extractor->extractRow(candidate: 7));
		$this->assertSame(['ok' => true], $this->extractor->extractRow(candidate: $this->serializable(['ok' => true])));
	}//end testUnreadableObjectsAreDropped()

	/**
	 * firstRow skips unusable candidates and returns the first readable one.
	 *
	 * @return void
	 */
	public function testFirstRowSkipsUnusableCandidates(): void {
		$first = $this->extractor->firstRow(
			result: ['results' => [null, new \stdClass(), ['id' => 'first'], ['id' => 'second']]]
		);

		$this->assertSame(['id' => 'first'], $first);
		$this->assertNull($this->extractor->firstRow(result: ['results' => [null, 'x']]));
	}//end testFirstRowSkipsUnusableCandidates()
}//end class
