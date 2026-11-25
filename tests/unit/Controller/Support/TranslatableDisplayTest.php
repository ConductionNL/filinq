<?php

/**
 * Unit tests for TranslatableDisplay.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller\Support
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://conduction.nl
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller\Support;

use OCA\Filinq\Controller\Support\TranslatableDisplay;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * A translatable value is shown as one text.
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */
#[CoversClass(TranslatableDisplay::class)]
final class TranslatableDisplayTest extends TestCase {

	/**
	 * The reader's language wins, then nl, then en, then the first text.
	 *
	 * @return void
	 */
	public function testTheReadersLanguageWinsThenNlThenEnThenTheFirst(): void {
		$english = new TranslatableDisplay(acceptLanguage: 'en-US,en;q=0.9,nl;q=0.8');
		$german  = new TranslatableDisplay(acceptLanguage: 'de-DE');
		$none    = new TranslatableDisplay(acceptLanguage: '');

		$this->assertSame(expected: 'Objection', actual: $english->text(map: ['nl' => 'Bezwaar', 'en' => 'Objection']));
		$this->assertSame(expected: 'Bezwaar', actual: $german->text(map: ['en' => 'Objection', 'nl' => 'Bezwaar']));
		$this->assertSame(expected: 'Objection', actual: $none->text(map: ['en' => 'Objection']));
		$this->assertSame(expected: 'Einspruch', actual: $english->text(map: ['fr' => '', 'de' => 'Einspruch']));
		$this->assertSame(expected: '', actual: $english->text(map: []));
	}//end testTheReadersLanguageWinsThenNlThenEnThenTheFirst()

	/**
	 * Only the named fields that hold a language map change; plain text and other fields stay.
	 *
	 * @return void
	 */
	public function testOnlyNamedLanguageMapsChange(): void {
		$display = new TranslatableDisplay(acceptLanguage: 'en');
		$rows    = [
			['uuid' => 'a', 'subject' => ['nl' => 'Bezwaarschrift'], 'channel' => ['scan']],
			['uuid' => 'b', 'subject' => 'Already text', 'rejectReason' => ['nl' => 'Niet voor ons', 'en' => 'Not ours']],
			'not a row',
		];

		$shown = $display->rows(rows: $rows, fields: ['subject', 'rejectReason']);

		$this->assertSame(expected: 'Bezwaarschrift', actual: $shown[0]['subject']);
		$this->assertSame(expected: ['scan'], actual: $shown[0]['channel']);
		$this->assertSame(expected: 'Already text', actual: $shown[1]['subject']);
		$this->assertSame(expected: 'Not ours', actual: $shown[1]['rejectReason']);
		$this->assertSame(expected: 'not a row', actual: $shown[2]);
	}//end testOnlyNamedLanguageMapsChange()
}//end class
