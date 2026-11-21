<?php

/**
 * Architecture test: filinq computes no retention (archiefwet-retention-engine REQ-DDARE-003).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Architecture
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-record-schemas-carry-retention-categories-computed-by-openregister-req-ddare-003
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * OpenRegister derives the archiefactiedatum, lists what is due and destroys
 * it; no class in lib/ writes that date, adds a bewaartermijn to a date, or
 * calls OpenRegister's destruction and certificate machinery itself.
 *
 * @coversNothing
 */
class RetentionArchitectureTest extends TestCase {

	/**
	 * Every PHP file under lib/, by path relative to the app.
	 *
	 * @return array<string, string> Path => source.
	 */
	private static function lib(): array {
		$root    = dirname(__DIR__, 3);
		$sources = [];
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/lib')) as $file) {
			if ($file->isFile() === true && $file->getExtension() === 'php') {
				$sources[substr($file->getPathname(), (strlen($root) + 1))] = (string) file_get_contents($file->getPathname());
			}
		}

		ksort($sources);

		return $sources;

	}//end lib()

	/**
	 * No class writes an archiefactiedatum: it is OpenRegister's to derive.
	 *
	 * @return void
	 */
	public function testNoClassWritesAnArchiefactiedatum(): void {
		$sources = self::lib();
		$this->assertArrayHasKey(key: 'lib/Service/RetentionSurfaceService.php', array: $sources);

		$writers = [];
		foreach ($sources as $path => $source) {
			if (preg_match('/\[\s*[\'"]archiefactiedatum[\'"]\s*\]\s*=[^=>]|[\'"]archiefactiedatum[\'"]\s*=>/', $source) === 1) {
				$writers[] = $path;
			}
		}

		$this->assertSame(expected: [], actual: $writers);

	}//end testNoClassWritesAnArchiefactiedatum()

	/**
	 * No class adds a bewaartermijn to a date or schedules a destruction.
	 *
	 * @return void
	 */
	public function testNoClassDoesRetentionArithmeticOrDestruction(): void {
		$found = [];
		foreach (self::lib() as $path => $source) {
			if (preg_match('/bewaartermijn[^;]*\)\s*->\s*add\s*\(|->add\s*\(\s*new\s+DateInterval\s*\([^)]*bewaartermijn/i', $source) === 1) {
				$found[] = $path . ': date arithmetic on a bewaartermijn';
			}

			// Code, not prose: a docblock may say what OpenRegister's job does.
			$code = '/use\s+[^;]*\\\\(DestructionCheckJob|DestructionService)\s*;|\b(DestructionCheckJob|DestructionService)::|->(generateCertificate|executeDestruction)\s*\(/';
			if (preg_match($code, $source) === 1) {
				$found[] = $path . ': OpenRegister destruction machinery';
			}
		}

		$this->assertSame(expected: [], actual: $found);

	}//end testNoClassDoesRetentionArithmeticOrDestruction()
}//end class
