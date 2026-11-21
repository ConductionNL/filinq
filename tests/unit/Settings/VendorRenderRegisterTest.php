<?php

/**
 * The register fields a vendor render needs
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/template-engine-renders-through-a-vendor/tasks.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * A template names its vendor template id; a generatedDocument can be pending with a job id.
 */
class VendorRenderRegisterTest extends TestCase {

	/**
	 * The register descriptor.
	 *
	 * @return array<string, mixed> The decoded register.
	 */
	private function register(): array {
		$parsed = json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json'), true);
		$this->assertIsArray(actual: $parsed);

		return $parsed;

	}//end register()

	/**
	 * The schemas carry the optional vendor fields and the pending state.
	 *
	 * @return void
	 */
	public function testTheSchemasCarryTheVendorFields(): void {
		$schemas  = $this->register()['components']['schemas'];
		$template = $schemas['template'];
		$document = $schemas['generatedDocument'];

		$this->assertSame(expected: 'string', actual: $template['properties']['vendorTemplateId']['type']);
		$this->assertNotContains(needle: 'vendorTemplateId', haystack: $template['required']);
		$this->assertSame(expected: ['generated', 'failed', 'pending'], actual: $document['properties']['status']['enum']);
		$this->assertSame(expected: 'string', actual: $document['properties']['vendorJobId']['type']);
		$this->assertSame(expected: 'string', actual: $document['properties']['engine']['type']);
		$this->assertNotContains(needle: 'vendorJobId', haystack: $document['required']);

	}//end testTheSchemasCarryTheVendorFields()

	/**
	 * The demo data holds a vendor template and a pending entry that names it.
	 *
	 * @return void
	 */
	public function testTheDemoDataHoldsAVendorTemplateAndAPendingEntry(): void {
		$bySlug = [];
		foreach ($this->register()['components']['objects'] as $object) {
			$bySlug[$object['@self']['schema'] . '/' . $object['@self']['slug']] = $object;
		}

		$template = $bySlug['template/besluit-via-documentleverancier'];
		$this->assertSame(expected: 'besluit-standaard', actual: $template['vendorTemplateId']);

		$entry = $bySlug['generatedDocument/demo-besluit-bij-documentleverancier'];
		$this->assertSame(expected: 'pending', actual: $entry['status']);
		$this->assertSame(expected: 'besluit-via-documentleverancier', actual: $entry['templateId']);
		$this->assertStringStartsWith(prefix: 'vendor:', string: $entry['engine']);
		$this->assertNotSame(expected: '', actual: $entry['vendorJobId']);

		$required = $this->register()['components']['schemas']['generatedDocument']['required'];
		foreach ($required as $field) {
			$this->assertArrayHasKey(key: $field, array: $entry);
		}

	}//end testTheDemoDataHoldsAVendorTemplateAndAPendingEntry()
}//end class
