<?php

/**
 * Unit tests for the dossier-register additions to `filinq_register.json`.
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
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author  Conduction Development Team <info@conduction.nl>
 * @license EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/add-dossier-schema/tasks.md "6.1"
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Verifies the dossier register / schemas / seed contract.
 */
class DossierRegisterConfigTest extends TestCase {

	private array $config;

	protected function setUp(): void {
		$path = __DIR__ . '/../../../lib/Settings/filinq_register.json';
		$raw = file_get_contents($path);
		$this->assertNotFalse($raw, 'filinq_register.json must be readable');

		$decoded = json_decode($raw, true);
		$this->assertIsArray($decoded, 'filinq_register.json must be valid JSON');
		$this->config = $decoded;
	}

	/**
	 * The two dossier schemas now live in the consolidated `filinq` register.
	 *
	 * This assertion used to demand a register named `dossier`. It was rewritten
	 * rather than deleted: the CONTRACT it protects is "the dossier and base
	 * schemas are reachable through a register this app addresses", and that
	 * contract survived the five-into-one consolidation — only the register did
	 * not. Deleting the test would have removed the check along with the stale
	 * name.
	 *
	 * The negative assertion matters as much as the positive one. A leftover
	 * `dossier` entry in `components.registers` would make the import create a
	 * SECOND register beside `filinq`, and every object written through it would
	 * be invisible to an app that now addresses `filinq` — no error, just an
	 * empty collection.
	 *
	 * @return void
	 */
	public function testDossierSchemasLiveInTheConsolidatedRegister(): void {
		$registers = $this->config['components']['registers'] ?? [];

		$this->assertArrayHasKey('filinq', $registers, 'the consolidated filinq register MUST be declared');
		$this->assertSame(
			['filinq'],
			array_keys($registers),
			'exactly ONE register may be declared; a second one silently forks the objects'
		);

		$filinq = $registers['filinq'];
		$this->assertSame('filinq', $filinq['slug']);
		$this->assertContains('dossier', $filinq['schemas']);
		$this->assertContains('base', $filinq['schemas']);
	}

	/**
	 * The declared register slug must equal `x-openregister.app`.
	 *
	 * For a `type: application` configuration ImportHandler resolves the
	 * auto-created register as `$slug = $xOpenregister['app'] ?? $appId`. If
	 * that field and the declared register slug disagree, the import creates
	 * BOTH — the declared one and the app one — and the app addresses whichever
	 * its call sites happen to name. That is the failure mode this whole change
	 * exists to remove, so it is pinned here.
	 *
	 * @return void
	 */
	public function testTheDeclaredRegisterSlugMatchesXOpenregisterApp(): void {
		$this->assertSame(
			$this->config['x-openregister']['app'] ?? null,
			$this->config['components']['registers']['filinq']['slug'] ?? null
		);
	}

	/**
	 * Every declared schema is bound to the one register.
	 *
	 * The consolidation is only complete if the single register lists ALL the
	 * schemas the five used to carry between them. A schema left out is not an
	 * error at import time — it simply never becomes reachable, and its objects
	 * have nowhere to be moved to.
	 *
	 * @return void
	 */
	public function testEverySchemaIsBoundToTheConsolidatedRegister(): void {
		$declared = array_keys($this->config['components']['schemas'] ?? []);
		$bound = $this->config['components']['registers']['filinq']['schemas'] ?? [];

		sort($declared);
		sort($bound);

		$this->assertSame($declared, $bound, 'every declared schema MUST be bound to the filinq register');
	}

	/**
	 * No seed object may still name one of the five retired registers.
	 *
	 * A seed object carrying `@self.register: "dossier"` would be imported into
	 * a register the app no longer addresses — the exact silent-fork this change
	 * removes, reintroduced through the seed data.
	 *
	 * @return void
	 */
	public function testNoSeedObjectNamesARetiredRegister(): void {
		$retired = ['consent', 'signing', 'templates', 'document', 'dossier'];

		foreach (($this->config['components']['objects'] ?? []) as $index => $object) {
			$register = $object['@self']['register'] ?? null;
			$this->assertNotContains(
				$register,
				$retired,
				sprintf('seed object #%d still names the retired register `%s`', $index, (string)$register)
			);
			$this->assertSame('filinq', $register, sprintf('seed object #%d must target `filinq`', $index));
		}
	}

	public function testDossierSchemaHasRequiredNameAndOptionalBasesCheckedOn(): void {
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

	/**
	 * `status` and `documents` are OPTIONAL and additive.
	 *
	 * Both were added for the dossier-management surface and existing objects
	 * were deliberately not migrated. Making either required would refuse every
	 * dossier already stored, on a schema with `hardValidation: true`.
	 *
	 * @return void
	 */
	public function testStatusAndDocumentsAreOptionalAndAdditive(): void {
		$dossier = $this->config['components']['schemas']['dossier'];

		$this->assertArrayHasKey('status', $dossier['properties']);
		$this->assertArrayHasKey('documents', $dossier['properties']);
		$this->assertNotContains('status', $dossier['required'] ?? []);
		$this->assertNotContains('documents', $dossier['required'] ?? []);

		$this->assertSame('array', $dossier['properties']['documents']['type']);
		$this->assertSame('string', $dossier['properties']['documents']['items']['type']);
		$this->assertArrayNotHasKey(
			'minItems',
			$dossier['properties']['documents'],
			'A dossier with no explicit members is valid — membership can come from the bound folder alone.'
		);
	}

	/**
	 * The lifecycle declares exactly the six transitions the UI offers.
	 *
	 * OpenRegister's guard is the authority; DossierManagementService mirrors
	 * this map so it can offer only the legal targets. The two drifting apart
	 * is how a UI comes to offer a transition the server then refuses, so the
	 * declaration is pinned here.
	 *
	 * @return void
	 */
	public function testDossierLifecycleDeclaresTheCanonicalTransitions(): void {
		$dossier = $this->config['components']['schemas']['dossier'];
		$this->assertArrayHasKey('x-openregister-lifecycle', $dossier);

		$lifecycle = $dossier['x-openregister-lifecycle'];
		$this->assertSame('status', $lifecycle['field']);
		$this->assertSame('open', $lifecycle['initialState'], 'A new dossier starts open.');

		$this->assertSame(
			['open', 'in-review', 'processed', 'published', 'closed'],
			array_keys($lifecycle['states'])
		);

		$edges = [];
		foreach ($lifecycle['transitions'] as $transition) {
			$edges[] = $transition['from'] . '->' . $transition['to'];
		}

		sort($edges);
		$this->assertSame(
			[
				'in-review->open',
				'in-review->processed',
				'open->in-review',
				'processed->closed',
				'processed->published',
				'published->closed',
			],
			$edges
		);
	}

	/**
	 * Every declared status is one the enum admits, and vice versa.
	 *
	 * A state the enum rejects can never be written; an enum value with no
	 * state is a status the lifecycle cannot reason about. Either mismatch is
	 * silent until someone tries the transition.
	 *
	 * @return void
	 */
	public function testStatusEnumAndLifecycleStatesAgree(): void {
		$dossier = $this->config['components']['schemas']['dossier'];

		$enum = $dossier['properties']['status']['enum'];
		$states = array_keys($dossier['x-openregister-lifecycle']['states']);

		sort($enum);
		sort($states);
		$this->assertSame($states, $enum);
	}

	/**
	 * The register version was bumped, or nothing reaches an existing install.
	 *
	 * SettingsInitializer gates the import on `info.version` against the stored
	 * configuration_version, strictly greater. A schema change shipped without a
	 * bump is inert on every install that already has the register — no error
	 * anywhere, the new properties simply never arrive.
	 *
	 * @return void
	 */
	public function testRegisterVersionCoversTheLifecycleAddition(): void {
		$version = $this->config['info']['version'];

		$this->assertTrue(
			version_compare($version, '8.2.0', '>='),
			'The dossier lifecycle + membership properties shipped in register v8.2.0; '
			. 'a lower version means they never reach an existing install. Found: ' . $version
		);
	}

	public function testBaseSchemaHasRequiredNameAndDescription(): void {
		$schemas = $this->config['components']['schemas'] ?? [];
		$this->assertArrayHasKey('base', $schemas);

		$base = $schemas['base'];
		$this->assertContains('name', $base['required'] ?? []);
		$this->assertContains('description', $base['required'] ?? []);

		$this->assertArrayHasKey('name', $base['properties']);
		$this->assertArrayHasKey('description', $base['properties']);
	}

	public function testSeedObjectsIncludeAllCanonicalWooArt5Grondslagen(): void {
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
			static fn (array $object): string => (string)($object['@self']['slug'] ?? ''),
			$baseSeeds
		);

		foreach ($expectedSlugs as $slug) {
			$this->assertContains($slug, $foundSlugs, "Seed base '$slug' MUST be present");
		}

		$this->assertCount(
			count($expectedSlugs),
			$baseSeeds,
			'The canonical Woo Art. 5 A–S legend must seed exactly ' . count($expectedSlugs) . ' base objects'
		);
	}

	public function testFiveOrMoreSeedDossiersExist(): void {
		$dossierSeeds = $this->seedObjectsForSchema('dossier');
		$this->assertGreaterThanOrEqual(5, count($dossierSeeds));
	}

	public function testAtLeastOneSeedDossierExercisesOptionalityCases(): void {
		$dossierSeeds = $this->seedObjectsForSchema('dossier');

		$matches = array_filter(
			$dossierSeeds,
			static fn (array $object): bool
				=> ($object['bases'] ?? null) === []
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
	private function seedObjectsForSchema(string $schemaSlug): array {
		$objects = $this->config['components']['objects'] ?? [];

		return array_values(array_filter(
			$objects,
			static fn (array $object): bool
				=> ($object['@self']['schema'] ?? null) === $schemaSlug
		));
	}
}
