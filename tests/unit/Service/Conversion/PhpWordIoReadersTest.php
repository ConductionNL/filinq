<?php

/**
 * Unit tests for the PhpWord readers PhpWordIo opens documents with.
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

use OCA\Filinq\Service\Conversion\PhpWordIo;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Reader\MsDoc;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Each reader name opens its own format.
 *
 * @covers \OCA\Filinq\Service\Conversion\PhpWordIo
 */
class PhpWordIoReadersTest extends TestCase {

	/**
	 * Temp files written by the test.
	 *
	 * @var array<int, string>
	 */
	private array $paths = [];

	/**
	 * Remove the temp files.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		foreach ($this->paths as $path) {
			@unlink($path);
		}

		parent::tearDown();
	}//end tearDown()

	/**
	 * Save a one-paragraph document with the given PhpWord writer.
	 *
	 * @param string $writer The writer name.
	 * @param string $text   The paragraph.
	 *
	 * @return string The path written.
	 */
	private function saved(string $writer, string $text): string {
		$document = new PhpWord();
		$document->addSection()->addText($text);
		$path = (string) tempnam(sys_get_temp_dir(), 'pwio_');
		$this->paths[] = $path;
		IOFactory::createWriter($document, $writer)->save($path);

		return $path;
	}//end saved()

	/**
	 * An OpenDocument text file is read by the ODText reader.
	 *
	 * @return void
	 */
	public function testTheOdTextReaderReadsAnOdt(): void {
		$io = new PhpWordIo();
		$document = $io->load(path: $this->saved(writer: 'ODText', text: 'Besluit op bezwaar'), readerName: 'ODText');

		self::assertStringContainsString('Besluit op bezwaar', $io->toHtml(document: $document));
	}//end testTheOdTextReaderReadsAnOdt()

	/**
	 * An RTF file is read by the RTF reader.
	 *
	 * @return void
	 */
	public function testTheRtfReaderReadsAnRtf(): void {
		$io = new PhpWordIo();
		$document = $io->load(path: $this->saved(writer: 'RTF', text: 'Ontvangstbevestiging'), readerName: 'RTF');

		self::assertStringContainsString('Ontvangstbevestiging', $io->toHtml(document: $document));
	}//end testTheRtfReaderReadsAnRtf()

	/**
	 * The MsDoc name selects the legacy Word reader.
	 *
	 * @return void
	 */
	public function testTheMsDocNameSelectsTheLegacyWordReader(): void {
		$createReader = new ReflectionMethod(PhpWordIo::class, 'createReader');

		self::assertInstanceOf(MsDoc::class, $createReader->invoke(new PhpWordIo(), 'MsDoc'));
	}//end testTheMsDocNameSelectsTheLegacyWordReader()
}//end class
