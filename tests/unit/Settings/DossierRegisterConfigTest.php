<?php

/**
 * Unit tests for the dossier-register additions to `docudesk_register.json`.
 *
 * Asserts the JSON contract added by the `add-dossier-schema` change:
 *   - register `dossier` exists with schemas `dossier` + `base`
 *   - schema `dossier` has required `name`, optional `bases` + `checkedOn`
 *   - schema `base` has required `name` + `description`
 *   - seed-object set includes the six canonical Woo Art. 5 grondslagen
 *   - at least five seed dossier objects across the persona personas
 *   - at least one seed dossier carries empty `bases` AND null `checkedOn`
 *     (covers the optionality cases)
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Settings
 *
 * @author  Conduction Development Team <info@conduction.nl>
 * @license EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/add-dossier-schema/tasks.md "6.1"
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Verifies the dossier register / schemas / seed contract.
 */
class DossierRegisterConfigTest extends TestCase
{

    private array $config;

    protected function setUp(): void
    {
        $path = __DIR__.'/../../../lib/Settings/docudesk_register.json';
        $raw  = file_get_contents($path);
        $this->assertNotFalse($raw, 'docudesk_register.json must be readable');

        $decoded = json_decode($raw, true);
        $this->assertIsArray($decoded, 'docudesk_register.json must be valid JSON');
        $this->config = $decoded;
    }

    public function testDossierRegisterExistsWithBothSchemas(): void
    {
        $registers = $this->config['components']['registers'] ?? [];
        $this->assertArrayHasKey('dossier', $registers, 'dossier register MUST be declared');

        $dossier = $registers['dossier'];
        $this->assertSame('dossier', $dossier['slug']);
        $this->assertContains('dossier', $dossier['schemas']);
        $this->assertContains('base', $dossier['schemas']);
    }

    public function testDossierSchemaHasRequiredNameAndOptionalBasesCheckedOn(): void
    {
        $schemas = $this->config['components']['schemas'] ?? [];
        $this->assertArrayHasKey('dossier', $schemas);

        $dossier = $schemas['dossier'];
        $this->assertContains('name', $dossier['required'] ?? [], 'dossier.name MUST be required');
        $this->assertNotContains('bases', $dossier['required'] ?? []);
        $this->assertNotContains('checkedOn', $dossier['required'] ?? []);

        $this->assertArrayHasKey('name', $dossier['properties']);
        $this->assertArrayHasKey('bases', $dossier['properties']);
        $this->assertArrayHasKey('checkedOn', $dossier['properties']);
        $this->assertSame('array', $dossier['properties']['bases']['type']);
        $this->assertSame('string', $dossier['properties']['bases']['items']['type']);
    }

    public function testBaseSchemaHasRequiredNameAndDescription(): void
    {
        $schemas = $this->config['components']['schemas'] ?? [];
        $this->assertArrayHasKey('base', $schemas);

        $base = $schemas['base'];
        $this->assertContains('name', $base['required'] ?? []);
        $this->assertContains('description', $base['required'] ?? []);

        $this->assertArrayHasKey('name', $base['properties']);
        $this->assertArrayHasKey('description', $base['properties']);
    }

    public function testSeedObjectsIncludeAllCanonicalWooArt5Grondslagen(): void
    {
        // The merge adopted Robert's canonical grondslagen taxonomy: the Woo
        // Art. 5 A–S legend (uitzonderingsgronden), which supersedes
        // development's earlier six ad-hoc bases. The seed slugs follow the
        // article numbering (art-5-1-1-a … art-5-2-2).
        $expectedSlugs = [
            'art-5-1-1-a',
            'art-5-1-1-b',
            'art-5-1-1-c',
            'art-5-1-1-d',
            'art-5-1-1-e',
            'art-5-1-2-a',
            'art-5-1-2-b',
            'art-5-1-2-c',
            'art-5-1-2-d',
            'art-5-1-2-e',
            'art-5-1-2-f',
            'art-5-1-2-g',
            'art-5-1-2-h',
            'art-5-1-2-i',
            'art-5-1-4',
            'art-5-1-5',
            'art-5-1-6',
            'art-5-2-1',
            'art-5-2-2',
        ];

        $baseSeeds = $this->seedObjectsForSchema('base');

        $foundSlugs = array_map(
            static fn (array $object): string => (string) ($object['@self']['slug'] ?? ''),
            $baseSeeds
        );

        foreach ($expectedSlugs as $slug) {
            $this->assertContains($slug, $foundSlugs, "Seed base '$slug' MUST be present");
        }

        $this->assertCount(
            count($expectedSlugs),
            $baseSeeds,
            'The canonical Woo Art. 5 A–S legend must seed exactly '.count($expectedSlugs).' base objects'
        );
    }

    public function testFiveOrMoreSeedDossiersExist(): void
    {
        $dossierSeeds = $this->seedObjectsForSchema('dossier');
        $this->assertGreaterThanOrEqual(5, count($dossierSeeds));
    }

    public function testAtLeastOneSeedDossierExercisesOptionalityCases(): void
    {
        $dossierSeeds = $this->seedObjectsForSchema('dossier');

        $matches = array_filter(
            $dossierSeeds,
            static fn (array $object): bool =>
                ($object['bases'] ?? null) === []
                && array_key_exists('checkedOn', $object) === true
                && $object['checkedOn'] === null
        );

        $this->assertGreaterThanOrEqual(
            1,
            count($matches),
            'At least one seed dossier MUST have bases=[] AND checkedOn=null'
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function seedObjectsForSchema(string $schemaSlug): array
    {
        $objects = $this->config['components']['objects'] ?? [];

        return array_values(array_filter(
            $objects,
            static fn (array $object): bool =>
                ($object['@self']['schema'] ?? null) === $schemaSlug
        ));
    }
}
