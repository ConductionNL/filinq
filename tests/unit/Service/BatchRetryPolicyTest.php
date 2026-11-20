<?php

/**
 * Unit tests for BatchRetryPolicy: lock misses are retried, bounded.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-resource-guards-on-extractconvert-backends-req-ddras-008
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\BatchRetryPolicy;
use OCP\Lock\LockedException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Which failures are lock misses, and what a retry does to the entry.
 *
 * @covers \OCA\Filinq\Service\BatchRetryPolicy
 * @uses   \OCA\Filinq\Exception\ConversionFailedException
 */
class BatchRetryPolicyTest extends TestCase {

	/**
	 * The attempts a LibreOffice lock miss records.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function lockAttempts(): array {
		return [['name' => 'libreoffice', 'reason' => 'could not acquire soffice:headless:convert lock: held']];

	}//end lockAttempts()

	/**
	 * A conversion failure naming the lock, also when wrapped, is a lock miss; others are not.
	 *
	 * @return void
	 */
	public function testLockMissesAreRecognisedThroughTheChain(): void {
		$lock = new ConversionFailedException(message: 'x', attempts: self::lockAttempts());

		$this->assertTrue((new BatchRetryPolicy())->isLockMiss(error: $lock));
		$this->assertTrue((new BatchRetryPolicy())->isLockMiss(error: new RuntimeException('wrapped', 0, $lock)));
		$this->assertTrue((new BatchRetryPolicy())->isLockMiss(error: new LockedException('soffice')));
		$this->assertFalse((new BatchRetryPolicy())->isLockMiss(error: new ConversionFailedException(message: 'x', attempts: [['reason' => 'unsupported']])));
		$this->assertFalse((new BatchRetryPolicy())->isLockMiss(error: new RuntimeException('unreadable')));

	}//end testLockMissesAreRecognisedThroughTheChain()

	/**
	 * Only a failed entry with the lock in its attempts is a lock miss.
	 *
	 * @return void
	 */
	public function testAnEntryIsALockMissOnlyWhenFailedOnTheLock(): void {
		$this->assertTrue((new BatchRetryPolicy())->entryIsLockMiss(entry: ['status' => 'error', 'conversionAttempts' => self::lockAttempts()]));
		$this->assertFalse((new BatchRetryPolicy())->entryIsLockMiss(entry: ['status' => 'anonymized', 'conversionAttempts' => self::lockAttempts()]));
		$this->assertFalse((new BatchRetryPolicy())->entryIsLockMiss(entry: ['status' => 'error', 'error' => 'other']));

	}//end testAnEntryIsALockMissOnlyWhenFailedOnTheLock()

	/**
	 * Within the bound the file is queued again; past it, it is an error with the lock category.
	 *
	 * @return void
	 */
	public function testRetriesAreBounded(): void {
		$entry = ['status' => 'error', 'error' => 'held', 'conversionAttempts' => self::lockAttempts(), 'retryCount' => 2];

		$queued = (new BatchRetryPolicy())->retryOrFail(entry: $entry, queueStatus: 'extracted', message: 'held');
		$this->assertSame('extracted', $queued['status']);
		$this->assertSame(3, $queued['retryCount']);
		$this->assertNull($queued['error']);
		$this->assertArrayNotHasKey('conversionAttempts', $queued);

		$failed = (new BatchRetryPolicy())->retryOrFail(entry: $queued, queueStatus: 'extracted', message: 'held');
		$this->assertSame('error', $failed['status']);
		$this->assertSame(4, $failed['retryCount']);
		$this->assertSame('held', $failed['error']);
		$this->assertSame(BatchRetryPolicy::LOCK_CATEGORY, $failed['errorCategory']);

	}//end testRetriesAreBounded()
}//end class
