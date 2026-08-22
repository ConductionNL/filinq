<?php

/**
 * Filinq ChartCodec
 *
 * Embeds a native DrawingML chart into an OOXML word-processing package.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Service
 * @package  OCA\Filinq\Service\Editing
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

namespace OCA\Filinq\Service\Editing;

use RuntimeException;

/**
 * A real chart, not a picture of one.
 *
 * This is the first thing in the editing surface that ADDS package parts rather
 * than rewriting one. Five things must agree or the document is corrupt, and a
 * corrupt `.docx` does not degrade — the suite refuses to open it at all:
 *
 *   1. `word/charts/chartN.xml`           the chart definition
 *   2. `[Content_Types].xml`              an Override declaring its content type
 *   3. `word/_rels/document.xml.rels`     a Relationship with a fresh rId
 *   4. `word/document.xml`                a `<w:drawing>` referencing that rId
 *   5. the rId in 3 and 4 must be THE SAME and must not collide with an existing one
 *
 * Because of (5) the relationship id is derived by scanning the existing rels for
 * the highest `rId<n>` and taking the next. Hard-coding one would work on a
 * freshly generated document and silently overwrite a real relationship — an
 * image, a hyperlink, a header — on a document that already had six.
 *
 * ## Values are cached, and there is no embedded workbook
 *
 * A chart may reference an embedded `.xlsx` so a user can click it and edit the
 * data. It may also carry its values inline in `<c:numCache>` / `<c:strCache>`,
 * which is what every suite actually renders from. This writes the caches only.
 *
 * The consequence is stated rather than hidden: the chart RENDERS correctly and is
 * selectable, resizable and styleable, but "Edit data" has no worksheet to open.
 * Minting a valid embedded workbook is a second package format inside this one, and
 * a subtly wrong one produces exactly the corrupt-file failure above.
 *
 * ## OOXML only
 *
 * An ODF chart is an embedded OBJECT — its own sub-directory with a `content.xml`,
 * plus a `META-INF/manifest.xml` entry, referenced by a `<draw:frame>`. That is a
 * different construction, not a translation of this one, and it is refused by name
 * rather than silently ignored.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 *
 * @spec openspec/specs/document-chart-embedding/spec.md
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ChartCodec {

	/**
	 * Chart types this codec can build, mapped to their DrawingML element.
	 *
	 * @var array<string, string>
	 */
	private const TYPES = [
		'bar'  => 'c:barChart',
		'line' => 'c:lineChart',
		'pie'  => 'c:pieChart',
	];

	/**
	 * Types that carry a category and a value axis.
	 *
	 * A pie chart has neither, and emitting axis references for one produces a
	 * file Word opens with a repair prompt.
	 *
	 * @var array<int, string>
	 */
	private const AXIAL_TYPES = ['bar', 'line'];

	/**
	 * Category axis id.
	 *
	 * @var int
	 */
	private const CAT_AXIS_ID = 111111111;

	/**
	 * Value axis id.
	 *
	 * @var int
	 */
	private const VAL_AXIS_ID = 222222222;

	/**
	 * Chart content type.
	 *
	 * @var string
	 */
	private const CHART_CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.drawingml.chart+xml';

	/**
	 * Chart relationship type.
	 *
	 * @var string
	 */
	private const CHART_REL_TYPE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart';

	/**
	 * Constructor.
	 *
	 * @param PackagePartIo   $io      The package part reader/writer.
	 * @param XmlBlockScanner $scanner The element-span scanner.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly PackagePartIo $io = new PackagePartIo(),
		private readonly XmlBlockScanner $scanner = new XmlBlockScanner(),
	) {
	}//end __construct()

	/**
	 * The chart types this codec can build.
	 *
	 * @return array<int, string> The type names.
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function types(): array {
		return array_keys(self::TYPES);
	}//end types()

	/**
	 * Whether a chart can be embedded in this extension.
	 *
	 * @param string $extension The file extension.
	 *
	 * @return bool True when supported.
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function supports(string $extension): bool {
		return (strtolower($extension) === 'docx');
	}//end supports()

	/**
	 * Embed a chart, placing it after an anchored paragraph or at the end.
	 *
	 * @param string      $packageBytes The raw package bytes.
	 * @param string      $extension    The file extension.
	 * @param array       $chart        The chart definition.
	 * @param string|null $afterAnchor  The anchor to place it after, or null for the end.
	 *
	 * @return array{bytes: string, chartPart: string, relationshipId: string}
	 *
	 * @throws RuntimeException When the format or definition is invalid.
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function embedChart(
		string $packageBytes,
		string $extension,
		array $chart,
		?string $afterAnchor = null,
	): array {
		if ($this->supports(extension: $extension) === false) {
			throw new RuntimeException(
				sprintf(
					'Charts can only be embedded in .docx files, not "%s". An ODF chart is an embedded object '
					. 'with its own manifest entry, which is a different construction and is not implemented.',
					$extension
				)
			);
		}

		$chart = $this->validate(chart: $chart);

		$documentXml = $this->io->readPart(packageBytes: $packageBytes, part: 'word/document.xml');
		$relsXml     = $this->relationships(packageBytes: $packageBytes);

		$index     = $this->nextChartIndex(packageBytes: $packageBytes);
		$chartPart = sprintf('word/charts/chart%d.xml', $index);
		$relId     = $this->nextRelationshipId(relsXml: $relsXml);

		// Order matters only in that every write must land; each writePart returns
		// new bytes, so they are threaded rather than applied to the original.
		$bytes = $this->io->writePart(
			packageBytes: $packageBytes,
			part: $chartPart,
			xml: $this->buildChartXml(chart: $chart)
		);

		$bytes = $this->io->writePart(
			packageBytes: $bytes,
			part: '[Content_Types].xml',
			xml: $this->withContentTypeOverride(
				xml: $this->io->readPart(packageBytes: $bytes, part: '[Content_Types].xml'),
				partName: '/' . $chartPart
			)
		);

		$bytes = $this->io->writePart(
			packageBytes: $bytes,
			part: 'word/_rels/document.xml.rels',
			xml: $this->withRelationship(
				xml: $relsXml,
				relId: $relId,
				target: sprintf('charts/chart%d.xml', $index)
			)
		);

		$bytes = $this->io->writePart(
			packageBytes: $bytes,
			part: 'word/document.xml',
			xml: $this->withDrawing(
				xml: $documentXml,
				relId: $relId,
				title: $chart['title'],
				afterAnchor: $afterAnchor
			)
		);

		return [
			'bytes'          => $bytes,
			'chartPart'      => $chartPart,
			'relationshipId' => $relId,
		];
	}//end embedChart()

	/**
	 * Validate and normalise a chart definition.
	 *
	 * The caller is a language model, so every constraint is checked and named.
	 * A series shorter than the category list is the interesting case: silently
	 * padding it would draw a chart the caller did not describe, and silently
	 * truncating the categories would drop data.
	 *
	 * @param array $chart The chart definition.
	 *
	 * @return array{type: string, title: string, categories: array<int, string>, series: array<int, array{name: string, values: array<int, float>}>}
	 *
	 * @throws RuntimeException When the definition is unusable.
	 */
	private function validate(array $chart): array {
		$type = strtolower((string)($chart['type'] ?? ''));
		if (isset(self::TYPES[$type]) === false) {
			throw new RuntimeException(
				sprintf('Unknown chart type "%s". Supported: %s.', $type, implode(', ', array_keys(self::TYPES)))
			);
		}

		$categories = array_values((array)($chart['categories'] ?? []));
		if ($categories === []) {
			throw new RuntimeException('A chart needs at least one category.');
		}

		$rawSeries = array_values((array)($chart['series'] ?? []));
		if ($rawSeries === []) {
			throw new RuntimeException('A chart needs at least one series.');
		}

		if ($type === 'pie' && count($rawSeries) > 1) {
			throw new RuntimeException('A pie chart can only show one series.');
		}

		$series = [];
		foreach ($rawSeries as $position => $entry) {
			$values = array_values((array)(((array)$entry)['values'] ?? []));
			if (count($values) !== count($categories)) {
				throw new RuntimeException(
					sprintf(
						'Series %d has %d value(s) but there are %d categories. '
						. 'Every series must carry exactly one value per category.',
						((int)$position + 1),
						count($values),
						count($categories)
					)
				);
			}

			$series[] = [
				'name'   => (string)(((array)$entry)['name'] ?? sprintf('Series %d', ((int)$position + 1))),
				'values' => array_map(static fn ($value): float => (float)$value, $values),
			];
		}

		return [
			'type'       => $type,
			'title'      => (string)($chart['title'] ?? ''),
			'categories' => array_map(static fn ($category): string => (string)$category, $categories),
			'series'     => $series,
		];
	}//end validate()

	/**
	 * Build the chart part XML.
	 *
	 * @param array $chart The validated chart definition.
	 *
	 * @return string The chart XML.
	 */
	private function buildChartXml(array $chart): string {
		$element = self::TYPES[$chart['type']];

		$series = '';
		foreach ($chart['series'] as $index => $entry) {
			$series .= $this->buildSeries(index: (int)$index, entry: $entry, categories: $chart['categories']);
		}

		$body = sprintf('<%s>', $element);
		if ($chart['type'] === 'bar') {
			$body .= '<c:barDir val="col"/><c:grouping val="clustered"/>';
		}

		if ($chart['type'] === 'line') {
			$body .= '<c:grouping val="standard"/>';
		}

		$body .= '<c:varyColors val="0"/>' . $series;

		$axes = '';
		if (in_array($chart['type'], self::AXIAL_TYPES, true) === true) {
			$body .= sprintf('<c:axId val="%d"/><c:axId val="%d"/>', self::CAT_AXIS_ID, self::VAL_AXIS_ID);
			$axes  = $this->buildAxes();
		}

		$body .= sprintf('</%s>', $element);

		$title = '';
		if ($chart['title'] !== '') {
			$title = sprintf(
				'<c:title><c:tx><c:rich><a:bodyPr/><a:p><a:r><a:t>%s</a:t></a:r></a:p></c:rich></c:tx>'
				. '<c:overlay val="0"/></c:title><c:autoTitleDeleted val="0"/>',
				$this->escape(value: $chart['title'])
			);
		}

		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<c:chartSpace xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" '
			. 'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
			. 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
			. '<c:chart>' . $title . '<c:plotArea><c:layout/>' . $body . $axes . '</c:plotArea>'
			. '<c:plotVisOnly val="1"/><c:dispBlanksAs val="gap"/></c:chart></c:chartSpace>';
	}//end buildChartXml()

	/**
	 * Build one series element.
	 *
	 * @param int   $index      The series index.
	 * @param array $entry      The series definition.
	 * @param array $categories The category labels.
	 *
	 * @return string The series XML.
	 */
	private function buildSeries(int $index, array $entry, array $categories): string {
		$catPoints = '';
		foreach ($categories as $position => $label) {
			$catPoints .= sprintf(
				'<c:pt idx="%d"><c:v>%s</c:v></c:pt>',
				(int)$position,
				$this->escape(value: (string)$label)
			);
		}

		$valPoints = '';
		foreach ($entry['values'] as $position => $value) {
			$valPoints .= sprintf('<c:pt idx="%d"><c:v>%s</c:v></c:pt>', (int)$position, (string)$value);
		}

		$count = count($categories);

		// The `c:f` formula references a sheet that does not exist in this package,
		// which is legal: suites render from the cache and only consult the formula
		// when a user asks to edit the data. The reference is kept because omitting
		// it makes some readers treat the series as having no source at all.
		return sprintf(
			'<c:ser><c:idx val="%1$d"/><c:order val="%1$d"/>'
			. '<c:tx><c:strRef><c:f>Sheet1!$%2$s$1</c:f><c:strCache><c:ptCount val="1"/>'
			. '<c:pt idx="0"><c:v>%3$s</c:v></c:pt></c:strCache></c:strRef></c:tx>'
			. '<c:cat><c:strRef><c:f>Sheet1!$A$2:$A$%4$d</c:f><c:strCache><c:ptCount val="%5$d"/>%6$s'
			. '</c:strCache></c:strRef></c:cat>'
			. '<c:val><c:numRef><c:f>Sheet1!$%2$s$2:$%2$s$%4$d</c:f><c:numCache>'
			. '<c:formatCode>General</c:formatCode><c:ptCount val="%5$d"/>%7$s</c:numCache></c:numRef></c:val>'
			. '</c:ser>',
			$index,
			chr((66 + $index)),
			$this->escape(value: $entry['name']),
			($count + 1),
			$count,
			$catPoints,
			$valPoints
		);
	}//end buildSeries()

	/**
	 * Build the category and value axes.
	 *
	 * @return string The axis XML.
	 */
	private function buildAxes(): string {
		return sprintf(
			'<c:catAx><c:axId val="%1$d"/><c:scaling><c:orientation val="minMax"/></c:scaling>'
			. '<c:delete val="0"/><c:axPos val="b"/><c:crossAx val="%2$d"/></c:catAx>'
			. '<c:valAx><c:axId val="%2$d"/><c:scaling><c:orientation val="minMax"/></c:scaling>'
			. '<c:delete val="0"/><c:axPos val="l"/><c:crossAx val="%1$d"/></c:valAx>',
			self::CAT_AXIS_ID,
			self::VAL_AXIS_ID
		);
	}//end buildAxes()

	/**
	 * Read the document relationships, or a minimal set when absent.
	 *
	 * @param string $packageBytes The package bytes.
	 *
	 * @return string The relationships XML.
	 */
	private function relationships(string $packageBytes): string {
		$part = 'word/_rels/document.xml.rels';
		if ($this->io->hasPart(packageBytes: $packageBytes, part: $part) === true) {
			return $this->io->readPart(packageBytes: $packageBytes, part: $part);
		}

		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
			. '</Relationships>';
	}//end relationships()

	/**
	 * Pick a relationship id that is not already taken.
	 *
	 * Scanned rather than hard-coded. A fixed `rId1` works on a freshly generated
	 * document and silently REPLACES a real relationship — an image, a hyperlink,
	 * a header — on one that already has several.
	 *
	 * @param string $relsXml The relationships XML.
	 *
	 * @return string The new relationship id.
	 */
	private function nextRelationshipId(string $relsXml): string {
		preg_match_all('#Id="rId(\d+)"#', $relsXml, $matches);

		$highest = 0;
		foreach ($matches[1] as $value) {
			$highest = max($highest, (int)$value);
		}

		return 'rId' . ($highest + 1);
	}//end nextRelationshipId()

	/**
	 * Pick a chart part index that is not already taken.
	 *
	 * @param string $packageBytes The package bytes.
	 *
	 * @return int The next index.
	 */
	private function nextChartIndex(string $packageBytes): int {
		$index  = 1;
		$exists = $this->io->hasPart(
			packageBytes: $packageBytes,
			part: sprintf('word/charts/chart%d.xml', $index)
		);

		while ($exists === true) {
			$index++;
			$exists = $this->io->hasPart(
				packageBytes: $packageBytes,
				part: sprintf('word/charts/chart%d.xml', $index)
			);
		}

		return $index;
	}//end nextChartIndex()

	/**
	 * Add the chart's content-type override.
	 *
	 * Without this the suite does not know what the part is and refuses the whole
	 * document — a missing Override is not a degraded chart, it is a corrupt file.
	 *
	 * @param string $xml      The content types XML.
	 * @param string $partName The part name, with a leading slash.
	 *
	 * @return string The rewritten XML.
	 *
	 * @throws RuntimeException When the Types element cannot be found.
	 */
	private function withContentTypeOverride(string $xml, string $partName): string {
		$override = sprintf('<Override PartName="%s" ContentType="%s"/>', $partName, self::CHART_CONTENT_TYPE);

		if (str_contains($xml, '</Types>') === false) {
			throw new RuntimeException('[Content_Types].xml has no closing Types element; the package may be corrupt.');
		}

		return str_replace('</Types>', $override . '</Types>', $xml);
	}//end withContentTypeOverride()

	/**
	 * Add the chart relationship.
	 *
	 * @param string $xml    The relationships XML.
	 * @param string $relId  The relationship id.
	 * @param string $target The relationship target, relative to `word/`.
	 *
	 * @return string The rewritten XML.
	 *
	 * @throws RuntimeException When the Relationships element cannot be found.
	 */
	private function withRelationship(string $xml, string $relId, string $target): string {
		$relationship = sprintf(
			'<Relationship Id="%s" Type="%s" Target="%s"/>',
			$relId,
			self::CHART_REL_TYPE,
			$target
		);

		if (str_contains($xml, '</Relationships>') === false) {
			throw new RuntimeException('document.xml.rels has no closing Relationships element.');
		}

		return str_replace('</Relationships>', $relationship . '</Relationships>', $xml);
	}//end withRelationship()

	/**
	 * Insert the drawing paragraph into the body.
	 *
	 * @param string      $xml         The document XML.
	 * @param string      $relId       The chart relationship id.
	 * @param string      $title       The chart title, used as the drawing's name.
	 * @param string|null $afterAnchor The anchor to place it after, or null for the end.
	 *
	 * @return string The rewritten XML.
	 *
	 * @throws RuntimeException When the anchor does not resolve or the body cannot be found.
	 */
	private function withDrawing(string $xml, string $relId, string $title, ?string $afterAnchor): string {
		$paragraph = $this->buildDrawingParagraph(relId: $relId, title: $title);

		if ($afterAnchor === null || trim($afterAnchor) === '') {
			if (str_contains($xml, '</w:body>') === false) {
				throw new RuntimeException('word/document.xml has no closing body element.');
			}

			// Before the sectPr when there is one: a sectPr must be the LAST child
			// of the body, and a paragraph after it is invalid.
			if (preg_match('#<w:sectPr[\s>]#', $xml) === 1) {
				return (string)preg_replace('#(<w:sectPr[\s>])#', $paragraph . '$1', $xml, 1);
			}

			return str_replace('</w:body>', $paragraph . '</w:body>', $xml);
		}

		$spans = $this->scanner->spans(xml: $xml, tag: 'w:p');
		foreach ($spans as $span) {
			$markup = substr($xml, $span[0], $span[1]);
			if ($this->anchorOf(markup: $markup) === $afterAnchor) {
				return substr_replace($xml, $markup . $paragraph, $span[0], $span[1]);
			}
		}

		throw new RuntimeException(
			sprintf(
				'Anchor "%s" does not match any paragraph in this document. Anchors are derived from '
				. 'paragraph content, so re-read the document and use a current anchor.',
				$afterAnchor
			)
		);
	}//end withDrawing()

	/**
	 * Compute a paragraph's anchor, matching PackageCodec's scheme.
	 *
	 * Ordinals are not reproduced here: this resolves a single anchor for
	 * placement, and a duplicate-text paragraph resolves to its first occurrence,
	 * which is the same paragraph a reader would point at.
	 *
	 * @param string $markup The paragraph markup.
	 *
	 * @return string The anchor.
	 */
	private function anchorOf(string $markup): string {
		preg_match_all('#<w:t(?:\s[^>]*)?>(.*?)</w:t>#s', $markup, $matches);
		$text = html_entity_decode(implode('', $matches[1]), (ENT_QUOTES | ENT_XML1), 'UTF-8');

		return sprintf('b%s-1', substr(sha1($text), 0, 8));
	}//end anchorOf()

	/**
	 * Build the paragraph holding the drawing.
	 *
	 * @param string $relId The chart relationship id.
	 * @param string $title The chart title.
	 *
	 * @return string The paragraph XML.
	 */
	private function buildDrawingParagraph(string $relId, string $title): string {
		$name = $title;
		if ($name === '') {
			$name = 'Chart';
		}

		return sprintf(
			'<w:p><w:r><w:drawing>'
			. '<wp:inline distT="0" distB="0" distL="0" distR="0" '
			. 'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">'
			. '<wp:extent cx="5486400" cy="3200400"/><wp:effectExtent l="0" t="0" r="0" b="0"/>'
			. '<wp:docPr id="%d" name="%s"/>'
			. '<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
			. '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/chart">'
			. '<c:chart xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" '
			. 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:id="%s"/>'
			. '</a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>',
			(int)filter_var($relId, FILTER_SANITIZE_NUMBER_INT),
			$this->escape(value: $name),
			$relId
		);
	}//end buildDrawingParagraph()

	/**
	 * XML-escape a value.
	 *
	 * @param string $value The value.
	 *
	 * @return string The escaped value.
	 */
	private function escape(string $value): string {
		return htmlspecialchars($value, (ENT_QUOTES | ENT_XML1), 'UTF-8');
	}//end escape()
}//end class
