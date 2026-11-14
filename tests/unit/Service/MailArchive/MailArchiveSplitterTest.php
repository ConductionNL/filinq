<?php

/**
 * Unit tests for MailArchiveSplitter
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
use OCA\Filinq\Service\MailArchive\MailArchiveRefused;
use OCA\Filinq\Service\MailArchive\MailArchiveSplitter;
use OCA\Filinq\Service\MailArchive\ReadpstCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * MBOX, ZIP and a converted PST split into messages, in resumable units.
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#2-1
 */
class MailArchiveSplitterTest extends TestCase {

	/**
	 * The scratch directory of this test.
	 *
	 * @var string
	 */
	private string $dir;

	/**
	 * The PST converter.
	 *
	 * @var ReadpstCommand&MockObject
	 */
	private ReadpstCommand $readpst;

	/**
	 * Make a scratch directory.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->dir = sys_get_temp_dir() . '/filinq-mail-archive-' . bin2hex(random_bytes(6));
		mkdir($this->dir);
		$this->readpst = $this->createMock(ReadpstCommand::class);

	}//end setUp()

	/**
	 * Remove the scratch directory.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$this->remove(path: $this->dir);

	}//end tearDown()

	/**
	 * Five messages, the third quoting a `From ` line in its body.
	 *
	 * @return string The MBOX path.
	 */
	private function mbox(): string {
		$body = '';
		for ($i = 1; $i <= 5; $i++) {
			$body .= "From jansen@example.org Mon Jan  5 10:00:0{$i} 2026\n";
			$body .= "Message-ID: <m{$i}@example.org>\nSubject: Bericht {$i}\n\n";
			$body .= ($i === 3) ? ">From the archive: hello\n>>From deeper\nFrom-like but not a separator\n" : "Tekst {$i}\n";
			$body .= "\n";
		}

		$path = $this->dir . '/mailbox-jansen.mbox';
		file_put_contents($path, $body);

		return $path;

	}//end mbox()

	/**
	 * Three `.eml` files in two folders and one `.msg`.
	 *
	 * @return string The ZIP path.
	 */
	private function zip(): string {
		$path = $this->dir . '/export.zip';
		$zip = new ZipArchive();
		$zip->open($path, ZipArchive::CREATE);
		$zip->addEmptyDir('Inbox');
		$zip->addFromString('Inbox/a.eml', "Subject: A\n\nA\n");
		$zip->addFromString('Projecten/Brug/b.eml', "Subject: B\n\nB\n");
		$zip->addFromString('c.EML', "Subject: C\n\nC\n");
		$zip->addFromString('Projecten/Brug/d.msg', 'binary');
		$zip->close();

		return $path;

	}//end zip()

	/**
	 * The splitter under test.
	 *
	 * @return MailArchiveSplitter
	 */
	private function splitter(): MailArchiveSplitter {
		return new MailArchiveSplitter($this->readpst);

	}//end splitter()

	/**
	 * Each format is recognised by its first bytes, whatever the name says.
	 *
	 * @return void
	 */
	public function testTheFormatIsDetectedFromTheContent(): void {
		$pst = $this->dir . '/a.bin';
		file_put_contents($pst, "!BDN\x00rest");
		$text = $this->dir . '/notes.txt';
		file_put_contents($text, 'Hello');

		$this->assertSame('mbox', $this->splitter()->detectFormat($this->mbox(), 'renamed.dat'));
		$this->assertSame('zip', $this->splitter()->detectFormat($this->zip(), 'export.zip'));
		$this->assertSame('pst', $this->splitter()->detectFormat($pst, 'archief.pst'));

		$this->expectException(MailArchiveRefused::class);
		$this->expectExceptionMessage('not an MBOX, PST or ZIP file');
		$this->splitter()->detectFormat($text, 'notes.mbox');

	}//end testTheFormatIsDetectedFromTheContent()

	/**
	 * An MBOX splits into its five messages; a quoted `>From ` loses one `>`.
	 *
	 * @return void
	 */
	public function testAnMboxSplitsOnFromLinesAndUnescapesQuotedOnes(): void {
		$unit = $this->splitter()->next(['format' => 'mbox', 'path' => $this->mbox()], new MailArchiveRead('mailbox-jansen', 50));

		$this->assertTrue($unit['done']);
		$this->assertCount(5, $unit['messages']);
		$this->assertSame('mailbox-jansen', $unit['messages'][0]['folder']);
		$this->assertSame('mailbox-jansen-000003.eml', $unit['messages'][2]['name']);
		$this->assertSame("Message-ID: <m1@example.org>\nSubject: Bericht 1\n\nTekst 1\n", $unit['messages'][0]['raw']);
		$this->assertSame(
			"Message-ID: <m3@example.org>\nSubject: Bericht 3\n\nFrom the archive: hello\n>From deeper\nFrom-like but not a separator\n",
			$unit['messages'][2]['raw']
		);

	}//end testAnMboxSplitsOnFromLinesAndUnescapesQuotedOnes()

	/**
	 * A unit stops at the limit and the next one resumes at the saved cursor.
	 *
	 * @return void
	 */
	public function testAnMboxResumesAtTheSavedCursor(): void {
		$source = ['format' => 'mbox', 'path' => $this->mbox()];

		$first = $this->splitter()->next($source, new MailArchiveRead('box', 2));
		$this->assertFalse($first['done']);
		$this->assertSame(['box-000001.eml', 'box-000002.eml'], array_column($first['messages'], 'name'));
		$this->assertSame(2, $first['cursor']['ordinal']);

		// A new splitter, as after a crash: only the saved cursor carries over.
		$second = $this->splitter()->next($source, (new MailArchiveRead('box', 2))->from($first['cursor']));
		$third = $this->splitter()->next($source, (new MailArchiveRead('box', 2))->from($second['cursor']));

		$this->assertSame("Message-ID: <m3@example.org>\nSubject: Bericht 3\n\nFrom the archive: hello\n>From deeper\nFrom-like but not a separator\n", $second['messages'][0]['raw']);
		$this->assertSame(['box-000005.eml'], array_column($third['messages'], 'name'));
		$this->assertTrue($third['done']);

	}//end testAnMboxResumesAtTheSavedCursor()

	/**
	 * ZIP entries become messages in their directory's folder; a `.msg` fails visibly.
	 *
	 * @return void
	 */
	public function testAZipYieldsItsEmlEntriesAndFailsAMsgVisibly(): void {
		$unit = $this->splitter()->next(['format' => 'zip', 'path' => $this->zip()], new MailArchiveRead('export', 50));

		$this->assertTrue($unit['done']);
		$this->assertSame(
			[
				['folder' => 'Inbox', 'name' => 'a.eml', 'raw' => "Subject: A\n\nA\n"],
				['folder' => 'Projecten/Brug', 'name' => 'b.eml', 'raw' => "Subject: B\n\nB\n"],
				['folder' => 'export', 'name' => 'c.EML', 'raw' => "Subject: C\n\nC\n"],
				['folder' => 'Projecten/Brug', 'name' => 'd.msg', 'reason' => 'unsupported-format'],
			],
			$unit['messages']
		);

	}//end testAZipYieldsItsEmlEntriesAndFailsAMsgVisibly()

	/**
	 * A ZIP unit resumes at the next entry index.
	 *
	 * @return void
	 */
	public function testAZipResumesAtTheNextEntry(): void {
		$source = ['format' => 'zip', 'path' => $this->zip()];
		$first = $this->splitter()->next($source, new MailArchiveRead('export', 1));
		$rest = $this->splitter()->next($source, (new MailArchiveRead('export', 10))->from($first['cursor']));

		$this->assertSame(['a.eml'], array_column($first['messages'], 'name'));
		$this->assertFalse($first['done']);
		$this->assertSame(['b.eml', 'c.EML', 'd.msg'], array_column($rest['messages'], 'name'));
		$this->assertTrue($rest['done']);

	}//end testAZipResumesAtTheNextEntry()

	/**
	 * The folders an archivist maps, with their counts.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#2-2
	 */
	public function testFoldersAreListedWithTheirMessageCounts(): void {
		$this->assertSame(
			['Inbox' => 1, 'Projecten/Brug' => 2, 'export' => 1],
			$this->splitter()->folders(['format' => 'zip', 'path' => $this->zip()], 'export')
		);
		$this->assertSame(
			['mailbox-jansen' => 5],
			$this->splitter()->folders(['format' => 'mbox', 'path' => $this->mbox()], 'mailbox-jansen')
		);

	}//end testFoldersAreListedWithTheirMessageCounts()

	/**
	 * Without readpst a PST is refused at once, with the message the archivist sees.
	 *
	 * @return void
	 */
	public function testAPstWithoutReadpstIsRefused(): void {
		$this->readpst->method('isAvailable')->willReturn(false);
		$this->readpst->expects($this->never())->method('convert');

		$this->assertFalse($this->splitter()->canReadPst());
		$this->expectException(MailArchiveRefused::class);
		$this->expectExceptionMessage('readpst is not installed on this server');

		$this->splitter()->prepare($this->dir . '/archief.pst', 'pst', $this->dir . '/out');

	}//end testAPstWithoutReadpstIsRefused()

	/**
	 * A failing conversion is refused rather than read as an empty archive.
	 *
	 * @return void
	 */
	public function testAFailedConversionIsRefused(): void {
		$this->readpst->method('isAvailable')->willReturn(true);
		$this->readpst->method('convert')->willReturn(false);

		$this->expectException(MailArchiveRefused::class);
		$this->expectExceptionMessage('readpst could not read this file');

		$this->splitter()->prepare($this->dir . '/archief.pst', 'pst', $this->dir . '/out');

	}//end testAFailedConversionIsRefused()

	/**
	 * A converted PST is read from its output directory, the path naming the folder.
	 *
	 * @return void
	 */
	public function testAConvertedPstIsReadFromItsDirectory(): void {
		$out = $this->dir . '/out';
		mkdir($out);
		$this->readpst->method('isAvailable')->willReturn(true);
		$this->readpst->expects($this->once())->method('convert')->with($this->dir . '/archief.pst', $out)->willReturnCallback(
			static function (string $pstPath, string $outputDir): bool {
				mkdir($outputDir . '/Postvak IN/Projecten', 0777, true);
				file_put_contents($outputDir . '/Postvak IN/1.eml', "Subject: 1\n\n1\n");
				file_put_contents($outputDir . '/Postvak IN/Projecten/2.eml', "Subject: 2\n\n2\n");
				return true;
			}
		);

		$source = $this->splitter()->prepare($this->dir . '/archief.pst', 'pst', $out);
		$this->assertSame(['format' => 'dir', 'path' => $out], $source);

		$unit = $this->splitter()->next($source, new MailArchiveRead('archief', 1));
		$this->assertSame([['folder' => 'Postvak IN', 'name' => '1.eml', 'raw' => "Subject: 1\n\n1\n"]], $unit['messages']);
		$rest = $this->splitter()->next($source, (new MailArchiveRead('archief', 5))->from($unit['cursor']));
		$this->assertSame('Postvak IN/Projecten', $rest['messages'][0]['folder']);
		$this->assertTrue($rest['done']);

	}//end testAConvertedPstIsReadFromItsDirectory()

	/**
	 * MBOX and ZIP need no converter.
	 *
	 * @return void
	 */
	public function testMboxAndZipArePassedThrough(): void {
		$this->readpst->expects($this->never())->method('isAvailable');

		$this->assertSame(['format' => 'zip', 'path' => '/x.zip'], $this->splitter()->prepare('/x.zip', 'zip', '/tmp'));

	}//end testMboxAndZipArePassedThrough()

	/**
	 * An archive that cannot be opened is refused, not read as empty.
	 *
	 * @return void
	 */
	public function testAnUnreadableArchiveIsRefused(): void {
		$this->expectException(MailArchiveRefused::class);
		$this->expectExceptionMessage('the archive could not be opened');

		$this->splitter()->next(['format' => 'zip', 'path' => $this->dir . '/missing.zip'], new MailArchiveRead('x', 5));

	}//end testAnUnreadableArchiveIsRefused()

	/**
	 * Remove a directory tree.
	 *
	 * @param string $path The directory.
	 *
	 * @return void
	 */
	private function remove(string $path): void {
		if (is_dir($path) === false) {
			return;
		}

		foreach ((array) scandir($path) as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}

			$full = $path . '/' . $entry;
			is_dir($full) === true ? $this->remove(path: $full) : unlink($full);
		}

		rmdir($path);

	}//end remove()
}//end class
