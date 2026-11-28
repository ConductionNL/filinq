<?php

/**
 * The two Woo templates filinq seeds: present with their stable slugs and the
 * woo-decision contract, and shaped so an organisation's edit survives an upgrade.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * @link https://conduction.nl
 * @spec openspec/changes/archive/2026-11-26-woo-request-workflow/specs/woo-request-workflow/spec.md#requirement-filinq-seeds-two-organisation-editable-woo-templates-req-ddwrw-006
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * `woo-besluit` and `woo-inventarislijst` in the register's seed objects.
 */
class WooTemplateSeedTest extends TestCase {

	/**
	 * The seeded templates by slug.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function seededTemplates(): array {
		$register  = json_decode((string) file_get_contents(__DIR__.'/../../../lib/Settings/filinq_register.json'), true);
		$templates = [];
		foreach ($register['components']['objects'] as $object) {
			if (($object['@self']['schema'] ?? '') === 'template') {
				$templates[(string) ($object['@self']['slug'] ?? '')] = $object;
			}
		}

		return $templates;

	}//end seededTemplates()

	/**
	 * Both slugs exist, in the woo namespace, declaring the contract and a Twig body the template schema accepts.
	 *
	 * @return void
	 */
	public function testBothTemplatesAreSeeded(): void {
		$templates = self::seededTemplates();
		$schema    = json_decode((string) file_get_contents(__DIR__.'/../../../lib/Settings/filinq_register.json'), true)['components']['schemas']['template'];

		foreach (['woo-besluit', 'woo-inventarislijst'] as $slug) {
			$this->assertArrayHasKey($slug, $templates, $slug);
			$template = $templates[$slug];
			$this->assertSame(['filinq', 'template'], [$template['@self']['register'], $template['@self']['schema']], $slug);
			$this->assertSame($slug, $template['slug'], $slug);
			$this->assertSame('woo', $template['namespace'], $slug);
			$this->assertSame('woo-decision', $template['dataContract'], $slug);
			$this->assertStringContainsString('wooDecision.documents', $template['content'], $slug);
			foreach ($schema['required'] as $field) {
				$this->assertNotSame('', (string) ($template[$field] ?? ''), $slug.': '.$field);
			}

			$this->assertSame([], array_diff(array_keys(array_diff_key($template, ['@self' => true])), array_keys($schema['properties'])), $slug);
		}

	}//end testBothTemplatesAreSeeded()

	/**
	 * An edited template survives an upgrade.
	 *
	 * OpenRegister matches a seed to the stored object by register, schema and
	 * slug, rewrites it only when the seed's own version moves above the one it
	 * shipped, and otherwise follows changed shipped content only on an object
	 * nobody edited. So the seeds must carry no uuid (which would pin a new
	 * object) and no version of their own (which would overwrite every edit
	 * the day it is raised).
	 *
	 * @return void
	 */
	public function testAnEditedTemplateSurvivesAnUpgrade(): void {
		$templates = self::seededTemplates();

		foreach (['woo-besluit', 'woo-inventarislijst'] as $slug) {
			$self = $templates[$slug]['@self'];
			$this->assertSame(['register', 'schema', 'slug'], array_keys($self), $slug);
			$this->assertArrayNotHasKey('version', $templates[$slug], $slug);
			$this->assertArrayNotHasKey('uuid', $templates[$slug], $slug);
			$this->assertArrayNotHasKey('id', $templates[$slug], $slug);
		}

	}//end testAnEditedTemplateSurvivesAnUpgrade()
}//end class
