<?php

/**
 * Doubles for the wizard tests
 *
 * WizardObjectStore keeps wizardDefinition objects in memory and refuses any
 * payload the real wizardDefinition fragment of filinq_register.json refuses
 * (Opis), so a save the register would reject fails the test. The seed
 * wizard is design.md's "Beschikking parkeervergunning" interview.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Wizard
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Wizard;

use OCA\OpenRegister\Service\ObjectService;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use RuntimeException;

/**
 * In-memory wizardDefinition store with the register's validation.
 */
class WizardObjectStore extends ObjectService {

	/**
	 * Stored objects by uuid.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public array $rows = [];

	/**
	 * Saves made, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public array $saves = [];

	/**
	 * Save count per uuid, the stand-in for OpenRegister's object version.
	 *
	 * @var array<string, int>
	 */
	public array $versions = [];

	/**
	 * The uuids deleted.
	 *
	 * @var string[]
	 */
	public array $deleted = [];

	/**
	 * Find one.
	 *
	 * @param string $id            The uuid.
	 * @param string $register      The register.
	 * @param string $schema        The schema.
	 * @param bool   $_rbac         RBAC.
	 * @param bool   $_multitenancy Multitenancy.
	 * @param bool   $_render       Render.
	 * @param bool   $_audit        Audit.
	 *
	 * @return array<string, mixed>|null The object.
	 */
	public function find(
		string $id = '',
		string $register = '',
		string $schema = '',
		bool $_rbac = true,
		bool $_multitenancy = true,
		bool $_render = true,
		bool $_audit = true,
	) {
		if (isset($this->rows[$id]) === false) {
			return null;
		}

		return $this->rows[$id] + ['@self' => ['id' => $id, 'version' => '1.0.' . ($this->versions[$id] ?? 0)]];

	}//end find()

	/**
	 * Save one.
	 *
	 * @param array<string, mixed> $object        The object.
	 * @param string               $register      The register.
	 * @param string               $schema        The schema.
	 * @param string|null          $uuid          The uuid.
	 * @param bool                 $_rbac         RBAC.
	 * @param bool                 $_multitenancy Multitenancy.
	 *
	 * @return array<string, mixed> The stored object.
	 */
	public function saveObject(
		array $object = [],
		string $register = '',
		string $schema = '',
		?string $uuid = null,
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		if ($register !== 'filinq' || $schema !== 'wizardDefinition') {
			throw new RuntimeException('unexpected target ' . $register . '/' . $schema);
		}

		self::assertValid(schema: 'wizardDefinition', payload: $object);
		$this->saves[] = $object;
		$uuid ??= 'wizard-' . (count($this->rows) + 1);
		$this->rows[$uuid] = $object;
		$this->versions[$uuid] = ($this->versions[$uuid] ?? 0) + 1;

		return $this->find(id: $uuid);

	}//end saveObject()

	/**
	 * Search by equality filters.
	 *
	 * @param string               $registerSlug  The register.
	 * @param string               $schemaSlug    The schema.
	 * @param array<string, mixed> $filters       The filters.
	 * @param bool                 $_rbac         RBAC.
	 * @param bool                 $_multitenancy Multitenancy.
	 *
	 * @return array<int, array<string, mixed>> The hits.
	 */
	public function searchObjectsBySlug(
		string $registerSlug,
		string $schemaSlug,
		array $filters = [],
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		$hits = [];
		foreach (array_keys($this->rows) as $id) {
			foreach ($filters as $key => $value) {
				if (($this->rows[$id][$key] ?? null) !== $value) {
					continue 2;
				}
			}

			$hits[] = $this->find(id: (string) $id);
		}

		return $hits;

	}//end searchObjectsBySlug()

	/**
	 * Delete one.
	 *
	 * @param string $uuid          The uuid.
	 * @param string $register      The register.
	 * @param string $schema        The schema.
	 * @param bool   $_rbac         RBAC.
	 * @param bool   $_multitenancy Multitenancy.
	 *
	 * @return bool True.
	 */
	public function deleteObject(
		string $uuid = '',
		string $register = '',
		string $schema = '',
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		unset($this->rows[$uuid]);
		$this->deleted[] = $uuid;

		return true;

	}//end deleteObject()

	/**
	 * Assert a payload passes a schema fragment of filinq_register.json.
	 *
	 * Property-level `required: false` flags and the presentation keys OpenRegister
	 * adds are not JSON Schema; they are dropped, everything else is checked.
	 *
	 * @param string               $schema  The schema slug.
	 * @param array<string, mixed> $payload The object as written.
	 *
	 * @return void
	 */
	public static function assertValid(string $schema, array $payload): void {
		$register = json_decode((string) file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_register.json'));
		$fragment = self::clean(node: $register->components->schemas->{$schema});
		$jsonSchema = (object) [
			'type' => 'object',
			'required' => $fragment->required ?? [],
			'properties' => $fragment->properties,
			'additionalProperties' => false,
		];
		unset($payload['uuid']);
		if ($schema !== 'templateVersion') {
			// version is OpenRegister's object version everywhere else; on templateVersion it is a real field.
			unset($payload['version']);
		}
		$result = (new Validator())->validate(json_decode((string) json_encode($payload)), json_encode($jsonSchema));
		if ($result->isValid() === false) {
			throw new RuntimeException($schema . ' refused: ' . json_encode((new ErrorFormatter())->format($result->error())));
		}

	}//end assertValid()

	/**
	 * Drop the non-JSON-Schema keys from a fragment, recursively.
	 *
	 * @param mixed $node The fragment.
	 *
	 * @return mixed The cleaned fragment.
	 */
	private static function clean(mixed $node): mixed {
		if (is_array($node) === true) {
			return array_map(static fn (mixed $item): mixed => self::clean(node: $item), $node);
		}

		if (is_object($node) === false) {
			return $node;
		}

		$copy = new \stdClass();
		foreach (get_object_vars($node) as $key => $value) {
			if (in_array($key, ['facetable', 'visible', 'order', 'x-i18n', 'objectConfiguration', 'cascadeDelete', 'inversedBy', 'writeBack', 'removeAfterWriteBack'], true) === true) {
				continue;
			}

			if ($key === 'required' && is_bool($value) === true) {
				continue;
			}

			$copy->{$key} = ($key === 'properties' && is_object($value) === true)
				? (object) array_map(static fn (mixed $p): mixed => self::clean(node: $p), get_object_vars($value))
				: self::clean(node: $value);
		}

		return $copy;

	}//end clean()
}//end class

/**
 * The seed wizard and its parts.
 */
final class WizardFixtures {

	/**
	 * The seed template's id.
	 *
	 * @var string
	 */
	public const TEMPLATE = '00000000-0000-0000-0000-000000000101';

	/**
	 * design.md's seed wizard.
	 *
	 * @return array<string, mixed> The definition.
	 */
	public static function seed(): array {
		return [
			'name' => 'Beschikking parkeervergunning, begeleid',
			'namespace' => 'filinq',
			'templateId' => self::TEMPLATE,
			'active' => true,
			'questions' => [
				['key' => 'dossier', 'label' => 'Which dossier is this decision for?', 'type' => 'registerObject', 'required' => true, 'register' => 'filinq', 'schema' => 'dossier'],
				['key' => 'besluit', 'label' => 'What is the decision?', 'type' => 'choice', 'required' => true, 'choices' => [['value' => 'toegewezen', 'label' => 'Granted'], ['value' => 'afgewezen', 'label' => 'Rejected']], 'mapsTo' => 'besluit.uitkomst'],
				['key' => 'afwijzingsreden', 'label' => 'Reason for rejection', 'type' => 'text', 'required' => true, 'mapsTo' => 'besluit.afwijzingsreden', 'condition' => ['questionKey' => 'besluit', 'operator' => 'equals', 'value' => 'afgewezen']],
				['key' => 'ingangsdatum', 'label' => 'Effective date', 'type' => 'date', 'required' => true, 'mapsTo' => 'besluit.ingangsdatum', 'condition' => ['questionKey' => 'besluit', 'operator' => 'equals', 'value' => 'toegewezen']],
			],
		];

	}//end seed()
}//end class
