<?php

/**
 * Pdfa3MetadataAssembler: how metadata becomes the XMP block and the XML sidecar
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Mpdf\Mpdf;
use OCA\Filinq\Service\Pdfa3MetadataAssembler;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;

/**
 * Keys are sanitised, values JSON-encoded or skipped, in both serialisations,
 * compared with output recorded from the implementation before it was split.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class Pdfa3MetadataSerialisationTest extends TestCase {

	/**
	 * The XMP block recorded before the split.
	 */
	private const XMP = '   <rdf:Description rdf:about="" xmlns:docudesk="https://www.docudesk.app/ns/mdto/1.0/">
    <docudesk:identifier>Z-1 &amp; &lt;zaak&gt;</docudesk:identifier>
    <docudesk:f_1stKey>x</docudesk:f_1stKey>
    <docudesk:f_-dash>y</docudesk:f_-dash>
    <docudesk:space>z</docudesk:space>
    <docudesk:space>dup</docudesk:space>
    <docudesk:list>[&quot;a&quot;,1]</docudesk:list>
    <docudesk:obj>{&quot;k&quot;:&quot;v&quot;}</docudesk:obj>
    <docudesk:num>12</docudesk:num>
    <docudesk:flag>1</docudesk:flag>
    <docudesk:none></docudesk:none>
    <docudesk:quote>it&apos;s &quot;q&quot;</docudesk:quote>
   </rdf:Description>
';

	/**
	 * The sidecar recorded before the split.
	 */
	private const SIDECAR = '<?xml version="1.0" encoding="UTF-8"?>
<docudeskMetadata>
  <title>Besluit</title>
  <author>A</author>
  <identifier>Z-1 &amp; &lt;zaak&gt;</identifier>
  <f_1stKey>x</f_1stKey>
  <f_-dash>y</f_-dash>
  <space>z</space>
  <space>dup</space>
  <list>["a",1]</list>
  <obj>{"k":"v"}</obj>
  <num>12</num>
  <flag>1</flag>
  <none></none>
  <quote>it\'s "q"</quote>
</docudeskMetadata>
';

	/**
	 * Metadata with every kind of key and value the serialisers treat differently.
	 *
	 * @return array<string, mixed>
	 */
	private function metadata(): array {
		return [
			'title' => 'Besluit',
			'author' => 'A',
			'identifier' => 'Z-1 & <zaak>',
			'1stKey' => 'x',
			'-dash' => 'y',
			'sp ace' => 'z',
			'space' => 'dup',
			'***' => 'gone',
			'list' => ['a', 1],
			'obj' => (object) ['k' => 'v'],
			'bad' => ['n' => NAN],
			'num' => 12,
			'flag' => true,
			'none' => null,
			'quote' => "it's \"q\"",
		];

	}//end metadata()

	/**
	 * The assembler with the default attachment cap.
	 *
	 * @return Pdfa3MetadataAssembler
	 */
	private function assembler(): Pdfa3MetadataAssembler {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnArgument(2);

		return new Pdfa3MetadataAssembler($config);

	}//end assembler()

	/**
	 * The XMP block carries every non-standard key, escaped and sanitised.
	 *
	 * @return void
	 */
	public function testTheXmpBlock(): void {
		$xmp = null;
		$mpdf = $this->createMock(Mpdf::class);
		$mpdf->method('SetAdditionalXmpRdf')->willReturnCallback(
			static function (string $value) use (&$xmp): void {
				$xmp = $value;
			}
		);

		$this->assembler()->applyMetadata($mpdf, $this->metadata(), 'Standaard');

		$this->assertSame(self::XMP, $xmp);

	}//end testTheXmpBlock()

	/**
	 * Only standard keys give no XMP block at all.
	 *
	 * @return void
	 */
	public function testOnlyStandardKeysGiveNoXmpBlock(): void {
		$mpdf = $this->createMock(Mpdf::class);
		$mpdf->expects($this->never())->method('SetAdditionalXmpRdf');

		$this->assembler()->applyMetadata($mpdf, ['title' => 'T', 'keywords' => ['a', 'b']], 'Standaard');

	}//end testOnlyStandardKeysGiveNoXmpBlock()

	/**
	 * The XML sidecar carries every key, sanitised, standard keys included.
	 *
	 * @return void
	 */
	public function testTheSidecar(): void {
		$files = $this->assembler()->buildAssociatedFiles([], $this->metadata());

		$this->assertSame(self::SIDECAR, $files[0]['content']);

	}//end testTheSidecar()
}//end class
