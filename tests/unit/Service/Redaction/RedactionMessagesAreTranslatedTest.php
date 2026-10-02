<?php

/**
 * Every message these gates can produce exists in Dutch.
 *
 * 🔴 THE FAILURE THIS CATCHES IS THE ONE NOBODY REPORTS. A refusal added six
 * months from now renders in English for every Dutch reader, and nothing
 * throws, nothing logs, and the person who sees it assumes the product is
 * English. So this does not check the nine strings that exist today: it reads
 * the `say()` calls out of the source and checks whatever it finds.
 *
 * 🔴 AND A DUTCH ENTRY EQUAL TO ITS ENGLISH KEY IS NOT A TRANSLATION. That is
 * what the extraction step writes as a placeholder, and it passes a presence
 * check while reading exactly like the untranslated string it stands for.
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

use PHPUnit\Framework\TestCase;

/**
 * Tests that the redaction gates' messages are in the catalogues.
 */
class RedactionMessagesAreTranslatedTest extends TestCase {

	/**
	 * The classes whose messages a user reads.
	 *
	 * @var array<int, string>
	 */
	private const SOURCES = [
		'lib/Service/Redaction/RedactionReviewGate.php',
		'lib/Service/Redaction/DownloadAgreementGate.php',
	];

	/**
	 * Every message reaches the reader in Dutch.
	 *
	 * @return void
	 */
	public function testEveryMessageHasADutchTranslation(): void {
		$dutch = $this->catalogue(locale: 'nl');
		$messages = $this->messagesInSource();

		$this->assertNotEmpty(
			$messages,
			'reading no messages out of the source would pass this test on an empty catalogue'
		);

		foreach ($messages as $message) {
			$this->assertArrayHasKey(
				$message,
				$dutch,
				sprintf('this message reaches a Dutch reader in English: "%s"', $message)
			);
			$this->assertNotSame(
				$message,
				$dutch[$message],
				sprintf('the Dutch entry is the English source, which is a placeholder: "%s"', $message)
			);
		}

	}//end testEveryMessageHasADutchTranslation()

	/**
	 * Every message has an English source entry for translators to pick up.
	 *
	 * @return void
	 */
	public function testEveryMessageHasAnEnglishSourceEntry(): void {
		$english = $this->catalogue(locale: 'en');

		foreach ($this->messagesInSource() as $message) {
			$this->assertArrayHasKey($message, $english);
		}

	}//end testEveryMessageHasAnEnglishSourceEntry()

	/**
	 * The strings these classes pass to say(), read out of the source.
	 *
	 * @return array<int, string> The messages.
	 */
	private function messagesInSource(): array {
		$messages = [];

		foreach (self::SOURCES as $relative) {
			$source = (string)file_get_contents($this->pathTo(relative: $relative));

			// Each say() call, with its argument possibly concatenated over
			// several lines the way the coding standard wraps them.
			preg_match_all('/say\(\s*message:\s*((?:\'(?:[^\'\\\\]|\\\\.)*\'\s*\.?\s*)+)\)/', $source, $calls);

			foreach ($calls[1] as $argument) {
				preg_match_all('/\'((?:[^\'\\\\]|\\\\.)*)\'/', $argument, $pieces);
				$messages[] = str_replace(["\\'", '\\\\'], ["'", '\\'], implode('', $pieces[1]));
			}
		}

		return array_values(array_unique($messages));

	}//end messagesInSource()

	/**
	 * One locale catalogue.
	 *
	 * @param string $locale The locale.
	 *
	 * @return array<string, string> The translations.
	 */
	private function catalogue(string $locale): array {
		$decoded = json_decode(
			(string)file_get_contents($this->pathTo(relative: 'l10n/'.$locale.'.json')),
			true
		);

		return (array)($decoded['translations'] ?? []);

	}//end catalogue()

	/**
	 * One path inside the app root.
	 *
	 * @param string $relative The path relative to the app root.
	 *
	 * @return string The absolute path.
	 */
	private function pathTo(string $relative): string {
		return dirname(__DIR__, 4).'/'.$relative;

	}//end pathTo()
}//end class
