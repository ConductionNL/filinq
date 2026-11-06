<?php

/**
 * Example data for DqZaak Gekoppeld: the Lindelaan dossier's documents find
 * case 2026-0110 and not the closed Woo request with the same title.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://conduction.nl
 *
 * @spec exclude example-set data integrity; no requirement covers the demo fragment
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * The case lookup of the three Lindelaan documents.
 */
class ZuiddrechtLindelaanDocumentsLookupTest extends TestCase {

	/**
	 * Each document's case lookup names the open case's Mijn Zuiddrecht
	 * reference, which the closed request with the same title does not carry.
	 *
	 * @return void
	 */
	public function testEachDocumentLooksUpTheOpenCaseByItsReference(): void {
		$set = json_decode((string)file_get_contents(__DIR__.'/../../../lib/Settings/example-sets/zuiddrecht.json'), true);
		$this->assertIsArray(actual: $set);

		$objects = array_column($set['objects'], null, 'key');
		foreach (['document-fietspad-lindelaan-1', 'document-fietspad-lindelaan-2', 'document-fietspad-lindelaan-3'] as $key) {
			$this->assertSame(
				expected: ['title' => 'Verlichting fietspad Lindelaan', 'externalReference' => 'MZ-ZDR-2026-0412'],
				actual: $objects[$key]['lookups']['case']['where'],
				message: $key
			);
		}
	}//end testEachDocumentLooksUpTheOpenCaseByItsReference()
}//end class
