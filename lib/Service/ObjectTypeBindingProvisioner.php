<?php

/**
 * Object type binding provisioner
 *
 * Fills the `<slug>_register`, `<slug>_schema` and `<slug>_source` app-config
 * keys for every schema of the app's own OpenRegister register, so a fresh
 * install resolves its object types without an administrator first choosing
 * each register and schema in the settings.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/admin-settings/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\IAppConfig;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Writes the object type bindings an administrator has not set.
 *
 * Only empty keys are written: a binding an administrator chose, or one an
 * earlier run wrote, is never changed.
 */
class ObjectTypeBindingProvisioner {

	/**
	 * The slug of the register the app imports its schemas into.
	 */
	public const REGISTER_SLUG = 'filinq';

	/**
	 * OpenRegister's register service.
	 */
	private const REGISTER_SERVICE = 'OCA\OpenRegister\Service\RegisterService';

	/**
	 * OpenRegister's schema mapper.
	 */
	private const SCHEMA_MAPPER = 'OCA\OpenRegister\Db\SchemaMapper';

	/**
	 * Constructor.
	 *
	 * @param IAppConfig         $config    The app configuration.
	 * @param ContainerInterface $container Resolves OpenRegister's services.
	 * @param LoggerInterface    $logger    The logger.
	 * @param string             $appName   The app id the keys belong to.
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function __construct(
		private readonly IAppConfig $config,
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
		private readonly string $appName='filinq',
	) {

	}//end __construct()

	/**
	 * Write the missing bindings for every schema of the app's register.
	 *
	 * @return int|null The number of object types that got a binding (0 when all
	 *                  were set already), or null when the register is not there
	 *                  (yet) or OpenRegister cannot be read, so the caller tries again.
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function provision(): ?int {
		try {
			$register = $this->findRegister();
			if ($register === null) {
				return null;
			}

			$written = 0;
			foreach ($this->schemasOf(register: $register) as $slug => $schemaId) {
				if ($this->bind(slug: $slug, registerId: (string) $register['id'], schemaId: $schemaId) === true) {
					$written++;
				}
			}
		} catch (Throwable $e) {
			$this->logger->warning(
				'[ObjectTypeBindingProvisioner] Object type bindings not provisioned: ' . $e->getMessage(),
				['app' => $this->appName]
			);

			return null;
		}//end try

		if ($written > 0) {
			$this->logger->info(
				'[ObjectTypeBindingProvisioner] Provisioned object type bindings',
				['app' => $this->appName, 'count' => $written, 'register' => $register['id']]
			);
		}

		return $written;

	}//end provision()

	/**
	 * The app's register, by slug.
	 *
	 * @return array<string, mixed>|null The serialised register, or null when absent.
	 */
	private function findRegister(): ?array {
		$registers = $this->container->get(self::REGISTER_SERVICE)->findAll(
			limit: null,
			offset: null,
			filters: [],
			searchConditions: [],
			searchParams: []
		);

		foreach ((array) $registers as $register) {
			$data = self::serialise(entity: $register);
			if (($data['slug'] ?? '') === self::REGISTER_SLUG && (string) ($data['id'] ?? '') !== '') {
				return $data;
			}
		}

		return null;

	}//end findRegister()

	/**
	 * The schemas of a register, slug to id. A schema that cannot be read is skipped.
	 *
	 * @param array<string, mixed> $register The serialised register.
	 *
	 * @return array<string, string>
	 */
	private function schemasOf(array $register): array {
		$mapper  = $this->container->get(self::SCHEMA_MAPPER);
		$schemas = [];
		foreach ((array) ($register['schemas'] ?? []) as $reference) {
			$id = $reference;
			if (is_array($reference) === true) {
				$id = ($reference['id'] ?? '');
			}

			try {
				$data = self::serialise(entity: $mapper->find(id: $id));
			} catch (Throwable) {
				continue;
			}

			$slug = (string) ($data['slug'] ?? '');
			if ($slug !== '' && (string) ($data['id'] ?? '') !== '') {
				$schemas[$slug] = (string) $data['id'];
			}
		}

		return $schemas;

	}//end schemasOf()

	/**
	 * Write one object type's binding when its register key is empty.
	 *
	 * @param string $slug       The schema slug, the object type's key prefix.
	 * @param string $registerId The register id.
	 * @param string $schemaId   The schema id.
	 *
	 * @return bool True when the binding was written.
	 */
	private function bind(string $slug, string $registerId, string $schemaId): bool {
		if ($this->config->getValueString($this->appName, $slug . '_register', '') !== '') {
			return false;
		}

		$this->config->setValueString($this->appName, $slug . '_register', $registerId);
		if ($this->config->getValueString($this->appName, $slug . '_schema', '') === '') {
			$this->config->setValueString($this->appName, $slug . '_schema', $schemaId);
		}

		if ($this->config->getValueString($this->appName, $slug . '_source', '') === '') {
			$this->config->setValueString($this->appName, $slug . '_source', 'openregister');
		}

		return true;

	}//end bind()

	/**
	 * An OpenRegister entity, or an array, as an array.
	 *
	 * @param mixed $entity The entity.
	 *
	 * @return array<string, mixed>
	 */
	private static function serialise(mixed $entity): array {
		if (is_array($entity) === true) {
			return $entity;
		}

		if (is_object($entity) === true && method_exists($entity, 'jsonSerialize') === true) {
			return (array) $entity->jsonSerialize();
		}

		return [];

	}//end serialise()
}//end class
