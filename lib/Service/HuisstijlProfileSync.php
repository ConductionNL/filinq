<?php

/**
 * Huisstijl Profile Sync
 *
 * Keeps one `huisstijl` per thematiq token set, filled from thematiq's
 * document house style profile, so an organisation sets its logo, colours,
 * fonts and footer once in Theming and every generated document follows.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/templates-house-style-from-thematiq/tasks.md#task-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\App\IAppManager;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Upserts the managed house style of a user's token set from the profile.
 *
 * Without thematiq (not enabled, or its profile service absent) every call
 * returns null and nothing is written, so filinq behaves as before.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class HuisstijlProfileSync {

	/**
	 * The `managedBy` value of a house style that follows thematiq.
	 *
	 * @var string
	 */
	public const MANAGED_BY = 'thematiq';

	/**
	 * The app that publishes the profile.
	 *
	 * @var string
	 */
	public const PROFILE_APP = 'thematiq';

	/**
	 * The service that publishes the profile, named by string: thematiq is an
	 * optional peer and its class is absent wherever it is not installed.
	 *
	 * @var string
	 */
	public const PROFILE_SERVICE = 'OCA\\Thematiq\\Service\\DocumentStyleService';

	/**
	 * The register and schema of the house style.
	 *
	 * @var string
	 */
	public const REGISTER = 'filinq';

	/**
	 * The schema slug of the house style.
	 *
	 * @var string
	 */
	public const SCHEMA = 'huisstijl';

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container The container that holds thematiq's profile service.
	 * @param IAppManager $appManager Whether thematiq is enabled.
	 * @param DocumentObjectServiceResolver $objectResolver OpenRegister's ObjectService.
	 * @param HuisstijlProfileMapper $mapper Maps the profile onto the house style fields.
	 * @param HuisstijlWriteGate $gate The gate the managed write goes through.
	 * @param IUserSession $userSession The signed-in user, when no user is named.
	 * @param LoggerInterface $logger Logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly IAppManager $appManager,
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly HuisstijlProfileMapper $mapper,
		private readonly HuisstijlWriteGate $gate,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The managed house style of a user's token set, refreshed from the profile when it changed.
	 *
	 * @param string|null $uid The user; null for the signed-in user (or the instance default without one).
	 *
	 * @return array<string, mixed>|null The managed house style, or null without thematiq or on a failure.
	 *
	 * @spec openspec/changes/templates-house-style-from-thematiq/specs/letter-correspondence-generation/spec.md#requirement-filinq-keeps-a-house-style-per-thematiq-token-set-req-hsp-001
	 */
	public function syncFor(?string $uid): ?array {
		$profileService = $this->profileService();
		if ($profileService === null) {
			return null;
		}

		try {
			$profile = $profileService->forUser($uid ?? $this->currentUid());
			if (is_array($profile) === false) {
				return null;
			}

			$mapped = $this->mapper->mapProfile(profile: $profile);
			if ($mapped['tokenSetId'] === '') {
				return null;
			}

			$existing = $this->findManaged(tokenSetId: $mapped['tokenSetId']);
			if ($existing !== null && ($existing['profileHash'] ?? null) === $mapped['profileHash']) {
				return $existing;
			}

			return $this->store(huisstijl: $mapped, uuid: $this->uuidOf(row: ($existing ?? [])));
		} catch (Throwable $e) {
			$this->logger->warning(
				message: 'Failed to sync the house style from thematiq: ' . $e->getMessage(),
				context: ['uid' => $uid]
			);
			return null;
		}//end try

	}//end syncFor()

	/**
	 * Thematiq's profile service, or null when thematiq is not there.
	 *
	 * @return object|null The service.
	 */
	private function profileService(): ?object {
		try {
			if ($this->appManager->isEnabledForUser(self::PROFILE_APP) === false) {
				return null;
			}

			$service = $this->container->get(self::PROFILE_SERVICE);
		} catch (Throwable) {
			return null;
		}

		if (is_object($service) === false || method_exists($service, 'forUser') === false) {
			return null;
		}

		return $service;

	}//end profileService()

	/**
	 * The signed-in user's id, or null.
	 *
	 * @return string|null The uid.
	 */
	private function currentUid(): ?string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return null;
		}

		return $user->getUID();

	}//end currentUid()

	/**
	 * The stored managed house style of a token set, or null.
	 *
	 * @param string $tokenSetId The token set.
	 *
	 * @return array<string, mixed>|null The house style.
	 */
	private function findManaged(string $tokenSetId): ?array {
		$rows = $this->objectResolver->resolve()->searchObjectsBySlug(
			registerSlug: self::REGISTER,
			schemaSlug: self::SCHEMA,
			filters: ['managedBy' => self::MANAGED_BY, 'tokenSetId' => $tokenSetId],
			_rbac: false,
			_multitenancy: false
		);

		if (is_array($rows) === false) {
			return null;
		}

		foreach ($rows as $row) {
			$data = $this->rowData(row: $row);
			if ($this->mapper->isManaged(huisstijl: $data) === true && ($data['tokenSetId'] ?? null) === $tokenSetId) {
				return $data;
			}
		}

		return null;

	}//end findManaged()

	/**
	 * Write the managed house style through the gate.
	 *
	 * The schema lets only template editors write a house style; the sync runs
	 * for whoever generates a letter, so it writes without RBAC.
	 *
	 * @param array<string, mixed> $huisstijl The mapped house style.
	 * @param string|null $uuid The stored row to update, or null to create.
	 *
	 * @return array<string, mixed> The stored house style.
	 */
	private function store(array $huisstijl, ?string $uuid): array {
		$objectService = $this->objectResolver->resolve();
		$saved = $this->gate->during(
			write: static fn (): mixed => $objectService->saveObject(
				object: $huisstijl,
				register: self::REGISTER,
				schema: self::SCHEMA,
				uuid: $uuid,
				_rbac: false,
				_multitenancy: false
			)
		);

		$stored = $this->rowData(row: $saved);
		if ($stored === []) {
			return $huisstijl;
		}

		return $stored;

	}//end store()

	/**
	 * A stored row as an array.
	 *
	 * @param mixed $row An array, or an entity with `jsonSerialize()` or `getObject()`.
	 *
	 * @return array<string, mixed> The data, or [] when it is neither.
	 */
	private function rowData(mixed $row): array {
		if (is_array($row) === true) {
			return $row;
		}

		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			return $this->arrayOf(value: $row->jsonSerialize());
		}

		if (is_object($row) === true && method_exists($row, 'getObject') === true) {
			return $this->arrayOf(value: $row->getObject());
		}

		return [];

	}//end rowData()

	/**
	 * The uuid of a stored row, or null.
	 *
	 * @param array<string, mixed> $row The row.
	 *
	 * @return string|null The uuid.
	 */
	private function uuidOf(array $row): ?string {
		$self = $this->arrayOf(value: ($row['@self'] ?? null));
		foreach ([($self['id'] ?? null), ($row['id'] ?? null), ($row['uuid'] ?? null)] as $candidate) {
			if (is_string($candidate) === true && $candidate !== '') {
				return $candidate;
			}
		}

		return null;

	}//end uuidOf()

	/**
	 * A value as an array, [] when it is not one.
	 *
	 * @param mixed $value The value.
	 *
	 * @return array<mixed> The array.
	 */
	private function arrayOf(mixed $value): array {
		if (is_array($value) === true) {
			return $value;
		}

		return [];

	}//end arrayOf()
}//end class
