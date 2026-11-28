<?php

/**
 * Unit tests for PhpWordIo.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use InvalidArgumentException;
use OCA\Filinq\Service\Conversion\PhpWordIo;
use PhpOffice\PhpWord\PhpWord;
use PHPUnit\Framework\TestCase;

/**
 * Reader selection and HTML output.
 *
 * @covers \OCA\Filinq\Service\Conversion\PhpWordIo
 */
class PhpWordIoTest extends TestCase {

	/**
	 * An unknown reader name is refused with the name in the message.
	 *
	 * @return void
	 */
	public function testUnknownReaderIsRefused(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('"PDF" is not a valid PhpWord reader.');

		(new PhpWordIo())->load(path: '/nonexistent', readerName: 'PDF');
	}//end testUnknownReaderIsRefused()

	/**
	 * An HTML file loads through the HTML reader and its text survives the HTML writer.
	 *
	 * @return void
	 */
	public function testHtmlRoundTrip(): void {
		$path = tempnam(sys_get_temp_dir(), 'phpwordio');
		file_put_contents($path, '<html><body><p>Hallo wereld</p></body></html>');

		try {
			$io = new PhpWordIo();
			$document = $io->load(path: $path, readerName: 'HTML');
			$html = $io->toHtml(document: $document);
		} finally {
			unlink($path);
		}

		$this->assertInstanceOf(PhpWord::class, $document);
		$this->assertStringContainsString('Hallo wereld', $html);
		$this->assertStringContainsString('<html', $html);
	}//end testHtmlRoundTrip()

	/**
	 * A document built in memory is written as HTML.
	 *
	 * @return void
	 */
	public function testToHtmlWritesSectionText(): void {
		$document = new PhpWord();
		$document->addSection()->addText('Besluit op bezwaar');

		$this->assertStringContainsString('Besluit op bezwaar', (new PhpWordIo())->toHtml(document: $document));
	}//end testToHtmlWritesSectionText()
}//end class
