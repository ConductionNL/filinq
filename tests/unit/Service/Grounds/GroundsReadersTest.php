<?php

/**
 * Source scan: no class reads Woo grounds from the `base` schema
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Grounds
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Grounds;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Every class in `lib/` that queries the `base` schema is one that reads a
 * tenant's own bases next to the Woo grounds, and takes those grounds from
 * the resolver (through `WooGroundLabels`). A new reader of `base` fails here
 * until it is either routed through the resolver or shown not to serve Woo
 * grounds.
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-every-grounds-reader-in-filinq-goes-through-the-resolver-req-grd-002
 */
#[CoversNothing]
class GroundsReadersTest extends TestCase {

	/**
	 * The classes that read `base` for a tenant's own bases, each with the Woo grounds from the resolver.
	 *
	 * @var list<string>
	 */
	private const TENANT_BASE_READERS = [
		'lib/Service/BaseLabelResolver.php',
		'lib/Service/DossierContextService.php',
		'lib/Service/LegalBasisCatalog.php',
	];

	/**
	 * A query naming the `base` schema, in the shapes the code base uses.
	 */
	private const BASE_QUERY = '/\bschema(?:Slug)?\s*:\s*[\'"]base[\'"]/';

	/**
	 * The PHP files under lib/ that query the `base` schema, relative to the app root.
	 *
	 * @return list<string>
	 */
	private static function baseReaders(): array {
		$root = dirname(__DIR__, 4);
		$found = [];
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/lib'));
		foreach ($files as $file) {
			if ($file->isFile() === false || $file->getExtension() !== 'php') {
				continue;
			}

			if (preg_match(self::BASE_QUERY, (string)file_get_contents($file->getPathname())) === 1) {
				$found[] = substr($file->getPathname(), strlen($root) + 1);
			}
		}

		sort($found);
		return $found;

	}//end baseReaders()

	/**
	 * Only the known tenant-base readers query `base`, and each takes Woo grounds from the resolver.
	 *
	 * @return void
	 */
	public function testNoClassReadsWooGroundsFromTheBaseSchema(): void {
		$readers = self::baseReaders();

		$this->assertSame(self::TENANT_BASE_READERS, $readers, 'A class outside the known readers queries the `base` schema.');

		$root = dirname(__DIR__, 4);
		foreach ($readers as $reader) {
			$this->assertStringContainsString(
				'WooGroundLabels',
				(string)file_get_contents($root . '/' . $reader),
				$reader . ' queries `base` but does not take the Woo grounds from the resolver.'
			);
		}

	}//end testNoClassReadsWooGroundsFromTheBaseSchema()

	/**
	 * The scan pattern finds the query shapes in use, so an empty scan cannot pass by accident.
	 *
	 * @return void
	 */
	public function testThePatternMatchesTheQueryShapes(): void {
		$this->assertSame(1, preg_match(self::BASE_QUERY, "searchObjectsBySlug(registerSlug: 'filinq', schemaSlug: 'base')"));
		$this->assertSame(1, preg_match(self::BASE_QUERY, "findAllOf(objectService: \$objectService, schema: 'base')"));
		$this->assertSame(0, preg_match(self::BASE_QUERY, "schema: 'baseline'"));

	}//end testThePatternMatchesTheQueryShapes()
}//end class
