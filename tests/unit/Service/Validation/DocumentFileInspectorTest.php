<?php

/**
 * The file probes of document validation: safe name and mime reads, extension mismatches, encryption and the text-layer heuristic.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Validation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Validation;


use OCA\Filinq\Service\Validation\DocumentFileInspector;
use OCP\Files\File;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\Validation\DocumentFileInspector
 */
class DocumentFileInspectorTest extends TestCase {

	/**
	 * Build an inspector whose configured text-layer minimum is the given value.
	 *
	 * @param int $minimum The configured minimum characters per page.
	 *
	 * @return DocumentFileInspector
	 */
	private function inspector(int $minimum = 32): DocumentFileInspector {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueInt')->with('filinq', 'validation.text_layer_min_chars_per_page', 32)->willReturn($minimum);

		return new DocumentFileInspector(appConfig: $config);
	}//end inspector()

	/**
	 * A PDF body with the given page and text-run counts.
	 *
	 * @param int $pages The number of /Type /Page objects.
	 * @param int $runs  The number of Tj operators.
	 *
	 * @return string
	 */
	private static function pdf(int $pages, int $runs): string {
		return '%PDF-1.7 /Type /Pages '.str_repeat("/Type /Page\n", $pages).str_repeat("(x) Tj\n", $runs);
	}//end pdf()

	public function testNameAndMimeAreReadOrEmptyOnFailure(): void {
		$file = $this->createMock(File::class);
		$file->method('getMimeType')->willReturn('application/pdf');
		$file->method('getName')->willReturn('besluit.pdf');
		$broken = $this->createMock(File::class);
		$broken->method('getMimeType')->willThrowException(new RuntimeException('gone'));
		$broken->method('getName')->willThrowException(new RuntimeException('gone'));

		$inspector = $this->inspector();
		$this->assertSame(['application/pdf', 'besluit.pdf'], [$inspector->safeMimeType(file: $file), $inspector->safeName(file: $file)]);
		$this->assertSame(['', ''], [$inspector->safeMimeType(file: $broken), $inspector->safeName(file: $broken)]);
	}//end testNameAndMimeAreReadOrEmptyOnFailure()

	public function testExtensionMismatches(): void {
		$inspector = $this->inspector();

		$this->assertTrue($inspector->extensionMismatches(name: 'besluit.pdf', mime: 'text/plain'));
		$this->assertTrue($inspector->extensionMismatches(name: 'Brief.DOCX', mime: 'application/msword'));
		$this->assertFalse($inspector->extensionMismatches(name: 'Brief.DOCX', mime: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'));
		$this->assertFalse($inspector->extensionMismatches(name: 'notes.md', mime: 'text/plain'));
		$this->assertFalse($inspector->extensionMismatches(name: 'page.htm', mime: 'text/html'));
		$this->assertFalse($inspector->extensionMismatches(name: 'archive.tar.gz', mime: 'text/plain'), 'Unknown extensions are not judged.');
		$this->assertFalse($inspector->extensionMismatches(name: 'README', mime: 'text/plain'), 'No extension is not judged.');
		$this->assertFalse($inspector->extensionMismatches(name: 'besluit.pdf', mime: ''), 'An unknown mime is not judged.');
	}//end testExtensionMismatches()

	public function testEncryptionIsOnlyJudgedOnAPdf(): void {
		$inspector = $this->inspector();

		$this->assertTrue($inspector->isPdfEncrypted(content: '%PDF-1.7 trailer << /Encrypt 9 0 R >>'));
		$this->assertFalse($inspector->isPdfEncrypted(content: '%PDF-1.7 trailer << /Root 1 0 R >>'));
		$this->assertFalse($inspector->isPdfEncrypted(content: 'text mentioning /Encrypt'));
	}//end testEncryptionIsOnlyJudgedOnAPdf()

	public function testTheTextLayerIsJudgedPerPage(): void {
		$inspector = $this->inspector();

		// 4 runs * 8 = 32 per page: exactly the minimum, so present.
		$this->assertFalse($inspector->textLayerMissing(content: self::pdf(pages: 1, runs: 4)));
		$this->assertTrue($inspector->textLayerMissing(content: self::pdf(pages: 1, runs: 3)));
		// The same 4 runs over two pages is 16 per page.
		$this->assertTrue($inspector->textLayerMissing(content: self::pdf(pages: 2, runs: 4)));
		$this->assertFalse($inspector->textLayerMissing(content: self::pdf(pages: 2, runs: 8)));
	}//end testTheTextLayerIsJudgedPerPage()

	public function testAPdfWithoutPageObjectsCountsAsOnePage(): void {
		$this->assertFalse($this->inspector()->textLayerMissing(content: '%PDF-1.7 '.str_repeat('[(a)] TJ ', 4)));
		$this->assertTrue($this->inspector()->textLayerMissing(content: '%PDF-1.7 scanned image only'));
	}//end testAPdfWithoutPageObjectsCountsAsOnePage()

	public function testANonPdfIsNeverMissingATextLayer(): void {
		$this->assertFalse($this->inspector()->textLayerMissing(content: 'plain text without operators'));
	}//end testANonPdfIsNeverMissingATextLayer()

	public function testTheConfiguredMinimumAppliesAndANonPositiveOneFallsBack(): void {
		$this->assertFalse($this->inspector(minimum: 8)->textLayerMissing(content: self::pdf(pages: 1, runs: 1)));
		$this->assertTrue($this->inspector(minimum: 100)->textLayerMissing(content: self::pdf(pages: 1, runs: 12)));
		$this->assertTrue($this->inspector(minimum: 0)->textLayerMissing(content: self::pdf(pages: 1, runs: 3)));
		$this->assertFalse($this->inspector(minimum: -5)->textLayerMissing(content: self::pdf(pages: 1, runs: 4)));
	}//end testTheConfiguredMinimumAppliesAndANonPositiveOneFallsBack()
}//end class
