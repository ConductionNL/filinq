<?php

/**
 * Unit tests for ContractDocumentText.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Contract
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

namespace OCA\Filinq\Tests\Unit\Service\Contract;

use OCA\Filinq\Service\Contract\ContractDocumentText;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use PHPUnit\Framework\TestCase;

/**
 * Which documents yield text, and that every failure yields none.
 *
 * @covers \OCA\Filinq\Service\Contract\ContractDocumentText
 */
class ContractDocumentTextTest extends TestCase {

	/**
	 * The reader over a user folder that answers getById with the given nodes.
	 *
	 * @param array<int, mixed> $nodes What getById(42) returns for alice.
	 *
	 * @return ContractDocumentText
	 */
	private function reader(array $nodes): ContractDocumentText {
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->with(42)->willReturn($nodes);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->with('alice')->willReturn($folder);

		return new ContractDocumentText(rootFolder: $root);

	}//end reader()

	/**
	 * A file with the given type, size and content.
	 *
	 * @param string          $mime    The MIME type.
	 * @param int             $size    The size in bytes.
	 * @param string|\Throwable $content The content, or what reading it throws.
	 *
	 * @return File
	 */
	private function file(string $mime, int $size = 100, string|\Throwable $content = 'Looptijd: 24 maanden'): File {
		$file = $this->createMock(File::class);
		$file->method('getMimeType')->willReturn($mime);
		$file->method('getSize')->willReturn($size);
		if ($content instanceof \Throwable) {
			$file->method('getContent')->willThrowException($content);
		} else {
			$file->method('getContent')->willReturn($content);
		}

		return $file;

	}//end file()

	/**
	 * A text file is read as it is.
	 *
	 * @return void
	 */
	public function testATextFileIsReadAsItIs(): void {
		$this->assertSame('Looptijd: 24 maanden', $this->reader([$this->file(mime: 'text/plain')])->textOf(userId: 'alice', fileId: 42));
		$this->assertSame('Looptijd: 24 maanden', $this->reader([$this->file(mime: 'text/markdown')])->textOf(userId: 'alice', fileId: 42));

	}//end testATextFileIsReadAsItIs()

	/**
	 * A file over 2 MB is not read at all.
	 *
	 * @return void
	 */
	public function testAFileOverTheLimitYieldsNoText(): void {
		$file = $this->file(mime: 'text/plain', size: 2000001);
		$file->expects($this->never())->method('getContent');

		$this->assertSame('', $this->reader([$file])->textOf(userId: 'alice', fileId: 42));
		$this->assertSame('Looptijd: 24 maanden', $this->reader([$this->file(mime: 'text/plain', size: 2000000)])->textOf(userId: 'alice', fileId: 42));

	}//end testAFileOverTheLimitYieldsNoText()

	/**
	 * A missing node, a folder, or a user folder that cannot be opened yields no text.
	 *
	 * @return void
	 */
	public function testNoFileYieldsNoText(): void {
		$this->assertSame('', $this->reader([])->textOf(userId: 'alice', fileId: 42));
		$this->assertSame('', $this->reader([$this->createMock(Folder::class)])->textOf(userId: 'alice', fileId: 42));

		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willThrowException(new NotPermittedException('no such user'));
		$this->assertSame('', (new ContractDocumentText(rootFolder: $root))->textOf(userId: 'ghost', fileId: 42));

	}//end testNoFileYieldsNoText()

	/**
	 * A document type that is neither text nor PDF yields no text and is not read.
	 *
	 * @return void
	 */
	public function testAnOtherTypeIsNotRead(): void {
		$file = $this->file(mime: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		$file->expects($this->never())->method('getContent');

		$this->assertSame('', $this->reader([$file])->textOf(userId: 'alice', fileId: 42));

	}//end testAnOtherTypeIsNotRead()

	/**
	 * A file whose content cannot be read yields no text rather than an error.
	 *
	 * @return void
	 */
	public function testAnUnreadableFileYieldsNoText(): void {
		$this->assertSame('', $this->reader([$this->file(mime: 'text/plain', content: new NotFoundException('gone'))])->textOf(userId: 'alice', fileId: 42));
		$this->assertSame('', $this->reader([$this->file(mime: 'application/pdf', content: new NotFoundException('gone'))])->textOf(userId: 'alice', fileId: 42));

	}//end testAnUnreadableFileYieldsNoText()

	/**
	 * A PDF whose bytes cannot be converted yields no text, and leaves no
	 * temporary file behind.
	 *
	 * @return void
	 */
	public function testAnUnconvertiblePdfYieldsNoText(): void {
		$before = glob(sys_get_temp_dir() . '/filinq_contract_*') ?: [];

		$this->assertSame('', $this->reader([$this->file(mime: 'application/pdf', content: 'not a pdf')])->textOf(userId: 'alice', fileId: 42));

		$this->assertSame($before, glob(sys_get_temp_dir() . '/filinq_contract_*') ?: []);

	}//end testAnUnconvertiblePdfYieldsNoText()
}//end class
