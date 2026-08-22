<?php

/**
 * An edit must not quietly destroy what it does not understand.
 *
 * These are the tests that catch the failure nobody notices at the time: a
 * one-cell write that also drops the named ranges, or a shape edit that loses
 * the transitions. The codec has no opinion about those features — which is
 * exactly why they need asserting, because "I ignored it" and "I deleted it"
 * look identical from the call site.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://filinq.app
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#4-verify
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\PackagePartIo;
use OCA\Filinq\Service\Editing\PresentationCodec;
use OCA\Filinq\Service\Editing\SpreadsheetCodec;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Round-trip fidelity for spreadsheets and presentations.
 */
class RoundTripFidelityTest extends TestCase {

	private PackagePartIo $io;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		$this->io = new PackagePartIo();
	}//end setUp()

	/**
	 * Zip a set of parts into a package.
	 *
	 * @param array<string, string> $parts Entry name to contents.
	 *
	 * @return string The package bytes.
	 */
	private function pack(array $parts): string {
		$path = tempnam(sys_get_temp_dir(), 'pkg');
		$zip = new ZipArchive();
		$zip->open($path, ZipArchive::OVERWRITE);
		foreach ($parts as $name => $contents) {
			$zip->addFromString($name, $contents);
		}

		$zip->close();
		$bytes = (string)file_get_contents($path);
		unlink($path);

		return $bytes;
	}//end pack()

	/**
	 * Every entry in a package, with a hash of its contents.
	 *
	 * @param string $bytes The package.
	 *
	 * @return array<string, string> Entry name to hash.
	 */
	private function fingerprint(string $bytes): array {
		$path = tempnam(sys_get_temp_dir(), 'fp');
		file_put_contents($path, $bytes);

		$zip = new ZipArchive();
		$zip->open($path);
		$parts = [];
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$name = (string)$zip->getNameIndex($i);
			$parts[$name] = md5((string)$zip->getFromName($name));
		}

		$zip->close();
		unlink($path);

		return $parts;
	}//end fingerprint()

	/**
	 * 🔴 A one-cell edit must leave named ranges, conditional formatting and a
	 * pivot table exactly where they were — and must not touch any OTHER part
	 * of the package at all.
	 *
	 * @return void
	 */
	public function testASpreadsheetEditPreservesFeaturesItDoesNotUnderstand(): void {
		$content = '<?xml version="1.0"?><office:document-content>'
			. '<office:body><office:spreadsheet>'
			. '<table:table table:name="Blad">'
			. '<table:table-row>'
			. '<table:table-cell office:value-type="float" office:value="10"><text:p>10</text:p></table:table-cell>'
			. '</table:table-row>'
			. '</table:table>'
			. '<table:named-expressions>'
			. '<table:named-range table:name="Tarief" table:cell-range-address="$Blad.$A$1"/>'
			. '</table:named-expressions>'
			. '<calcext:conditional-formats>'
			. '<calcext:conditional-format calcext:target-range-address="Blad.A1"/>'
			. '</calcext:conditional-formats>'
			. '<table:data-pilot-tables><table:data-pilot-table table:name="Draaitabel"/></table:data-pilot-tables>'
			. '</office:spreadsheet></office:body></office:document-content>';

		$package = $this->pack([
			'content.xml' => $content,
			'styles.xml' => '<office:document-styles/>',
			'META-INF/manifest.xml' => '<manifest:manifest/>',
			'settings.xml' => '<office:document-settings/>',
		]);

		$before = $this->fingerprint($package);
		$codec = new SpreadsheetCodec($this->io);

		$result = $codec->applyCellEdits($package, 'ods', [['cell' => 'Blad!A1', 'value' => '42']]);
		$after = $this->fingerprint($result['bytes']);

		// Every part except the one holding the cell must be byte-identical.
		foreach ($before as $name => $hash) {
			if ($name === 'content.xml') {
				continue;
			}

			$this->assertSame($hash, ($after[$name] ?? null), $name . ' was modified by a one-cell edit');
		}

		$this->assertSame(array_keys($before), array_keys($after), 'a package part was added or dropped');

		$content = $this->io->readPart(packageBytes: $result['bytes'], part: 'content.xml');
		$this->assertStringContainsString('table:named-range', $content, 'the named range was destroyed');
		$this->assertStringContainsString('calcext:conditional-format', $content, 'conditional formatting was destroyed');
		$this->assertStringContainsString('data-pilot-table', $content, 'the pivot table was destroyed');
		$this->assertStringContainsString('42', $content, 'the edit itself did not land');
	}//end testASpreadsheetEditPreservesFeaturesItDoesNotUnderstand()

	/**
	 * 🔴 A shape edit must leave speaker notes, transitions and an embedded
	 * chart intact.
	 *
	 * @return void
	 */
	public function testAPresentationEditPreservesFeaturesItDoesNotUnderstand(): void {
		$content = '<?xml version="1.0"?><office:document-content><office:body><office:presentation>'
			. '<draw:page draw:name="pagina1" presentation:transition-type="automatic">'
			. '<draw:frame draw:name="Titel"><draw:text-box><text:p>Oud</text:p></draw:text-box></draw:frame>'
			. '<draw:frame draw:name="Grafiek"><draw:object xlink:href="./Object 1"/></draw:frame>'
			. '<presentation:notes>'
			. '<draw:frame draw:name="Notitie"><draw:text-box><text:p>Spreektekst</text:p></draw:text-box></draw:frame>'
			. '</presentation:notes>'
			. '</draw:page>'
			. '</office:presentation></office:body></office:document-content>';

		$package = $this->pack([
			'content.xml' => $content,
			'styles.xml' => '<office:document-styles/>',
			'Object 1/content.xml' => '<office:document-content><chart:chart/></office:document-content>',
		]);

		$before = $this->fingerprint($package);
		$codec = new PresentationCodec($this->io);

		$result = $codec->applyShapeEdits(
			$package,
			'odp',
			[['slide' => 'pagina1', 'shape' => 'Titel', 'text' => 'Nieuw']]
		);
		$after = $this->fingerprint($result['bytes']);

		$this->assertSame(
			$before['Object 1/content.xml'],
			($after['Object 1/content.xml'] ?? null),
			'the embedded chart part was modified by a text edit'
		);

		$content = $this->io->readPart(packageBytes: $result['bytes'], part: 'content.xml');
		$this->assertStringContainsString('presentation:transition-type', $content, 'the slide transition was lost');
		$this->assertStringContainsString('Spreektekst', $content, 'the speaker note was destroyed');
		$this->assertStringContainsString('xlink:href="./Object 1"', $content, 'the chart reference was destroyed');
		$this->assertStringContainsString('Nieuw', $content, 'the edit itself did not land');
	}//end testAPresentationEditPreservesFeaturesItDoesNotUnderstand()

	/**
	 * A dependent whose cached value is a spreadsheet ERROR is reported
	 * separately from an ordinary stale one. "This no longer follows from its
	 * inputs" and "this cell is broken" are different things to hear, and an
	 * error persisting silently through an edit reads as data.
	 *
	 * @return void
	 */
	public function testAnErroredDependentIsReportedSeparately(): void {
		$content = '<?xml version="1.0"?><office:document-content><office:body><office:spreadsheet>'
			. '<table:table table:name="Blad">'
			. '<table:table-row>'
			. '<table:table-cell office:value-type="float" office:value="10"><text:p>10</text:p></table:table-cell>'
			. '<table:table-cell table:formula="of:=[.A1]/0"><text:p>#DIV/0!</text:p></table:table-cell>'
			. '<table:table-cell table:formula="of:=[.A1]*2"><text:p>20</text:p></table:table-cell>'
			. '</table:table-row>'
			. '</table:table></office:spreadsheet></office:body></office:document-content>';

		$codec = new SpreadsheetCodec($this->io);
		$result = $codec->applyCellEdits(
			$this->pack(['content.xml' => $content]),
			'ods',
			[['cell' => 'Blad!A1', 'value' => '5']]
		);

		$this->assertContains('Blad!B1', $result['staleDependents']);
		$this->assertContains('Blad!C1', $result['staleDependents']);
		$this->assertSame(['Blad!B1'], $result['erroredDependents'], 'only the #DIV/0! cell is an error');
	}//end testAnErroredDependentIsReportedSeparately()
}//end class
