<?php

/**
 * Unit tests for the ODF and OOXML block-style codecs.
 *
 * 🔴 The property these tests exist for: a style key that a format cannot
 * express is REFUSED, never silently dropped. `list` on `.odt` is the case that
 * proves it — an ODF list is a `<text:list>` element WRAPPING the paragraph,
 * not a property of it, so honouring it would restructure the document rather
 * than restyle a block. Dropping it quietly would tell the caller their list
 * was applied when the file has no list in it.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\OdfBlockStyleCodec;
use OCA\Filinq\Service\Editing\OoxmlBlockStyleCodec;
use OCA\Filinq\Service\Editing\PackageCodec;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for the two block-style codecs.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
 */
class BlockStyleCodecsTest extends TestCase {
	/**
	 * Each codec claims its own format and refuses the other's.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testEachCodecClaimsOnlyItsOwnFormat(): void {
		$odf = new OdfBlockStyleCodec();
		$ooxml = new OoxmlBlockStyleCodec();

		$this->assertTrue($odf->supports(format: PackageCodec::FORMAT_ODF));
		$this->assertFalse($odf->supports(format: PackageCodec::FORMAT_OOXML));

		$this->assertTrue($ooxml->supports(format: PackageCodec::FORMAT_OOXML));
		$this->assertFalse($ooxml->supports(format: PackageCodec::FORMAT_ODF));
	}//end testEachCodecClaimsOnlyItsOwnFormat()

	/**
	 * 🔴 `list` on ODF is REFUSED, and the refusal explains itself.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testOdfRefusesAListRatherThanDroppingIt(): void {
		$codec = new OdfBlockStyleCodec();

		try {
			$codec->applyStyle(
				markup: '<text:p>one</text:p>',
				style: ['list' => true],
				styleName: 'P1'
			);
			$this->fail('a list on .odt must be refused, not silently dropped');
		} catch (RuntimeException $e) {
			$this->assertStringContainsString(
				'text:list',
				$e->getMessage(),
				'the refusal must say WHY, so the caller knows it is structural rather than unsupported styling'
			);
		}
	}//end testOdfRefusesAListRatherThanDroppingIt()

	/**
	 * A style the format CAN express produces an automatic style.
	 *
	 * The control for the refusal above: if `applyStyle` refused everything,
	 * that test would pass while the codec did nothing useful.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testOdfEmitsAnAutomaticStyleForAnExpressibleStyle(): void {
		$result = (new OdfBlockStyleCodec())->applyStyle(
			markup: '<text:p>one</text:p>',
			style: ['bold' => true],
			styleName: 'P1'
		);

		$this->assertNotNull($result['automaticStyle'], 'bold is expressible in ODF and must produce a style');
		$this->assertStringContainsString('P1', (string)$result['automaticStyle']);
	}//end testOdfEmitsAnAutomaticStyleForAnExpressibleStyle()

	/**
	 * An empty style changes nothing and declares no automatic style.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testAnEmptyOdfStyleIsANoOp(): void {
		$markup = '<text:p>one</text:p>';

		$result = (new OdfBlockStyleCodec())->applyStyle(markup: $markup, style: [], styleName: 'P1');

		$this->assertSame($markup, $result['markup']);
		$this->assertNull($result['automaticStyle']);
	}//end testAnEmptyOdfStyleIsANoOp()

	/**
	 * OOXML expresses a style inline, on the paragraph itself.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testOoxmlAppliesAStyleToTheParagraph(): void {
		$result = (new OoxmlBlockStyleCodec())->applyStyle(
			markup: '<w:p><w:r><w:t>one</w:t></w:r></w:p>',
			style: ['bold' => true],
			styleName: 'P1'
		);

		$this->assertNotSame(
			'<w:p><w:r><w:t>one</w:t></w:r></w:p>',
			$result['markup'],
			'an expressible style must change the markup'
		);
		$this->assertStringContainsString('<w:t>one</w:t>', $result['markup'], 'the text must survive styling');
	}//end testOoxmlAppliesAStyleToTheParagraph()

	/**
	 * An empty OOXML style changes nothing either.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testAnEmptyOoxmlStyleIsANoOp(): void {
		$markup = '<w:p><w:r><w:t>one</w:t></w:r></w:p>';

		$result = (new OoxmlBlockStyleCodec())->applyStyle(markup: $markup, style: [], styleName: 'P1');

		$this->assertSame($markup, $result['markup']);
	}//end testAnEmptyOoxmlStyleIsANoOp()

	/**
	 * OOXML supports a list, where ODF refuses one.
	 *
	 * The pair is the point: the same style key is legitimate in one format and
	 * structurally impossible in the other, and the codecs must disagree.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testOoxmlAcceptsAListThatOdfRefuses(): void {
		$result = (new OoxmlBlockStyleCodec())->applyStyle(
			markup: '<w:p><w:r><w:t>one</w:t></w:r></w:p>',
			style: ['list' => true],
			styleName: 'P1'
		);

		$this->assertStringContainsString('<w:t>one</w:t>', $result['markup']);
	}//end testOoxmlAcceptsAListThatOdfRefuses()
}//end class
