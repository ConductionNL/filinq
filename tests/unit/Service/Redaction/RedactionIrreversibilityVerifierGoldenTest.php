<?php

/**
 * Golden results of the irreversibility verification.
 *
 * The whole result array for documents that may and may not be published,
 * recorded on the implementation before verify() and regionFor() were split.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Redaction;

use OCA\Filinq\Service\Redaction\RedactionIrreversibilityVerifier;
use OCA\Filinq\Service\Redaction\RedactionLeakRoute;
use PHPUnit\Framework\TestCase;

/**
 * Golden results for RedactionIrreversibilityVerifier.
 */
class RedactionIrreversibilityVerifierGoldenTest extends TestCase {

	/**
	 * The finding for one route.
	 *
	 * @param string   $route  The route.
	 * @param string[] $values The values found.
	 *
	 * @return array<string, mixed>
	 */
	private function finding(string $route, array $values): array {
		return ['route' => $route, 'description' => RedactionLeakRoute::DESCRIPTIONS[$route], 'values' => $values];
	}//end finding()

	/**
	 * A clean document is published, with every route checked.
	 *
	 * @return void
	 */
	public function testACleanDocumentIsAllowed(): void {
		$verifier = new RedactionIrreversibilityVerifier();
		$bytes = "%PDF-1.7\n<< /Annots [] >>\nendobj\n<x:xmpmeta>gemeente</x:xmpmeta>\n%%EOF\n";

		$result = $verifier->verify($bytes, ['Fatima El-Amrani', 'ab', '  '], 'pdf');

		$this->assertSame(
			['verdict' => 'clean', 'outputMode' => 'pdf', 'routesChecked' => RedactionLeakRoute::ALL, 'findings' => [], 'mayBePublished' => true],
			$result
		);
		$this->assertTrue($verifier->mayBePublished($result));

	}//end testACleanDocumentIsAllowed()

	/**
	 * Every region route, an unterminated region and an earlier revision, refused with findings in route order.
	 *
	 * @return void
	 */
	public function testALeakingDocumentIsRefused(): void {
		$verifier = new RedactionIrreversibilityVerifier();
		$bytes = "%PDF-1.7\nBT (Jan Jansen) Tj ET\n%%EOF\n"
			."<x:xmpmeta>JAN JANSEN</x:xmpmeta>\n"
			."/Thumb stream Piet Pietersen endobj\n"
			."/AcroForm << /V (Klaas) >> endobj\n"
			."/EmbeddedFile stream jan jansen";

		$result = $verifier->verify($bytes, ['Jan Jansen', 'jan jansen', 'Piet Pietersen', 'Klaas', 'Onbekend']);

		$expected = [];
		foreach (RedactionLeakRoute::ALL as $route) {
			$values = [
				RedactionLeakRoute::TEXT_UNDER_MARK => ['jan jansen', 'piet pietersen', 'klaas'],
				RedactionLeakRoute::EMBEDDED_PREVIEW => ['piet pietersen'],
				RedactionLeakRoute::XMP => ['jan jansen'],
				RedactionLeakRoute::EXIF => ['jan jansen', 'piet pietersen', 'klaas'],
				RedactionLeakRoute::INCREMENTAL_UPDATE => [],
				RedactionLeakRoute::ANNOTATIONS_AND_FIELDS => ['klaas'],
				RedactionLeakRoute::EMBEDDED_ATTACHMENT => ['jan jansen'],
			][$route] ?? ['jan jansen', 'piet pietersen', 'klaas'];
			if ($values !== []) {
				$expected[] = $this->finding($route, $values);
			}
		}

		$this->assertSame(
			['verdict' => 'leaking', 'outputMode' => '', 'routesChecked' => RedactionLeakRoute::ALL, 'findings' => $expected, 'mayBePublished' => false],
			$result
		);
		$this->assertFalse($verifier->mayBePublished($result));

	}//end testALeakingDocumentIsRefused()

	/**
	 * Only the part before the last end-of-file marker counts as an earlier revision.
	 *
	 * @return void
	 */
	public function testAnEarlierRevisionIsRefused(): void {
		$verifier = new RedactionIrreversibilityVerifier();
		$bytes = "%PDF-1.7\n(Jan Jansen)\n%%EOF\n(revision 2)\n%%EOF\n(after)";

		$result = $verifier->verify($bytes, ['revision 2', 'after']);
		$routes = array_column($result['findings'], 'values', 'route');

		$this->assertSame(['revision 2'], $routes[RedactionLeakRoute::INCREMENTAL_UPDATE]);
		$this->assertArrayNotHasKey(RedactionLeakRoute::XMP, $routes);
		$this->assertSame('leaking', $result['verdict']);

	}//end testAnEarlierRevisionIsRefused()

	/**
	 * Empty bytes and no usable values are refused as unverifiable, with the reason.
	 *
	 * @return void
	 */
	public function testUnverifiableIsRefused(): void {
		$verifier = new RedactionIrreversibilityVerifier();
		$empty = $verifier->verify(" \n", [], 'docx');
		$this->assertSame(
			[
				'verdict' => 'unverifiable',
				'outputMode' => 'docx',
				'routesChecked' => [],
				'findings' => [],
				'mayBePublished' => false,
				'reason' => 'the written copy is empty, so nothing could be examined',
			],
			$empty
		);

		$nothing = $verifier->verify('%PDF-1.7 Jan', ['Jo', ' ', 12]);
		$this->assertSame('no redacted values were supplied, so there was nothing to look for', $nothing['reason']);
		$this->assertSame('unverifiable', $nothing['verdict']);
		$this->assertFalse($verifier->mayBePublished($nothing));
		$this->assertFalse($verifier->mayBePublished(['verdict' => 'clean']));
		$this->assertFalse($verifier->mayBePublished(['mayBePublished' => true]));

	}//end testUnverifiableIsRefused()
}//end class
