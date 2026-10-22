<?php

/**
 * Unit tests for the leaf declarations in the register descriptor.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * What the register says about the leaves, checked here or nowhere.
 *
 * The import runs at boot against a live OpenRegister and answers HTTP 200 even
 * when it wrote nothing: a schema whose `configuration` OpenRegister refuses is
 * logged and skipped, and the rest of the register lands. So an unknown
 * `mailObjectTemplate` key or a linked type outside the accepted vocabulary is
 * not a loud failure anywhere — it is a leaf that never appears.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class LeafIntegrationsConfigurationTest extends TestCase {

	/**
	 * Every leaf id OpenRegister accepts in `configuration.linkedTypes`.
	 *
	 * Taken from `Schema::legacyLinkedTypeIds()`, the allow-list the entity
	 * falls back on when the integration registry is not reachable — which is
	 * every hydration outside a booted request.
	 *
	 * @var array<int, string>
	 */
	private const ACCEPTED_LINKED_TYPES = [
		'files',
		'mail',
		'contacts',
		'notes',
		'todos',
		'calendar',
		'talk',
		'deck',
	];

	/**
	 * The leaves each schema is declared to host.
	 *
	 * @var array<string, array<int, string>>
	 */
	private const EXPECTED = [
		'signingRequest' => ['mail', 'calendar'],
		'signerRecord' => ['contacts'],
		'publicationConsent' => ['mail', 'calendar', 'deck'],
		'correspondence' => ['mail'],
		'generatedDocument' => ['files'],
		'dossier' => ['files', 'deck'],
	];

	/**
	 * The parsed register descriptor.
	 *
	 * @return array<string, mixed> The descriptor.
	 */
	private function descriptor(): array {
		$raw = file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json');
		$this->assertIsString($raw);

		$parsed = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
		$this->assertIsArray($parsed);

		return $parsed;

	}//end descriptor()

	/**
	 * Every schema in the descriptor.
	 *
	 * @return array<string, array<string, mixed>> The schemas.
	 */
	private function schemas(): array {
		return $this->descriptor()['components']['schemas'];

	}//end schemas()

	/**
	 * Exactly the six named schemas host leaves, with the declared ids.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
	 */
	public function testTheSixSchemasDeclareTheirLeaves(): void {
		$schemas = $this->schemas();

		foreach (self::EXPECTED as $name => $types) {
			$this->assertArrayHasKey($name, $schemas);
			$configuration = $schemas[$name]['configuration'];
			$this->assertArrayHasKey('linkedTypes', $configuration, $name . ' declares no leaves');
			$this->assertSame($types, $configuration['linkedTypes'], $name);
		}

	}//end testTheSixSchemasDeclareTheirLeaves()

	/**
	 * No other schema was swept along.
	 *
	 * @return void
	 */
	public function testNoOtherSchemaDeclaresALeaf(): void {
		$declaring = [];
		foreach ($this->schemas() as $name => $schema) {
			if (isset($schema['configuration']['linkedTypes']) === true) {
				$declaring[] = $name;
			}
		}

		sort($declaring);
		$expected = array_keys(self::EXPECTED);
		sort($expected);

		$this->assertSame($expected, $declaring);

	}//end testNoOtherSchemaDeclaresALeaf()

	/**
	 * Every declared leaf id is one OpenRegister will accept.
	 *
	 * An id outside the vocabulary throws inside `setConfiguration()`, which
	 * the import catches per schema: the schema is skipped and the register
	 * still reports success.
	 *
	 * @return void
	 */
	public function testEveryLinkedTypeIsAcceptedByOpenRegister(): void {
		foreach ($this->schemas() as $name => $schema) {
			foreach (($schema['configuration']['linkedTypes'] ?? []) as $type) {
				$this->assertContains($type, self::ACCEPTED_LINKED_TYPES, $name . ' declares ' . $type);
			}
		}

	}//end testEveryLinkedTypeIsAcceptedByOpenRegister()

	/**
	 * Every create-from-email field names a real property of its own schema.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/leaf-integrations/specs/publication-consent/spec.md
	 */
	public function testEveryMailTemplateKeyIsAPropertyOfItsSchema(): void {
		$withTemplate = [];
		foreach ($this->schemas() as $name => $schema) {
			$template = ($schema['configuration']['mailObjectTemplate'] ?? null);
			if ($template === null) {
				continue;
			}

			$withTemplate[] = $name;
			$properties = array_keys($schema['properties']);
			foreach ($template as $field => $value) {
				$this->assertContains($field, $properties, $name . '.' . $field . ' is not a property');
				$this->assertIsScalar($value, $name . '.' . $field . ' must be scalar');
			}
		}

		sort($withTemplate);
		$this->assertSame(['correspondence', 'publicationConsent'], $withTemplate);

	}//end testEveryMailTemplateKeyIsAPropertyOfItsSchema()

	/**
	 * Create-from-email seeds a record in its initial state, and nothing more.
	 *
	 * A template that prefilled a decision, a status or a deadline would let a
	 * message from the Mail sidebar short-circuit a consent or a generation.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/leaf-integrations/specs/publication-consent/spec.md
	 */
	public function testNoMailTemplateAdvancesARecord(): void {
		$forbidden = [
			'consentStatus',
			'publicationDecision',
			'objectionDeadline',
			'notificationStatus',
			'status',
			'generatedAt',
		];

		foreach ($this->schemas() as $name => $schema) {
			foreach (array_keys($schema['configuration']['mailObjectTemplate'] ?? []) as $field) {
				$this->assertNotContains($field, $forbidden, $name . '.' . $field . ' advances the record');
			}
		}

	}//end testNoMailTemplateAdvancesARecord()

	/**
	 * The agent-facing surface is untouched by this change.
	 *
	 * `signerRecord` and `publicationConsent` are excluded from the MCP surface
	 * by `filinq-mcp-adoption`, and a leaf is an authenticated UI surface, not
	 * an agent one. This is the assertion rather than a grep so the two surfaces
	 * cannot drift apart in silence.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/leaf-integrations/tasks.md#task-3-3
	 */
	public function testTheLeavesDoNotWidenTheAgentSurface(): void {
		$schemas = $this->schemas();

		foreach (['signerRecord', 'publicationConsent'] as $excluded) {
			$this->assertArrayNotHasKey(
				'x-openregister-mcp',
				$schemas[$excluded]['configuration'],
				$excluded . ' must stay off the MCP surface'
			);
		}

	}//end testTheLeavesDoNotWidenTheAgentSurface()

	/**
	 * The change reaches an existing install, or it is inert everywhere.
	 *
	 * `SettingsInitializer` gates the import on `info.version` against the
	 * stored `configuration_version`, and the importer skips a schema whose own
	 * version did not move. Without both bumps this whole change is a no-op on
	 * every instance that already has Filinq, with nothing logged.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/leaf-integrations/tasks.md#task-1-3
	 */
	public function testTheVersionsMovedSoTheImportRuns(): void {
		$descriptor = $this->descriptor();

		$this->assertTrue(
			version_compare($descriptor['info']['version'], '8.15.0', '>'),
			'the register version must move past the version that shipped before this change'
		);

		$minimums = [
			'signingRequest' => '1.3.0',
			'signerRecord' => '1.1.0',
			'publicationConsent' => '1.1.0',
			'correspondence' => '1.1.0',
			'generatedDocument' => '1.2.0',
			'dossier' => '1.2.0',
		];

		foreach ($minimums as $name => $before) {
			$this->assertTrue(
				version_compare($descriptor['components']['schemas'][$name]['version'], $before, '>'),
				$name . ' kept its old version, so its configuration is dropped on re-import'
			);
		}

	}//end testTheVersionsMovedSoTheImportRuns()
}//end class
