<?php

/**
 * Unit tests for ChartCodec.
 *
 * openspec/changes/document-chart-embedding.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\ChartCodec;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

/**
 * Chart embedding, checked on the five things that must agree.
 *
 * A corrupt `.docx` does not degrade — the suite refuses to open it — so most of
 * these assertions are about parts agreeing with each other rather than about
 * anything visible.
 */
class ChartCodecTest extends TestCase {

	/**
	 * The codec under test.
	 *
	 * @var ChartCodec
	 */
	private ChartCodec $codec;

	/**
	 * A valid chart definition.
	 *
	 * @var array
	 */
	private const CHART = [
		'type'       => 'bar',
		'title'      => 'Toekenning per programma',
		'categories' => ['Zorg', 'Onderwijs', 'Wonen'],
		'series'     => [['name' => 'Bedrag', 'values' => [120, 90, 45]]],
	];

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->codec = new ChartCodec();
	}//end setUp()

	/**
	 * Build a minimal but valid docx package.
	 *
	 * @param int $existingRelationships How many relationships the document already has.
	 *
	 * @return string The package bytes.
	 */
	private function docx(int $existingRelationships = 0): string {
		$rels = '';
		for ($i = 1; $i <= $existingRelationships; $i++) {
			$rels .= sprintf(
				'<Relationship Id="rId%d" Type="http://example.test/rel" Target="target%d.xml"/>',
				$i,
				$i
			);
		}

		$entries = [
			'[Content_Types].xml' => '<?xml version="1.0"?><Types '
				. 'xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
				. '<Default Extension="xml" ContentType="application/xml"/></Types>',
			'word/document.xml' => '<?xml version="1.0"?><w:document '
				. 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
				. '<w:p><w:r><w:t>Intro paragraph</w:t></w:r></w:p>'
				. '<w:p><w:r><w:t>Second paragraph</w:t></w:r></w:p>'
				. '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/></w:sectPr></w:body></w:document>',
			'word/_rels/document.xml.rels' => '<?xml version="1.0"?><Relationships '
				. 'xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels
				. '</Relationships>',
		];

		$path = tempnam(sys_get_temp_dir(), 'chart') . '.docx';
		$zip  = new ZipArchive();
		$zip->open($path, ZipArchive::CREATE);
		foreach ($entries as $name => $contents) {
			$zip->addFromString($name, $contents);
		}

		$zip->close();

		$bytes = (string)file_get_contents($path);
		unlink($path);

		return $bytes;
	}//end docx()

	/**
	 * Read one entry from a package.
	 *
	 * @param string $bytes The package bytes.
	 * @param string $name  The entry name.
	 *
	 * @return string The entry contents.
	 */
	private function entry(string $bytes, string $name): string {
		$path = tempnam(sys_get_temp_dir(), 'chartread') . '.docx';
		file_put_contents($path, $bytes);

		$zip = new ZipArchive();
		$zip->open($path);
		$contents = (string)$zip->getFromName($name);
		$zip->close();
		unlink($path);

		return $contents;
	}//end entry()

	/**
	 * REQ: the relationship id in the rels and in the drawing are THE SAME.
	 *
	 * If they disagree the suite cannot resolve the chart and refuses the file.
	 * This is the single most important assertion here.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function testTheRelationshipIdMatchesBetweenRelsAndDrawing(): void {
		$result = $this->codec->embedChart($this->docx(), 'docx', self::CHART);

		$relId = $result['relationshipId'];

		$this->assertStringContainsString(
			sprintf('Id="%s"', $relId),
			$this->entry($result['bytes'], 'word/_rels/document.xml.rels')
		);
		$this->assertStringContainsString(
			sprintf('r:id="%s"', $relId),
			$this->entry($result['bytes'], 'word/document.xml')
		);
	}//end testTheRelationshipIdMatchesBetweenRelsAndDrawing()

	/**
	 * REQ: an existing relationship is never overwritten.
	 *
	 * A hard-coded `rId1` works on a freshly generated document and silently
	 * REPLACES a real relationship — an image, a hyperlink, a header — on one that
	 * already has several. This is the test that would catch that.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function testAnExistingRelationshipIsNeverOverwritten(): void {
		$result = $this->codec->embedChart($this->docx(existingRelationships: 6), 'docx', self::CHART);

		$this->assertSame('rId7', $result['relationshipId']);

		$rels = $this->entry($result['bytes'], 'word/_rels/document.xml.rels');
		for ($i = 1; $i <= 6; $i++) {
			$this->assertStringContainsString(
				sprintf('Id="rId%d" Type="http://example.test/rel"', $i),
				$rels,
				sprintf('pre-existing relationship rId%d must survive', $i)
			);
		}
	}//end testAnExistingRelationshipIsNeverOverwritten()

	/**
	 * REQ: the content-type override is present.
	 *
	 * A missing Override is not a degraded chart — the suite refuses the whole
	 * document.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function testTheContentTypeOverrideIsDeclared(): void {
		$result = $this->codec->embedChart($this->docx(), 'docx', self::CHART);

		$this->assertStringContainsString(
			'PartName="/word/charts/chart1.xml"',
			$this->entry($result['bytes'], '[Content_Types].xml')
		);
		$this->assertStringContainsString(
			'drawingml.chart+xml',
			$this->entry($result['bytes'], '[Content_Types].xml')
		);
	}//end testTheContentTypeOverrideIsDeclared()

	/**
	 * REQ: values and labels reach the cache, which is what suites render from.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function testValuesAndLabelsAreCached(): void {
		$result = $this->codec->embedChart($this->docx(), 'docx', self::CHART);
		$chart  = $this->entry($result['bytes'], 'word/charts/chart1.xml');

		foreach (['Zorg', 'Onderwijs', 'Wonen'] as $label) {
			$this->assertStringContainsString(sprintf('<c:v>%s</c:v>', $label), $chart);
		}

		foreach ([120, 90, 45] as $value) {
			$this->assertStringContainsString(sprintf('<c:v>%s</c:v>', $value), $chart);
		}

		$this->assertStringContainsString('Toekenning per programma', $chart);
	}//end testValuesAndLabelsAreCached()

	/**
	 * REQ: the drawing goes BEFORE the sectPr.
	 *
	 * A `w:sectPr` must be the last child of the body. A paragraph after it is
	 * invalid and Word opens the document with a repair prompt.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function testTheDrawingIsPlacedBeforeTheSectionProperties(): void {
		$result   = $this->codec->embedChart($this->docx(), 'docx', self::CHART);
		$document = $this->entry($result['bytes'], 'word/document.xml');

		$this->assertLessThan(
			strpos($document, '<w:sectPr'),
			strpos($document, '<w:drawing>'),
			'the drawing must precede the section properties'
		);
	}//end testTheDrawingIsPlacedBeforeTheSectionProperties()

	/**
	 * REQ: a chart can be placed after a named paragraph.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function testAChartCanBePlacedAfterAnAnchoredParagraph(): void {
		$anchor = sprintf('b%s-1', substr(sha1('Intro paragraph'), 0, 8));

		$result   = $this->codec->embedChart($this->docx(), 'docx', self::CHART, $anchor);
		$document = $this->entry($result['bytes'], 'word/document.xml');

		$this->assertLessThan(
			strpos($document, '<w:drawing>'),
			strpos($document, 'Intro paragraph'),
			'the chart must follow the anchored paragraph'
		);
		$this->assertLessThan(
			strpos($document, 'Second paragraph'),
			strpos($document, '<w:drawing>'),
			'the chart must precede the paragraph that followed the anchor'
		);
	}//end testAChartCanBePlacedAfterAnAnchoredParagraph()

	/**
	 * REQ: an unresolvable anchor is refused and nothing is written.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function testAnUnresolvableAnchorIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/does not match any paragraph/');

		$this->codec->embedChart($this->docx(), 'docx', self::CHART, 'bdeadbeef-1');
	}//end testAnUnresolvableAnchorIsRefused()

	/**
	 * REQ: a series whose length disagrees with the categories is refused.
	 *
	 * Padding would draw a chart the caller did not describe; truncating the
	 * categories would drop data. Neither is a safe guess.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function testAMismatchedSeriesLengthIsRefused(): void {
		$chart             = self::CHART;
		$chart['series'][0]['values'] = [1, 2];

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/has 2 value\(s\) but there are 3 categories/');

		$this->codec->embedChart($this->docx(), 'docx', $chart);
	}//end testAMismatchedSeriesLengthIsRefused()

	/**
	 * REQ: a pie chart refuses more than one series.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function testAPieChartRefusesASecondSeries(): void {
		$chart            = self::CHART;
		$chart['type']    = 'pie';
		$chart['series'][] = ['name' => 'Second', 'values' => [1, 2, 3]];

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/pie chart can only show one series/');

		$this->codec->embedChart($this->docx(), 'docx', $chart);
	}//end testAPieChartRefusesASecondSeries()

	/**
	 * REQ: a pie chart carries no axes.
	 *
	 * Emitting axis references for a pie produces a file Word opens with a repair
	 * prompt.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function testAPieChartCarriesNoAxes(): void {
		$chart         = self::CHART;
		$chart['type'] = 'pie';

		$result = $this->codec->embedChart($this->docx(), 'docx', $chart);
		$xml    = $this->entry($result['bytes'], 'word/charts/chart1.xml');

		$this->assertStringContainsString('<c:pieChart>', $xml);
		$this->assertStringNotContainsString('<c:catAx>', $xml);
		$this->assertStringNotContainsString('<c:valAx>', $xml);
	}//end testAPieChartCarriesNoAxes()

	/**
	 * REQ: an unknown type and an empty series are refused by name.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function testInvalidDefinitionsAreRefusedByName(): void {
		try {
			$chart         = self::CHART;
			$chart['type'] = 'donut';
			$this->codec->embedChart($this->docx(), 'docx', $chart);
			$this->fail('an unknown chart type must be refused');
		} catch (RuntimeException $e) {
			$this->assertStringContainsString('bar, line, pie', $e->getMessage());
		}

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/at least one series/');

		$chart           = self::CHART;
		$chart['series'] = [];
		$this->codec->embedChart($this->docx(), 'docx', $chart);
	}//end testInvalidDefinitionsAreRefusedByName()

	/**
	 * REQ: ODF is refused by name, not silently ignored.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function testOdfIsRefusedByName(): void {
		$this->assertFalse($this->codec->supports('odt'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/only be embedded in \.docx.*embedded object/s');

		$this->codec->embedChart($this->docx(), 'odt', self::CHART);
	}//end testOdfIsRefusedByName()

	/**
	 * REQ: a second chart gets its own part and its own relationship.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function testASecondChartDoesNotCollideWithTheFirst(): void {
		$first  = $this->codec->embedChart($this->docx(), 'docx', self::CHART);
		$second = $this->codec->embedChart($first['bytes'], 'docx', self::CHART);

		$this->assertSame('word/charts/chart1.xml', $first['chartPart']);
		$this->assertSame('word/charts/chart2.xml', $second['chartPart']);
		$this->assertNotSame($first['relationshipId'], $second['relationshipId']);

		// The first chart's part must survive the second embed.
		$this->assertStringContainsString(
			'<c:barChart>',
			$this->entry($second['bytes'], 'word/charts/chart1.xml')
		);
	}//end testASecondChartDoesNotCollideWithTheFirst()
}//end class
