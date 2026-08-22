<?php

/**
 * Measures whether content-hash anchors survive a real office-suite re-serialisation.
 *
 * openspec/changes/office-suite-portability. Answers the anchor-stability known
 * unknown recorded in ADR-087.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service\Office
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

namespace OCA\Filinq\Tests\Unit\Service\Office;

use OCA\Filinq\Service\Editing\PackageCodec;
use OCA\Filinq\Service\Editing\XmlBlockScanner;
use PHPUnit\Framework\TestCase;

/**
 * Round-trips a document through a real suite and re-reads its anchors.
 *
 * THREE OUTCOMES, NOT TWO: passed, failed, and **not run**. The third is the point.
 * A test that silently skips when no suite is reachable reports green, and a green
 * "anchors survive the round-trip" that never opened a document converts an open
 * question into a false answer. The skip message therefore says NOT MEASURED in
 * as many words.
 *
 * A second trap is baked into the fixture. The first version of this measurement
 * used a `docx -> docx` conversion and reported success: the file grew 1172 -> 1394
 * bytes, which looked like a rewrite. Comparing every package part by md5 showed
 * all four byte-identical — the suite had passed the file through without parsing
 * it, and the size delta was ZIP overhead. The assertion was true and worthless.
 *
 * So this test asserts the REWRITE HAPPENED before it asserts anything about
 * anchors. Without that guard the whole file is a very elaborate way of comparing
 * a document to itself.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service\Office
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 */
class AnchorRoundTripTest extends TestCase {

	/**
	 * Base URL of the conversion service, from the environment.
	 *
	 * @var string
	 */
	private string $serviceUrl = '';

	/**
	 * Resolve the suite endpoint, or declare the measurement not run.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->serviceUrl = (string)getenv('FILINQ_OFFICE_CONVERT_URL');
		if ($this->serviceUrl === '') {
			$this->markTestSkipped(
				'NOT MEASURED: no office suite reachable. '
				. 'Set FILINQ_OFFICE_CONVERT_URL (see docs/office-suite-setup.md) to run it. '
				. 'This is NOT a pass — the anchor-stability question is unanswered in this run.'
			);
		}
	}//end setUp()

	/**
	 * REQ: content-hash anchors survive a genuine suite re-serialisation.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md
	 */
	public function testAnchorsSurviveASuiteReserialisation(): void {
		$original = $this->fixtureBytes();

		// docx -> odt -> docx. A same-format conversion is a PASSTHROUGH on at
		// least one shipping suite; a format change forces the engine to parse and
		// re-serialise, which is what a save does to the document body.
		$odt   = $this->convert(bytes: $original, from: 'docx', to: 'odt');
		$after = $this->convert(bytes: $odt, from: 'odt', to: 'docx');

		$this->assertNotSame(
			$this->documentPart(package: $original),
			$this->documentPart(package: $after),
			'the suite did not rewrite word/document.xml, so this measurement would be vacuous'
		);

		$codec  = new PackageCodec(new XmlBlockScanner());
		$before = $codec->readBlocks($original, 'docx');
		$result = $codec->readBlocks($after, 'docx');

		$this->assertSame(
			array_column($before['blocks'], 'anchor'),
			array_column($result['blocks'], 'anchor'),
			'content-hash anchors must resolve identically after a suite re-serialises the document'
		);
	}//end testAnchorsSurviveASuiteReserialisation()

	/**
	 * Extract word/document.xml from a package.
	 *
	 * @param string $package The package bytes.
	 *
	 * @return string The part bytes.
	 */
	private function documentPart(string $package): string {
		$tmp = tempnam(sys_get_temp_dir(), 'rt') . '.docx';
		file_put_contents($tmp, $package);

		$zip = new \ZipArchive();
		$zip->open($tmp);
		$xml = (string)$zip->getFromName('word/document.xml');
		$zip->close();
		unlink($tmp);

		return $xml;
	}//end documentPart()

	/**
	 * Build the fixture document.
	 *
	 * Carries a duplicated paragraph on purpose: identical text means identical
	 * hash, so the occurrence ordinal is the only thing separating the two anchors.
	 * If the ordinal did not survive re-ordering, this is where it would show.
	 *
	 * @return string The package bytes.
	 */
	private function fixtureBytes(): string {
		$word    = new \PhpOffice\PhpWord\PhpWord();
		$section = $word->addSection();
		$section->addTitle('Subsidiebesluit 2026', 1);
		$section->addText('The assessment period is eight weeks.');

		$run = $section->addTextRun();
		$run->addText('This paragraph carries ');
		$run->addText('a bold run', ['bold' => true]);
		$run->addText(' that must survive.');

		$section->addText('Kind regards,');
		$section->addText('The assessment period is eight weeks.');

		$tmp = tempnam(sys_get_temp_dir(), 'fx') . '.docx';
		\PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save($tmp);
		$bytes = (string)file_get_contents($tmp);
		unlink($tmp);

		return $bytes;
	}//end fixtureBytes()

	/**
	 * Convert bytes through the suite.
	 *
	 * @param string $bytes The input package.
	 * @param string $from  The input extension.
	 * @param string $to    The output extension.
	 *
	 * @return string The converted package bytes.
	 */
	private function convert(string $bytes, string $from, string $to): string {
		$hosted = $this->publish(bytes: $bytes, extension: $from);

		$payload = json_encode(
			[
				'async'      => false,
				'filetype'   => $from,
				'key'        => substr(sha1($bytes . $to), 0, 16),
				'outputtype' => $to,
				'title'      => 'roundtrip.' . $from,
				'url'        => $hosted,
			]
		);

		$context = stream_context_create(
			[
				'http' => [
					'method'        => 'POST',
					'header'        => "Content-Type: application/json\r\nAccept: application/json\r\n",
					'content'       => $payload,
					'timeout'       => 90,
					'ignore_errors' => true,
				],
			]
		);

		$raw = file_get_contents($this->serviceUrl, false, $context);
		$this->assertIsString($raw, 'the conversion service did not answer');

		$decoded = json_decode((string)$raw, true);
		$this->assertIsArray($decoded, 'the conversion service returned a non-JSON body: ' . (string)$raw);
		$this->assertArrayNotHasKey(
			'error',
			$decoded,
			'the suite refused the conversion: ' . (string)$raw
		);

		$converted = file_get_contents((string)$decoded['fileUrl']);
		$this->assertIsString($converted, 'the converted document could not be fetched');

		return $converted;
	}//end convert()

	/**
	 * Publish bytes at a URL the suite can fetch.
	 *
	 * @param string $bytes     The package bytes.
	 * @param string $extension The file extension.
	 *
	 * @return string The URL.
	 */
	private function publish(string $bytes, string $extension): string {
		$dir = (string)getenv('FILINQ_OFFICE_FIXTURE_DIR');
		$url = (string)getenv('FILINQ_OFFICE_FIXTURE_URL');

		if ($dir === '' || $url === '') {
			$this->markTestSkipped(
				'NOT MEASURED: FILINQ_OFFICE_FIXTURE_DIR / _URL are unset, so the suite '
				. 'has no way to fetch the document. This is NOT a pass.'
			);
		}

		$name = 'rt-' . substr(sha1($bytes), 0, 12) . '.' . $extension;
		file_put_contents(rtrim($dir, '/') . '/' . $name, $bytes);

		return rtrim($url, '/') . '/' . $name;
	}//end publish()
}//end class
