<?php

/**
 * Unit tests that the server-side strings of the batch and image-redaction surfaces are catalogued.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/redaction-at-scale/tasks.md#5-quality
 * @spec openspec/changes/image-redaction/tasks.md#5-quality
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * `npm run test:l10n` scans `src/` only, so a literal `$this->l10n->t('…')`
 * in a controller can ship with no English source key and no Dutch value.
 * The user then reads the English message in a Dutch session. This test
 * reads the literal keys of the listed server files and asserts both
 * catalogues carry them.
 *
 * @coversNothing
 */
class ServerStringCatalogueTest extends TestCase {

	/**
	 * The server files whose messages reach the user on the batch and image-redaction screens.
	 */
	private const FILES = [
		'lib/Controller/BatchAnonymizationController.php',
		'lib/Controller/BatchLifecycleController.php',
		'lib/Service/BatchLifecycleService.php',
		'lib/Service/BatchRequestService.php',
		'lib/Controller/ScopeRedactionController.php',
		'lib/Service/Review/ScopeRedactionService.php',
		'lib/Service/Review/ImageRedactionService.php',
	];

	/**
	 * Every literal key of the listed files is in l10n/en.json.
	 *
	 * @return void
	 */
	public function testEveryServerKeyHasAnEnglishSource(): void {
		$english = $this->translations(locale: 'en');
		$missing = array_values(array_diff($this->usedKeys(), array_keys($english)));

		$this->assertSame([], $missing, 'Server strings without an entry in l10n/en.json.');

	}//end testEveryServerKeyHasAnEnglishSource()

	/**
	 * Every literal key of the listed files has a non-empty Dutch value.
	 *
	 * @return void
	 */
	public function testEveryServerKeyHasADutchValue(): void {
		$dutch   = $this->translations(locale: 'nl');
		$missing = [];
		foreach ($this->usedKeys() as $key) {
			if (isset($dutch[$key]) === false || $dutch[$key] === '' || $dutch[$key] === []) {
				$missing[] = $key;
			}
		}

		$this->assertSame([], $missing, 'Server strings without a value in l10n/nl.json.');

	}//end testEveryServerKeyHasADutchValue()

	/**
	 * The scan reads the files it names, so an empty result is a broken scan, not a clean tree.
	 *
	 * @return void
	 */
	public function testTheScanFindsTheBatchMessages(): void {
		$keys = $this->usedKeys();

		$this->assertContains('Batch not found', $keys);
		$this->assertContains('No files uploaded', $keys);

	}//end testTheScanFindsTheBatchMessages()

	/**
	 * The literal keys passed to `->t()` in the listed files, unique and in file order.
	 *
	 * @return array<int, string>
	 */
	private function usedKeys(): array {
		$keys = [];
		foreach (self::FILES as $file) {
			$path = dirname(__DIR__, 2).'/'.$file;
			$this->assertFileExists($path);
			$source = (string) file_get_contents($path);
			preg_match_all("/->t\(\s*'((?:[^'\\\\]|\\\\.)*)'/", $source, $matches);
			foreach ($matches[1] as $key) {
				$keys[] = stripcslashes($key);
			}
		}

		return array_values(array_unique($keys));

	}//end usedKeys()

	/**
	 * The translations of one shipped catalogue.
	 *
	 * @param string $locale The catalogue's locale, `en` or `nl`.
	 *
	 * @return array<string, string|array<int, string>>
	 */
	private function translations(string $locale): array {
		$path    = dirname(__DIR__, 2).'/l10n/'.$locale.'.json';
		$decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

		return $decoded['translations'];

	}//end translations()
}//end class
