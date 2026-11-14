<?php

/**
 * Unit tests for MailArchiveRead
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\MailArchive
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\MailArchive;

use OCA\Filinq\Service\MailArchive\MailArchiveRead;
use PHPUnit\Framework\TestCase;

/**
 * A read starts at the beginning with the bytes, and each change is a copy.
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#2-1
 */
class MailArchiveReadTest extends TestCase {

	/**
	 * A new read starts at the beginning, returns the bytes and keeps its folder and limit.
	 *
	 * @return void
	 */
	public function testANewReadStartsAtTheBeginningWithTheBytes(): void {
		$read = new MailArchiveRead('mailbox', 25);

		$this->assertSame('mailbox', $read->rootName);
		$this->assertSame(25, $read->limit);
		$this->assertSame([], $read->cursor());
		$this->assertTrue($read->withContent());
	}//end testANewReadStartsAtTheBeginningWithTheBytes()

	/**
	 * A limit below one reads one message.
	 *
	 * @return void
	 */
	public function testALimitBelowOneReadsOne(): void {
		$this->assertSame(1, (new MailArchiveRead('x', 0))->limit);
		$this->assertSame(1, (new MailArchiveRead('x', -5))->limit);
	}//end testALimitBelowOneReadsOne()

	/**
	 * from() and countingOnly() return changed copies and leave the original as it was.
	 *
	 * @return void
	 */
	public function testChangesAreCopies(): void {
		$read = new MailArchiveRead('box', 2);

		$next = $read->from(['offset' => 120, 'ordinal' => 2]);
		$counting = $next->countingOnly();

		$this->assertSame(['offset' => 120, 'ordinal' => 2], $next->cursor());
		$this->assertTrue($next->withContent());
		$this->assertSame([], $read->cursor());
		$this->assertFalse($counting->withContent());
		$this->assertSame(['offset' => 120, 'ordinal' => 2], $counting->cursor());
		$this->assertSame('box', $counting->rootName);
		$this->assertSame(2, $counting->limit);
	}//end testChangesAreCopies()
}//end class
