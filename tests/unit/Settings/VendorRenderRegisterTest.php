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
		$this->assertIsArray($parsed);

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

		$this->assertSame('string', $template['properties']['vendorTemplateId']['type']);
		$this->assertNotContains('vendorTemplateId', $template['required']);
		$this->assertSame(['generated', 'failed', 'pending'], $document['properties']['status']['enum']);
		$this->assertSame('string', $document['properties']['vendorJobId']['type']);
		$this->assertSame('string', $document['properties']['engine']['type']);
		$this->assertNotContains('vendorJobId', $document['required']);

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
		$this->assertSame('besluit-standaard', $template['vendorTemplateId']);

		$entry = $bySlug['generatedDocument/demo-besluit-bij-documentleverancier'];
		$this->assertSame('pending', $entry['status']);
		$this->assertSame('besluit-via-documentleverancier', $entry['templateId']);
		$this->assertStringStartsWith('vendor:', $entry['engine']);
		$this->assertNotSame('', $entry['vendorJobId']);

		$required = $this->register()['components']['schemas']['generatedDocument']['required'];
		foreach ($required as $field) {
			$this->assertArrayHasKey($field, $entry);
		}

	}//end testTheDemoDataHoldsAVendorTemplateAndAPendingEntry()
}//end class
