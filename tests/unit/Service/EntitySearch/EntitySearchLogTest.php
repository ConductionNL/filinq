<?php

/**
 * The rows EntitySearchLog writes and when it refuses.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\EntitySearch
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\EntitySearch;

use DateTime;
use DateTimeZone;
use OCA\Filinq\Exception\EntitySearchRefusedException;
use OCA\Filinq\Kernel\EntitySearchQuery;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\EntitySearch\EntitySearchLog;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * A search row carries a digest, never the text; a detail row the entity;
 * a store that throws or answers nothing refuses the lookup.
 *
 * @covers \OCA\Filinq\Service\EntitySearch\EntitySearchLog
 * @uses   \OCA\Filinq\Exception\EntitySearchRefusedException
 * @uses   \OCA\Filinq\Kernel\EntitySearchQuery
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EntitySearchLogTest extends TestCase {

	/**
	 * The saveObject calls.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $saved = [];

	/**
	 * A log over an object service that answers $answer, or throws it.
	 *
	 * @param mixed $answer What saveObject answers; a Throwable is thrown.
	 *
	 * @return EntitySearchLog The log.
	 */
	private function log(mixed $answer = ['id' => 1]): EntitySearchLog {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object = [], string $register = '', string $schema = '', ?string $uuid = null, bool $_rbac = true) use ($answer): mixed {
				$this->saved[] = ['object' => $object, 'register' => $register, 'schema' => $schema, '_rbac' => $_rbac];
				if ($answer instanceof \Throwable) {
					throw $answer;
				}

				return $answer;
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getDateTime')->willReturn(new DateTime('2026-11-13T10:20:30', new DateTimeZone('UTC')));

		return new EntitySearchLog($resolver, $time);

	}//end log()

	/**
	 * The digest is the sha256 of the trimmed, lowercased text; blank is empty.
	 *
	 * @return void
	 */
	public function testTheDigestNormalisesTheText(): void {
		$this->assertSame(hash('sha256', 'de vries'), EntitySearchLog::digest(query: "  De VRIES \n"));
		$this->assertSame(hash('sha256', 'émile'), EntitySearchLog::digest(query: 'ÉMILE'));
		$this->assertSame('', EntitySearchLog::digest(query: " \t "));
	}//end testTheDigestNormalisesTheText()

	/**
	 * A search row holds the digest, the cut filters and the count, written to
	 * the filinq register without RBAC.
	 *
	 * @return void
	 */
	public function testASearchRowHoldsTheDigestNotTheText(): void {
		$row = $this->log()->search(
			userId: 'petra',
			query: new EntitySearchQuery(text: 'Jan Jansen', type: str_repeat('T', 70), category: 'name', limit: 25, offset: 0),
			results: 4
		);

		$expected = [
			'action' => 'search',
			'queryDigest' => hash('sha256', 'jan jansen'),
			'typeFilter' => str_repeat('T', 64),
			'categoryFilter' => 'name',
			'resultCount' => 4,
			'performedBy' => 'petra',
			'performedAt' => '2026-11-13T10:20:30+00:00',
		];
		$this->assertSame($expected, $row);
		$this->assertCount(1, $this->saved);
		$this->assertSame(['object' => $expected, 'register' => 'filinq', 'schema' => 'entitySearchLog', '_rbac' => false], $this->saved[0]);
		$this->assertStringNotContainsString('Jansen', (string) json_encode($this->saved));
	}//end testASearchRowHoldsTheDigestNotTheText()

	/**
	 * A detail row holds the entity reference, cut to 64, and the occurrence
	 * count; the user id is cut to 255.
	 *
	 * @return void
	 */
	public function testADetailRowHoldsTheEntityAndCount(): void {
		$row = $this->log()->detail(userId: str_repeat('u', 300), entityUuid: str_repeat('e', 80), occurrences: 9);

		$this->assertSame(
			[
				'action' => 'detail',
				'entityRef' => str_repeat('e', 64),
				'occurrenceCount' => 9,
				'performedBy' => str_repeat('u', 255),
				'performedAt' => '2026-11-13T10:20:30+00:00',
			],
			$row
		);
		$this->assertSame($row, $this->saved[0]['object']);
	}//end testADetailRowHoldsTheEntityAndCount()

	/**
	 * A store that throws refuses with log_unavailable and names the cause.
	 *
	 * @return void
	 */
	public function testAThrowingStoreRefuses(): void {
		try {
			$this->log(answer: new RuntimeException('disk full'))->detail(userId: 'petra', entityUuid: 'e-1', occurrences: 1);
			$this->fail('The log took an entry its store refused.');
		} catch (EntitySearchRefusedException $e) {
			$this->assertSame(EntitySearchRefusedException::REASON_LOG_UNAVAILABLE, $e->getReason());
			$this->assertSame('The entity search log did not take the entry: disk full', $e->getMessage());
		}
	}//end testAThrowingStoreRefuses()

	/**
	 * A store that answers nothing refuses too.
	 *
	 * @return void
	 */
	public function testAStoreAnsweringNothingRefuses(): void {
		try {
			$this->log(answer: null)->search(
				userId: 'petra',
				query: new EntitySearchQuery(text: 'x', type: '', category: '', limit: 25, offset: 0),
				results: 0
			);
			$this->fail('The log reported an entry its store did not return.');
		} catch (EntitySearchRefusedException $e) {
			$this->assertSame(EntitySearchRefusedException::REASON_LOG_UNAVAILABLE, $e->getReason());
			$this->assertSame('The entity search log returned nothing for the entry.', $e->getMessage());
		}
	}//end testAStoreAnsweringNothingRefuses()
}//end class
