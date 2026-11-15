<?php

/**
 * Unit tests for ChartCodec on packages missing the closing elements it writes into.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\ChartCodec;
use OCA\Filinq\Service\Editing\PackagePartIo;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

/**
 * A package whose parts lack the element a chart is appended into is refused, not patched blindly.
 *
 * @covers \OCA\Filinq\Service\Editing\ChartCodec
 * @uses   \OCA\Filinq\Service\Editing\PackagePartIo
 */
class ChartCodecCorruptPackageTest extends TestCase {

	/**
	 * A valid chart definition.
	 *
	 * @var array<string, mixed>
	 */
	private const CHART = [
		'type' => 'bar',
		'title' => 'Budget',
		'categories' => ['Q1', 'Q2'],
		'series' => [['name' => 'Spend', 'values' => [10, 20]]],
	];

	/**
	 * A docx with overridable parts.
	 *
	 * @param array<string, string> $override Parts to replace.
	 *
	 * @return string The package bytes.
	 */
	private function docx(array $override = []): string {
		$entries = array_merge(
			[
				'[Content_Types].xml' => '<?xml version="1.0"?><Types xmlns="urn:ct"><Default Extension="xml" ContentType="application/xml"/></Types>',
				'word/document.xml' => '<?xml version="1.0"?><w:document xmlns:w="urn:w"><w:body>'
					. '<w:p><w:r><w:t>Intro</w:t></w:r></w:p></w:body></w:document>',
				'word/_rels/document.xml.rels' => '<?xml version="1.0"?><Relationships xmlns="urn:r"></Relationships>',
			],
			$override
		);

		$path = tempnam(sys_get_temp_dir(), 'chartbad');
		$zip = new ZipArchive();
		$zip->open($path, ZipArchive::OVERWRITE);
		foreach ($entries as $name => $contents) {
			$zip->addFromString($name, $contents);
		}

		$zip->close();
		$bytes = (string)file_get_contents($path);
		unlink($path);

		return $bytes;
	}//end docx()

	/**
	 * Embed and return the thrown message.
	 *
	 * @param string $bytes The package.
	 *
	 * @return string The exception message.
	 */
	private function refusal(string $bytes): string {
		try {
			(new ChartCodec())->embedChart($bytes, 'docx', self::CHART);
		} catch (RuntimeException $e) {
			return $e->getMessage();
		}

		$this->fail('A corrupt package must be refused.');
	}//end refusal()

	/**
	 * Without a closing Types element there is nowhere to declare the chart's content type.
	 *
	 * @return void
	 */
	public function testContentTypesWithoutClosingElementIsRefused(): void {
		$this->assertSame(
			'[Content_Types].xml has no closing Types element; the package may be corrupt.',
			$this->refusal($this->docx(['[Content_Types].xml' => '<Types xmlns="urn:ct"/>']))
		);
	}//end testContentTypesWithoutClosingElementIsRefused()

	/**
	 * Without a closing Relationships element the chart relationship cannot be added.
	 *
	 * @return void
	 */
	public function testRelationshipsWithoutClosingElementAreRefused(): void {
		$this->assertSame(
			'document.xml.rels has no closing Relationships element.',
			$this->refusal($this->docx(['word/_rels/document.xml.rels' => '<Relationships xmlns="urn:r"/>']))
		);
	}//end testRelationshipsWithoutClosingElementAreRefused()

	/**
	 * Appending at the end needs a closing body element.
	 *
	 * @return void
	 */
	public function testDocumentWithoutClosingBodyIsRefused(): void {
		$this->assertSame(
			'word/document.xml has no closing body element.',
			$this->refusal($this->docx(['word/document.xml' => '<w:document xmlns:w="urn:w"><w:body/></w:document>']))
		);
	}//end testDocumentWithoutClosingBodyIsRefused()

	/**
	 * An untitled chart's drawing is still named, so the suite can list it.
	 *
	 * @return void
	 */
	public function testAnUntitledChartDrawingIsNamedChart(): void {
		$chart = self::CHART;
		$chart['title'] = '';

		$result = (new ChartCodec())->embedChart($this->docx(), 'docx', $chart);
		$document = (new PackagePartIo())->readPart(packageBytes: $result['bytes'], part: 'word/document.xml');

		$this->assertSame('rId1', $result['relationshipId']);
		$this->assertStringContainsString('<wp:docPr id="1" name="Chart"/>', $document);
		$this->assertStringNotContainsString('<c:title>', (new PackagePartIo())->readPart(packageBytes: $result['bytes'], part: $result['chartPart']));
	}//end testAnUntitledChartDrawingIsNamedChart()
}//end class
