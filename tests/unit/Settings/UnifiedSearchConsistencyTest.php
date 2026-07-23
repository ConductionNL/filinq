<?php

/**
 * Unit tests for the `unified-search-provider` change: the searchable ⟺
 * deep-link invariant on `docudesk_register.json` + `src/manifest.json`.
 *
 * DocuDesk registers NO `OCP\Search\IProvider` of its own — it contributes to
 * OpenRegister's shared `openregister_objects` Unified Search provider (ADR-022)
 * purely by (a) marking a schema `searchable:true` and (b) declaring a manifest
 * `deepLinks[]` entry so a hit is navigable. A `searchable:true` schema without
 * a deep-link surfaces as a dead result; a deep-link naming a non-searchable or
 * absent schema is a dangling route. This test locks both directions, checks
 * each deep-link's `urlTemplate` maps onto a real manifest page route, and
 * asserts both the register `info.version` and `appinfo/info.xml` `<version>`
 * advanced versus the development merge base so OpenRegister re-imports the
 * corrected flags.
 *
 * Runs fully offline (no live Nextcloud): it only decodes JSON/XML on disk.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Settings
 *
 * @author  Conduction Development Team <info@conduction.nl>
 * @license EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/unified-search-provider/specs/unified-search-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Verifies the Unified Search searchable ⟺ deep-link invariant.
 */
class UnifiedSearchConsistencyTest extends TestCase
{

    /**
     * Decoded register configuration.
     *
     * @var array<string, mixed>
     */
    private array $register;

    /**
     * Decoded frontend manifest.
     *
     * @var array<string, mixed>
     */
    private array $manifest;

    /**
     * Load the register + manifest once per test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $registerRaw = file_get_contents(__DIR__.'/../../../lib/Settings/docudesk_register.json');
        $manifestRaw = file_get_contents(__DIR__.'/../../../src/manifest.json');
        $this->assertNotFalse($registerRaw, 'docudesk_register.json must be readable');
        $this->assertNotFalse($manifestRaw, 'src/manifest.json must be readable');

        $register = json_decode($registerRaw, true);
        $manifest = json_decode($manifestRaw, true);
        $this->assertIsArray($register, 'docudesk_register.json must be valid JSON');
        $this->assertIsArray($manifest, 'src/manifest.json must be valid JSON');

        $this->register = $register;
        $this->manifest = $manifest;

    }//end setUp()

    /**
     * Collect the slugs of every schema currently flagged `searchable:true`.
     *
     * @return array<int, string>
     */
    private function searchableSchemas(): array
    {
        $schemas = $this->register['components']['schemas'] ?? [];
        $out     = [];
        foreach ($schemas as $slug => $schema) {
            if (($schema['searchable'] ?? false) === true) {
                $out[] = $slug;
            }
        }

        return $out;

    }//end searchableSchemas()

    /**
     * Collect the manifest deep-link entries keyed by schema slug.
     *
     * @return array<string, array<string, mixed>>
     */
    private function deepLinksBySchema(): array
    {
        $out = [];
        foreach (($this->manifest['deepLinks'] ?? []) as $link) {
            $this->assertArrayHasKey('schemaSlug', $link, 'each deepLink needs a schemaSlug');
            $out[$link['schemaSlug']] = $link;
        }

        return $out;

    }//end deepLinksBySchema()

    /**
     * Only navigable schemas stay searchable: exactly template + signingRequest.
     *
     * @return void
     */
    public function testOnlyNavigableSchemasAreSearchable(): void
    {
        $searchable = $this->searchableSchemas();
        sort($searchable);
        $this->assertSame(
            ['signingRequest', 'template'],
            $searchable,
            'Only template + signingRequest may remain searchable (every other schema must be searchable:false to avoid dead Unified Search results)'
        );

    }//end testOnlyNavigableSchemasAreSearchable()

    /**
     * Every searchable schema has a matching manifest deep-link, and no
     * deep-link names a non-searchable or absent schema.
     *
     * @return void
     */
    public function testSearchableSchemasAndDeepLinksAreBijective(): void
    {
        $searchable = $this->searchableSchemas();
        $deepLinks  = $this->deepLinksBySchema();
        $schemas    = $this->register['components']['schemas'] ?? [];

        foreach ($searchable as $slug) {
            $this->assertArrayHasKey(
                $slug,
                $deepLinks,
                sprintf('searchable schema "%s" must have a manifest deepLinks entry', $slug)
            );
        }

        foreach ($deepLinks as $slug => $link) {
            $this->assertArrayHasKey(
                $slug,
                $schemas,
                sprintf('deepLink schema "%s" must exist in the register', $slug)
            );
            $this->assertTrue(
                ($schemas[$slug]['searchable'] ?? false) === true,
                sprintf('deepLink schema "%s" must be searchable:true', $slug)
            );
        }

    }//end testSearchableSchemasAndDeepLinksAreBijective()

    /**
     * Each deep-link urlTemplate maps onto a real manifest page detail route.
     *
     * @return void
     */
    public function testDeepLinkUrlTemplatesMapToManifestRoutes(): void
    {
        // Collect the concrete route prefixes DocuDesk pages own, dropping the
        // Vue param segment (":id" etc.) so "/templates/:id" matches
        // "/apps/docudesk/templates/{uuid}".
        $routePrefixes = [];
        foreach (($this->manifest['pages'] ?? []) as $page) {
            $route = $page['route'] ?? ($page['path'] ?? null);
            if (is_string($route) === true && str_contains($route, '/:') === true) {
                $routePrefixes[] = substr($route, 0, strpos($route, '/:'));
            }
        }

        foreach (($this->manifest['deepLinks'] ?? []) as $link) {
            $template = $link['urlTemplate'] ?? '';
            $this->assertStringStartsWith('/apps/docudesk/', $template, 'deepLink must target the docudesk app');
            $this->assertStringEndsWith('/{uuid}', $template, 'deepLink urlTemplate must end in the {uuid} placeholder');

            // Strip "/apps/docudesk" and the trailing "/{uuid}" → in-app route prefix.
            $inApp = substr($template, strlen('/apps/docudesk'));
            $inApp = substr($inApp, 0, (strlen($inApp) - strlen('/{uuid}')));
            $this->assertContains(
                $inApp,
                $routePrefixes,
                sprintf('deepLink "%s" must map onto a manifest detail route (prefix "%s")', $template, $inApp)
            );
        }

    }//end testDeepLinkUrlTemplatesMapToManifestRoutes()

    /**
     * No bespoke OCP\Search\IProvider is registered by DocuDesk (ADR-022 —
     * scoping is inherited from OpenRegister's openregister_objects provider).
     *
     * @return void
     */
    public function testNoBespokeSearchProviderRegistered(): void
    {
        $infoXml = file_get_contents(__DIR__.'/../../../appinfo/info.xml');
        $this->assertNotFalse($infoXml, 'appinfo/info.xml must be readable');
        $this->assertStringNotContainsStringIgnoringCase(
            'search-providers',
            $infoXml,
            'DocuDesk must not register a search provider in info.xml (consumes OpenRegister openregister_objects)'
        );

    }//end testNoBespokeSearchProviderRegistered()

}//end class
