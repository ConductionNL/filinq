<?php

/**
 * Unit tests for the dossier register configuration in docudesk_register.json.
 *
 * Verifies register shape, schema shape, seed object completeness, and edge-case
 * seed values (empty bases, null checkedOn) per the add-dossier-schema spec.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Settings
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/add-dossier-schema/tasks.md#task-10
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Tests the shape and completeness of the dossier register config in docudesk_register.json.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DossierRegisterConfigTest extends TestCase
{

    /**
     * The parsed register JSON as an associative array.
     *
     * @var array<string, mixed>
     */
    private array $config;


    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $path         = __DIR__.'/../../../lib/Settings/docudesk_register.json';
        $json         = file_get_contents($path);
        $this->config = json_decode($json, associative: true);

    }//end setUp()


    // -----------------------------------------------------------------------
    // Register shape (task 10a)
    // -----------------------------------------------------------------------


    /**
     * The dossier register entry exists with the correct slug and schema list.
     */
    public function testDossierRegisterExists(): void
    {
        $registers = $this->config['components']['registers'];
        $this->assertArrayHasKey('dossier', $registers, 'dossier register must be present');

        $reg = $registers['dossier'];
        $this->assertSame('dossier', $reg['slug']);
        $this->assertContains('dossier', $reg['schemas']);
        $this->assertContains('base', $reg['schemas']);

    }//end testDossierRegisterExists()


    /**
     * The base schema has all required structural fields.
     */
    public function testBaseSchemaShape(): void
    {
        $schemas = $this->config['components']['schemas'];
        $this->assertArrayHasKey('base', $schemas, 'base schema must be present');

        $base = $schemas['base'];
        $this->assertSame('base', $base['slug']);
        $this->assertContains('name', $base['required']);
        $this->assertContains('description', $base['required']);
        $this->assertSame('name', $base['configuration']['objectNameField']);
        $this->assertArrayHasKey('name', $base['properties']);
        $this->assertArrayHasKey('description', $base['properties']);

    }//end testBaseSchemaShape()


    /**
     * The dossier schema has all required structural fields and correct $ref on bases.
     */
    public function testDossierSchemaShape(): void
    {
        $schemas = $this->config['components']['schemas'];
        $this->assertArrayHasKey('dossier', $schemas, 'dossier schema must be present');

        $dossier = $schemas['dossier'];
        $this->assertSame('dossier', $dossier['slug']);
        $this->assertContains('name', $dossier['required']);
        $this->assertSame('name', $dossier['configuration']['objectNameField']);

        $props = $dossier['properties'];
        $this->assertArrayHasKey('name', $props);
        $this->assertArrayHasKey('description', $props);
        $this->assertArrayHasKey('bases', $props);
        $this->assertArrayHasKey('checkedOn', $props);

        // bases must use $ref for referential integrity.
        $this->assertSame('array', $props['bases']['type']);
        $this->assertArrayHasKey('$ref', $props['bases']['items']);
        $this->assertStringContainsString('base', $props['bases']['items']['$ref']);

        // checkedOn must be date-time so auditing works.
        $this->assertSame('date-time', $props['checkedOn']['format']);

        // facetable flags required by the spec.
        $this->assertTrue($props['bases']['facetable']);
        $this->assertTrue($props['checkedOn']['facetable']);

    }//end testDossierSchemaShape()


    // -----------------------------------------------------------------------
    // Seed object completeness (task 10b)
    // -----------------------------------------------------------------------


    /**
     * Exactly six base seed objects are present with the correct canonical slugs.
     */
    public function testSixBaseSeedObjects(): void
    {
        $objects   = $this->config['components']['objects'];
        $baseSlugs = array_column(
            array_filter($objects, static fn ($o) => ($o['@self']['schema'] ?? '') === 'base'),
            null,
            null,
        );

        $slugs = array_map(static fn ($o) => $o['@self']['slug'], $baseSlugs);

        $this->assertCount(6, $baseSlugs, 'Exactly six base seed objects required');
        $this->assertContains('persoonsgegevens', $slugs);
        $this->assertContains('bijzondere-persoonsgegevens', $slugs);
        $this->assertContains('strafrechtelijk', $slugs);
        $this->assertContains('bedrijfs-fabricagegegevens', $slugs);
        $this->assertContains('onevenredige-benadeling', $slugs);
        $this->assertContains('nationale-veiligheid', $slugs);

    }//end testSixBaseSeedObjects()


    /**
     * Exactly five dossier seed objects are present with non-empty names.
     */
    public function testFiveDossierSeedObjects(): void
    {
        $objects      = $this->config['components']['objects'];
        $dossierSeeds = array_values(
            array_filter($objects, static fn ($o) => ($o['@self']['schema'] ?? '') === 'dossier'),
        );

        $this->assertCount(5, $dossierSeeds, 'Exactly five dossier seed objects required');

        foreach ($dossierSeeds as $seed) {
            $this->assertNotEmpty($seed['name'], 'Every dossier seed must have a non-empty name');
            $this->assertArrayHasKey('folder', $seed['@self'], 'Every dossier seed must declare a folder binding');
        }

    }//end testFiveDossierSeedObjects()


    /**
     * Each base seed object carries both required fields.
     */
    public function testBaseSeedObjectsHaveRequiredFields(): void
    {
        $objects   = $this->config['components']['objects'];
        $baseSeeds = array_values(
            array_filter($objects, static fn ($o) => ($o['@self']['schema'] ?? '') === 'base'),
        );

        foreach ($baseSeeds as $seed) {
            $slug = $seed['@self']['slug'];
            $this->assertNotEmpty($seed['name'], "Base seed '$slug' must have a non-empty name");
            $this->assertNotEmpty($seed['description'], "Base seed '$slug' must have a non-empty description");
        }

    }//end testBaseSeedObjectsHaveRequiredFields()


    // -----------------------------------------------------------------------
    // Edge-case seed values (task 10c)
    // -----------------------------------------------------------------------


    /**
     * At least one dossier seed has empty bases AND null checkedOn (unreviewed state).
     */
    public function testAtLeastOneDossierWithEmptyBasesAndNullCheckedOn(): void
    {
        $objects      = $this->config['components']['objects'];
        $dossierSeeds = array_values(
            array_filter($objects, static fn ($o) => ($o['@self']['schema'] ?? '') === 'dossier'),
        );

        $found = false;
        foreach ($dossierSeeds as $seed) {
            $basesEmpty    = isset($seed['bases']) && $seed['bases'] === [];
            $checkedOnNull = array_key_exists('checkedOn', $seed) && $seed['checkedOn'] === null;
            if ($basesEmpty && $checkedOnNull) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'At least one dossier seed must have bases: [] and checkedOn: null');

    }//end testAtLeastOneDossierWithEmptyBasesAndNullCheckedOn()


    /**
     * Dossier seeds that have bases reference objects in the base schema.
     */
    public function testDossierSeedBasesReferenceSlugsOrEmpty(): void
    {
        $objects      = $this->config['components']['objects'];
        $dossierSeeds = array_values(
            array_filter($objects, static fn ($o) => ($o['@self']['schema'] ?? '') === 'dossier'),
        );

        foreach ($dossierSeeds as $seed) {
            if (empty($seed['bases']) === false) {
                foreach ($seed['bases'] as $baseRef) {
                    $this->assertIsArray($baseRef, 'Each bases entry must be an array (slug reference)');
                    $this->assertArrayHasKey('slug', $baseRef, 'Each bases entry must have a slug key');
                    $this->assertNotEmpty($baseRef['slug']);
                }
            }
        }

    }//end testDossierSeedBasesReferenceSlugsOrEmpty()


}//end class
