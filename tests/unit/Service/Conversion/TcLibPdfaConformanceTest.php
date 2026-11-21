<?php

/**
 * veraPDF conformance test for the tc-lib-pdf PDF/A-3b renderer
 *
 * Runs only when VERAPDF_BIN names a veraPDF command line; it is skipped
 * otherwise. Example with the official image:
 *
 *   printf '#!/bin/sh\nexec docker run --rm -v /tmp:/tmp verapdf/cli:latest "$@"\n' > /tmp/verapdf
 *   chmod +x /tmp/verapdf
 *   VERAPDF_BIN=/tmp/verapdf vendor/bin/phpunit tests/unit/Service/Conversion/TcLibPdfaConformanceTest.php
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use OCA\Filinq\Service\Conversion\TcLibPdfaRenderer;
use PHPUnit\Framework\TestCase;

/**
 * Validates tc-lib-pdf output against veraPDF's PDF/A-3b profile.
 */
class TcLibPdfaConformanceTest extends TestCase {

	/**
	 * Documents that exercise headings, inline styles, tables, plain text and non-Latin scripts.
	 *
	 * @return array<string, array{string}>
	 */
	public static function documents(): array {
		return [
			'decision letter' => ['<h1>Besluit op bezwaar</h1><p>José Müller-Lüdenscheidt, € 1.250,– “inclusief” btw. <b>Vet</b> en <i>cursief</i>.</p>'],
			'table' => ['<table border="1" cellpadding="4"><tr><th>Zaak</th><th>Status</th></tr><tr><td>Z-2026-001</td><td>Afgehandeld</td></tr></table>'],
			'plain text envelope' => ['<pre style="font-family:DejaVuSansMono,monospace; white-space:pre-wrap;">regel 1' . "\n" . 'regel 2</pre>'],
			'non-latin' => ['<p>Łukasz Żółć, Αλέξανδρος, Дмитрий</p>'],
		];
	}//end documents()

	/**
	 * Every document passes veraPDF's 3b profile.
	 *
	 * @param string $html The document.
	 *
	 * @return void
	 *
	 * @dataProvider documents
	 */
	public function testOutputPassesVeraPdf3b(string $html): void {
		$bin = (string) getenv('VERAPDF_BIN');
		if ($bin === '') {
			$this->markTestSkipped('Set VERAPDF_BIN to a veraPDF command line to run the conformance test.');
		}

		$path = sys_get_temp_dir() . '/filinq-tclib-' . bin2hex(random_bytes(4)) . '.pdf';
		file_put_contents($path, (new TcLibPdfaRenderer())->render(html: $html, title: 'Conformance'));

		$output = [];
		exec(escapeshellarg($bin) . ' --flavour 3b --format text ' . escapeshellarg($path) . ' 2>&1', $output);
		unlink($path);

		$this->assertStringStartsWith('PASS ', (string) end($output), implode("\n", $output));
	}//end testOutputPassesVeraPdf3b()
}//end class
