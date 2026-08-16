<?php

/**
 * Unit tests for BlockStyleCodec.
 *
 * openspec/changes/document-rich-editing.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Test
 * @package  OCA\DocuDesk\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://docudesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service\Editing;

use OCA\DocuDesk\Service\Editing\BlockStyleCodec;
use OCA\DocuDesk\Service\Editing\PackageCodec;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Style and layout applied to one paragraph's markup.
 */
class BlockStyleCodecTest extends TestCase {

	/**
	 * The codec under test.
	 *
	 * @var BlockStyleCodec
	 */
	private BlockStyleCodec $codec;

	/**
	 * A paragraph with one run.
	 *
	 * @var string
	 */
	private const PARAGRAPH = '<w:p><w:r><w:t>Binnen acht weken</w:t></w:r></w:p>';

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->codec = new BlockStyleCodec();
	}//end setUp()

	/**
	 * Apply style to the standard paragraph.
	 *
	 * @param array  $style  The style properties.
	 * @param string $markup Optional markup override.
	 *
	 * @return string The rewritten markup.
	 */
	private function apply(array $style, string $markup = self::PARAGRAPH): string {
		return $this->codec->applyStyle(
			markup: $markup,
			style: $style,
			format: PackageCodec::FORMAT_OOXML
		);
	}//end apply()

	/**
	 * REQ: run properties are applied to every run.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testBoldItalicUnderlineAreApplied(): void {
		$out = $this->apply(['bold' => true, 'italic' => true, 'underline' => true]);

		$this->assertStringContainsString('<w:b/>', $out);
		$this->assertStringContainsString('<w:i/>', $out);
		$this->assertStringContainsString('<w:u w:val="single"/>', $out);
		$this->assertStringContainsString('Binnen acht weken', $out, 'the text must be untouched');
	}//end testBoldItalicUnderlineAreApplied()

	/**
	 * REQ: switching a property OFF writes an explicit override.
	 *
	 * Omitting `<w:b/>` is not the same as turning bold off: a paragraph style can
	 * turn it on, and only `w:val="0"` overrides that.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testTurningBoldOffWritesAnExplicitOverride(): void {
		$out = $this->apply(['bold' => false]);

		$this->assertStringContainsString('<w:b w:val="0"/>', $out);
	}//end testTurningBoldOffWritesAnExplicitOverride()

	/**
	 * REQ: a heading sets pStyle; level 0 returns the paragraph to body text.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testHeadingLevelSetsAndClearsPStyle(): void {
		$this->assertStringContainsString('<w:pStyle w:val="Heading2"/>', $this->apply(['heading' => 2]));

		$heading = $this->apply(['heading' => 1]);
		$body    = $this->apply(['heading' => 0], $heading);

		$this->assertStringNotContainsString('w:pStyle', $body, 'level 0 must remove the heading style');
		$this->assertStringNotContainsString('Heading0', $body, 'no suite defines a "Heading0" style');
	}//end testHeadingLevelSetsAndClearsPStyle()

	/**
	 * REQ: alignment, list and page break are applied.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testLayoutPropertiesAreApplied(): void {
		$out = $this->apply(['alignment' => 'center', 'list' => true, 'pageBreakBefore' => true]);

		$this->assertStringContainsString('<w:jc w:val="center"/>', $out);
		$this->assertStringContainsString('<w:numPr>', $out);
		$this->assertStringContainsString('<w:pageBreakBefore/>', $out);
	}//end testLayoutPropertiesAreApplied()

	/**
	 * REQ: `justify` maps to OOXML's own value, which is not "justify".
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testJustifyMapsToBoth(): void {
		$this->assertStringContainsString('<w:jc w:val="both"/>', $this->apply(['alignment' => 'justify']));
	}//end testJustifyMapsToBoth()

	/**
	 * REQ: unrelated existing properties SURVIVE.
	 *
	 * Replacing `<w:pPr>` wholesale would silently drop spacing and indentation a
	 * user set by hand — the loss ADR-087 §2 warns about, and one no test notices
	 * unless it is this one.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testUnrelatedParagraphPropertiesSurvive(): void {
		$markup = '<w:p><w:pPr><w:spacing w:after="200"/><w:ind w:left="720"/></w:pPr>'
			. '<w:r><w:t>Text</w:t></w:r></w:p>';

		$out = $this->apply(['alignment' => 'right'], $markup);

		$this->assertStringContainsString('<w:spacing w:after="200"/>', $out);
		$this->assertStringContainsString('<w:ind w:left="720"/>', $out);
		$this->assertStringContainsString('<w:jc w:val="right"/>', $out);
	}//end testUnrelatedParagraphPropertiesSurvive()

	/**
	 * REQ: re-setting the same property replaces rather than duplicates it.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testResettingAPropertyReplacesIt(): void {
		$once  = $this->apply(['alignment' => 'left']);
		$twice = $this->apply(['alignment' => 'right'], $once);

		$this->assertSame(1, substr_count($twice, '<w:jc'), 'a second alignment must replace the first');
		$this->assertStringContainsString('w:val="right"', $twice);
	}//end testResettingAPropertyReplacesIt()

	/**
	 * REQ: existing run properties survive alongside new ones.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testExistingRunPropertiesSurvive(): void {
		$markup = '<w:p><w:r><w:rPr><w:sz w:val="28"/></w:rPr><w:t>Text</w:t></w:r></w:p>';

		$out = $this->apply(['bold' => true], $markup);

		$this->assertStringContainsString('<w:sz w:val="28"/>', $out);
		$this->assertStringContainsString('<w:b/>', $out);
	}//end testExistingRunPropertiesSurvive()

	/**
	 * REQ: ODF is refused BY NAME, and the refusal says what still works.
	 *
	 * The alternative — returning the markup unchanged — would report success and
	 * change nothing, which is the failure this codebase keeps meeting.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testOdfIsRefusedByNameRatherThanSilentlyIgnored(): void {
		$this->assertFalse($this->codec->supports(format: PackageCodec::FORMAT_ODF));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/OOXML.*\.docx.*Text edits and metadata DO work on \.odt/s');

		$this->codec->applyStyle(
			markup: '<text:p>Text</text:p>',
			style: ['bold' => true],
			format: PackageCodec::FORMAT_ODF
		);
	}//end testOdfIsRefusedByNameRatherThanSilentlyIgnored()

	/**
	 * REQ: an unknown property is refused, listing what is supported.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testUnknownPropertyIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/Unknown style property "colour".*bold, italic/s');

		$this->apply(['colour' => 'red']);
	}//end testUnknownPropertyIsRefused()

	/**
	 * REQ: an unknown alignment and an out-of-range heading are refused.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testInvalidValuesAreRefused(): void {
		try {
			$this->apply(['alignment' => 'middle']);
			$this->fail('an unknown alignment must be refused');
		} catch (RuntimeException $e) {
			$this->assertStringContainsString('Unknown alignment "middle"', $e->getMessage());
		}

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/Heading level must be between 0.*and 9/');
		$this->apply(['heading' => 12]);
	}//end testInvalidValuesAreRefused()

	/**
	 * REQ: an empty style object is refused rather than reported as applied.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testEmptyStyleIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/At least one style property is required/');

		$this->apply([]);
	}//end testEmptyStyleIsRefused()
}//end class
