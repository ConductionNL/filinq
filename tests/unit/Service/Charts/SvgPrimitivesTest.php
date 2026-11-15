<?php

/**
 * Unit tests for SvgPrimitives.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Charts;

use OCA\Filinq\Service\Charts\SvgArc;
use OCA\Filinq\Service\Charts\SvgBox;
use OCA\Filinq\Service\Charts\SvgCircle;
use OCA\Filinq\Service\Charts\SvgPoint;
use OCA\Filinq\Service\Charts\SvgPrimitives;
use OCA\Filinq\Service\Charts\SvgSector;
use OCA\Filinq\Service\Charts\SvgSegment;
use OCA\Filinq\Service\Charts\SvgSize;
use OCA\Filinq\Service\Charts\SvgText;
use OCA\Filinq\Service\Charts\SvgTextStyle;
use PHPUnit\Framework\TestCase;

/**
 * The SVG element strings every chart is drawn from.
 *
 * @covers \OCA\Filinq\Service\Charts\SvgPrimitives
 * @covers \OCA\Filinq\Service\Charts\SvgTextStyle
 *
 * @uses \OCA\Filinq\Service\Charts\SvgArc
 * @uses \OCA\Filinq\Service\Charts\SvgBox
 * @uses \OCA\Filinq\Service\Charts\SvgCircle
 * @uses \OCA\Filinq\Service\Charts\SvgPoint
 * @uses \OCA\Filinq\Service\Charts\SvgSector
 * @uses \OCA\Filinq\Service\Charts\SvgSegment
 * @uses \OCA\Filinq\Service\Charts\SvgSize
 * @uses \OCA\Filinq\Service\Charts\SvgText
 */
class SvgPrimitivesTest extends TestCase {

	/**
	 * The primitives under test.
	 *
	 * @var SvgPrimitives
	 */
	private SvgPrimitives $svg;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->svg = new SvgPrimitives();
	}//end setUp()

	/**
	 * The open tag sizes the canvas and paints it white.
	 *
	 * @return void
	 */
	public function testTheOpenTagSizesAndPaintsTheCanvas(): void {
		self::assertSame(
			'<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">'
			. '<rect x="0" y="0" width="400" height="300" fill="#FFFFFF"/>',
			$this->svg->svgOpenTag(width: 400, height: 300)
		);
	}//end testTheOpenTagSizesAndPaintsTheCanvas()

	/**
	 * Numbers are rounded to two decimals with trailing zeros and a bare point dropped.
	 *
	 * @return void
	 */
	public function testNumbersAreWrittenShort(): void {
		self::assertSame(
			'<line x1="10" y1="0.5" x2="3.14" y2="0" stroke="#000" stroke-width="1"/>',
			$this->svg->lineEl(segment: new SvgSegment(from: new SvgPoint(x: 10.0, y: 0.5), to: new SvgPoint(x: 3.14159, y: 0.0)), color: '#000')
		);
	}//end testNumbersAreWrittenShort()

	/**
	 * Text is escaped and carries its style's attributes.
	 *
	 * @return void
	 */
	public function testTextIsEscapedAndStyled(): void {
		self::assertSame(
			'<text x="5" y="7.25" font-family="sans-serif" font-size="9" font-weight="normal" fill="#666666" text-anchor="end">'
			. 'Q&amp;A &lt;b&gt; &quot;x&quot; &#039;y&#039;</text>',
			$this->svg->textEl(position: new SvgPoint(x: 5, y: 7.25), text: new SvgText(content: 'Q&A <b> "x" \'y\'', style: SvgTextStyle::Axis))
		);
	}//end testTextIsEscapedAndStyled()

	/**
	 * Each text style has its own attributes.
	 *
	 * @return void
	 */
	public function testEachTextStyleHasItsAttributes(): void {
		self::assertSame('font-size="13" font-weight="bold" fill="#000000" text-anchor="middle"', SvgTextStyle::Title->attributes());
		self::assertSame('font-size="9" font-weight="normal" fill="#333333" text-anchor="start"', SvgTextStyle::LabelStart->attributes());
		self::assertSame('font-size="9" font-weight="normal" fill="#333333" text-anchor="middle"', SvgTextStyle::LabelMiddle->attributes());
		self::assertSame('font-size="9" font-weight="normal" fill="#333333" text-anchor="end"', SvgTextStyle::LabelEnd->attributes());
		self::assertSame('font-size="11" font-weight="normal" fill="#888888" text-anchor="middle"', SvgTextStyle::Placeholder->attributes());
	}//end testEachTextStyleHasItsAttributes()

	/**
	 * Filled and outlined rectangles share the box attributes.
	 *
	 * @return void
	 */
	public function testRectanglesFillOrOutlineTheBox(): void {
		$box = new SvgBox(origin: new SvgPoint(x: 1.5, y: 2), size: new SvgSize(width: 30, height: 40.255));

		self::assertSame('<rect x="1.5" y="2" width="30" height="40.26" fill="#abc"/>', $this->svg->rectEl(box: $box, color: '#abc'));
		self::assertSame(
			'<rect x="1.5" y="2" width="30" height="40.26" fill="none" stroke="#abc" stroke-width="1"/>',
			$this->svg->rectOutlineEl(box: $box, color: '#abc')
		);
	}//end testRectanglesFillOrOutlineTheBox()

	/**
	 * Circles and polylines write their points.
	 *
	 * @return void
	 */
	public function testCirclesAndPolylines(): void {
		self::assertSame(
			'<circle cx="50" cy="60" r="12.5" fill="#f00"/>',
			$this->svg->circleEl(circle: new SvgCircle(center: new SvgPoint(x: 50, y: 60), radius: 12.5), color: '#f00')
		);
		self::assertSame(
			'<polyline points="0,10 5.5,2 9,0" fill="none" stroke="#0f0" stroke-width="2"/>',
			$this->svg->polylineEl(points: [[0, 10], [5.5, 2], [9, 0]], color: '#0f0')
		);
	}//end testCirclesAndPolylines()

	/**
	 * A slice up to half the circle uses the small arc, a larger one the large arc.
	 *
	 * @return void
	 */
	public function testThePieSliceArcFlagFollowsTheSweep(): void {
		$circle = new SvgCircle(center: new SvgPoint(x: 100, y: 100), radius: 50);

		self::assertSame(
			'<path d="M 100 100 L 150 100 A 50 50 0 0 1 50 100 Z" fill="#111"/>',
			$this->svg->pieSliceEl(sector: new SvgSector(circle: $circle, arc: new SvgArc(startAngle: 0, endAngle: 180)), color: '#111')
		);
		self::assertSame(
			'<path d="M 100 100 L 100 50 A 50 50 0 1 1 50 100 Z" fill="#111"/>',
			$this->svg->pieSliceEl(sector: new SvgSector(circle: $circle, arc: new SvgArc(startAngle: -90, endAngle: 180)), color: '#111')
		);
	}//end testThePieSliceArcFlagFollowsTheSweep()

	/**
	 * The placeholder is a grey framed box inset by one with the message centred.
	 *
	 * @return void
	 */
	public function testThePlaceholderFramesTheMessage(): void {
		self::assertSame(
			'<rect x="1" y="1" width="198" height="98" fill="#F5F5F5"/>'
			. '<rect x="1" y="1" width="198" height="98" fill="none" stroke="#CCCCCC" stroke-width="1"/>'
			. '<text x="100" y="50" font-family="sans-serif" font-size="11" font-weight="normal" fill="#888888" text-anchor="middle">no &amp; data</text>',
			$this->svg->placeholderEl(canvas: new SvgSize(width: 200, height: 100), message: 'no & data')
		);
	}//end testThePlaceholderFramesTheMessage()
}//end class
