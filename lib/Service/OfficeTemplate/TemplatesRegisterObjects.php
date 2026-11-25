<?php

/**
 * Filinq Templates Register Objects
 *
 * Reads and writes objects of one schema in the register the templates live
 * in, through OpenRegister's object service.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use Exception;
use OCA\Filinq\Kernel\Contracts\OpenRegisterResolverInterface;
use OCA\Filinq\Service\Support\OpenRegisterAccess;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectDeletion;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use RuntimeException;
use Throwable;

/**
 * Objects of one schema in the templates register.
 *
 * The register is the one the templates are configured in; the schema is
 * named by its slug, so fragments and import jobs need no settings of their
 * own. Every read and write goes through OpenRegister with its access checks.
 *
 * @category Service
 * @package  OCA\Filinq\Service\OfficeTemplate
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
 */
class TemplatesRegisterObjects {

	/**
	 * OpenRegister's object service.
	 */
	private const OBJECT_SERVICE = 'OCA\OpenRegister\Service\ObjectService';

	/**
	 * Constructor.
	 *
	 * @param OpenRegisterAccess            $openRegister The installed apps and the container.
	 * @param OpenRegisterResolverInterface $resolver     The templates register.
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function __construct(
		private readonly OpenRegisterAccess $openRegister,
		private readonly OpenRegisterResolverInterface $resolver,
	) {
	}//end __construct()

	/**
	 * A page of objects.
	 *
	 * @param string               $schema The schema slug.
	 * @param array<string, mixed> $query  Field filters, with `_limit` (default 20) and `_offset` (default 0).
	 *
	 * @return array{results: array<int, array<string, mixed>>, total: int}
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function search(string $schema, array $query=[]): array {
		$service = $this->objectService();
		$register = $this->register();
		$query = $service->buildSearchQuery(
			requestParams: $query + ['_limit' => 20, '_offset' => 0],
			register: $register,
			schema: $schema
		);
		$page = $service->searchObjectsPaginated(query: $query);

		$results = [];
		foreach ((array) ($page['results'] ?? []) as $row) {
			$results[] = $this->toArray(value: $row);
		}

		return ['results' => $results, 'total' => (int) ($page['total'] ?? count($results))];
	}//end search()

	/**
	 * One object.
	 *
	 * @param string $schema The schema slug.
	 * @param string $id     The object id.
	 *
	 * @return array<string, mixed>
	 *
	 * @throws Exception With code 404 when there is no such object.
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function get(string $schema, string $id): array {
		try {
			$row = $this->objectService()->find(id: $id, options: new ObjectFindOptions(register: $this->register(), schema: $schema));
		} catch (RuntimeException $e) {
			throw $e;
		} catch (Throwable) {
			$row = null;
		}

		$object = $this->toArray(value: $row);
		if ($object === []) {
			throw new Exception(message: 'Not found', code: 404);
		}

		return $object;
	}//end get()

	/**
	 * Create or update an object.
	 *
	 * @param string               $schema The schema slug.
	 * @param array<string, mixed> $data   The object.
	 * @param string|null          $id     The id to update, or null to create.
	 *
	 * @return array<string, mixed> The saved object.
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function save(string $schema, array $data, ?string $id=null): array {
		return $this->toArray(
			value: $this->objectService()->saveObject(
				object: $data,
				options: new ObjectSaveOptions(target: new ObjectFindTarget(register: $this->register(), schema: $schema), uuid: $id)
			)
		);
	}//end save()

	/**
	 * Delete an object.
	 *
	 * @param string $schema The schema slug.
	 * @param string $id     The object id.
	 *
	 * @return void
	 *
	 * @throws Exception With code 404 when there is no such object.
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function delete(string $schema, string $id): void {
		$this->get(schema: $schema, id: $id);
		$this->objectService()->deleteObject(
			new ObjectDeletion(uuid: $id, target: new ObjectFindTarget(register: $this->register(), schema: $schema))
		);
	}//end delete()

	/**
	 * The register the templates live in.
	 *
	 * @return string
	 */
	private function register(): string {
		return (string) $this->resolver->getRegisterAndSchema()['register'];
	}//end register()

	/**
	 * OpenRegister's object service.
	 *
	 * @return mixed
	 *
	 * @throws RuntimeException When OpenRegister is not installed.
	 */
	private function objectService(): mixed {
		if (in_array('openregister', $this->openRegister->apps->getInstalledApps(), true) === false) {
			throw new RuntimeException(message: 'OpenRegister service is not available.');
		}

		return $this->openRegister->container->get(self::OBJECT_SERVICE);
	}//end objectService()

	/**
	 * An object entity or row as an array.
	 *
	 * @param mixed $value The entity, row or null.
	 *
	 * @return array<string, mixed>
	 */
	private function toArray(mixed $value): array {
		if (is_object($value) === true && method_exists($value, 'jsonSerialize') === true) {
			$value = $value->jsonSerialize();
		}

		if (is_array($value) === false) {
			return [];
		}

		return $value;
	}//end toArray()
}//end class
