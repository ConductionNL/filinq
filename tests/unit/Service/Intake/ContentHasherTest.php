<?php

/**
 * Unit tests for ContentHasher.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Intake
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link https://conduction.nl
 *
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Intake;

use OCA\Filinq\Service\Intake\ContentHasher;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The SHA-256 of the bytes, or nothing; never a failed arrival.
 *
 * @covers \OCA\Filinq\Service\Intake\ContentHasher
 */
class ContentHasherTest extends TestCase {

	/**
	 * A hasher whose root folder answers the given node, or throws.
	 *
	 * @param mixed                $node   What getFirstNodeById answers; a Throwable is thrown.
	 * @param LoggerInterface|null $logger The logger.
	 *
	 * @return ContentHasher
	 */
	private function hasher(mixed $node, ?LoggerInterface $logger=null): ContentHasher {
		$root = $this->createMock(IRootFolder::class);
		if ($node instanceof \Throwable) {
			$root->method('getFirstNodeById')->willThrowException($node);
		} else {
			$root->method('getFirstNodeById')->with(12)->willReturn($node);
		}

		return new ContentHasher($root, $logger ?? $this->createMock(LoggerInterface::class));
	}//end hasher()

	/**
	 * A file over several chunks hashes to the SHA-256 of all its bytes, and its stream is closed.
	 *
	 * @return void
	 */
	public function testTheHashIsTheSha256OfTheBytes(): void {
		$bytes = str_repeat('Bezwaar parkeervergunning ', 90000);
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $bytes);
		rewind($stream);

		$file = $this->createMock(File::class);
		$file->method('fopen')->with('r')->willReturn($stream);

		self::assertSame(hash('sha256', $bytes), $this->hasher(node: $file)->hashOf(fileId: 12));
		self::assertFalse(is_resource($stream));
	}//end testTheHashIsTheSha256OfTheBytes()

	/**
	 * Two empty files hash alike; an empty file still has a hash.
	 *
	 * @return void
	 */
	public function testAnEmptyFileHasTheEmptyHash(): void {
		$file = $this->createMock(File::class);
		$file->method('fopen')->willReturn(fopen('php://memory', 'r'));

		self::assertSame(hash('sha256', ''), $this->hasher(node: $file)->hashOf(fileId: 12));
	}//end testAnEmptyFileHasTheEmptyHash()

	/**
	 * No file, a folder, an unopenable file and no id give no hash.
	 *
	 * @return void
	 */
	public function testWhatCannotBeReadHasNoHash(): void {
		self::assertNull($this->hasher(node: null)->hashOf(fileId: 12));
		self::assertNull($this->hasher(node: $this->createMock(Folder::class))->hashOf(fileId: 12));

		$closed = $this->createMock(File::class);
		$closed->method('fopen')->willReturn(false);
		self::assertNull($this->hasher(node: $closed)->hashOf(fileId: 12));

		$root = $this->createMock(IRootFolder::class);
		$root->expects($this->never())->method('getFirstNodeById');
		self::assertNull((new ContentHasher($root, $this->createMock(LoggerInterface::class)))->hashOf(fileId: 0));
	}//end testWhatCannotBeReadHasNoHash()

	/**
	 * A read that fails is logged and gives no hash, rather than failing the arrival.
	 *
	 * @return void
	 */
	public function testAFailedReadIsLoggedAndGivesNoHash(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning')->with(
			$this->stringContains('could not read the file'),
			$this->callback(static fn (array $context): bool => $context['fileId'] === 12 && $context['error'] === 'storage gone')
		);

		self::assertNull($this->hasher(node: new RuntimeException('storage gone'), logger: $logger)->hashOf(fileId: 12));
	}//end testAFailedReadIsLoggedAndGivesNoHash()
}//end class
