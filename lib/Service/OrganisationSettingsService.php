<?php

/**
 * Organisation Settings Service
 *
 * Reads and writes the active organisation's `organisationSettings` object:
 * the per-organisation overrides of the objection period, the anonymisation
 * profile and the default huisstijl. The single write path, guarded by the
 * organisation-admin check.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/admin-settings/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use InvalidArgumentException;
use OCA\Filinq\Exception\OrganisationSettingsDeniedException;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCP\IGroupManager;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * The organisation settings of the caller's active organisation.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) The class joins the organisation lookup, the
 * admin checks and the settings store of one organisation's settings; its value objects and
 * exceptions count toward the coupling too.
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/admin-settings/spec.md#requirement-per-organisation-settings-are-stored-as-organisationsettings-objects-req-ddmth-007
 */
class OrganisationSettingsService {
	use ReadsStoredObjectRows;

	/**
	 * The register holding the schema.
	 *
	 * @var string
	 */
	public const REGISTER = 'filinq';

	/**
	 * OpenRegister's organisation service, resolved lazily.
	 *
	 * @var string
	 */
	private const ORGANISATIONS = 'OCA\OpenRegister\Service\OrganisationService';

	/**
	 * Longest name and notes accepted.
	 *
	 * @var array<string, int>
	 */
	private const MAX_LENGTH = ['name' => 255, 'notes' => 2000];

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's object service.
	 * @param ContainerInterface            $container      Resolves OpenRegister's organisation service.
	 * @param IUserSession                  $userSession    The caller.
	 * @param IGroupManager                 $groupManager   Instance-admin check.
	 * @param EffectiveSettings             $effective      The instance defaults the overrides fall back to.
	 * @param LoggerInterface               $logger         Logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly ContainerInterface $container,
		private readonly IUserSession $userSession,
		private readonly IGroupManager $groupManager,
		private readonly EffectiveSettings $effective,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * The active organisation, whether the caller may edit its settings, and the stored overrides.
	 *
	 * An organisation that cannot be resolved yields no organisation, no
	 * settings and no edit right: an empty state, never another
	 * organisation's object.
	 *
	 * @return array<string, mixed> `organisation`, `canEdit`, `settings`, `instanceDefaults`.
	 *
	 * @spec openspec/changes/multi-tenant-hardening/specs/admin-settings/spec.md#requirement-per-organisation-settings-are-stored-as-organisationsettings-objects-req-ddmth-007
	 */
	public function view(): array {
		$organisation = $this->activeOrganisation();
		$instance     = ['consentPeriodDays' => $this->effective->instanceConsentPeriodDays()];
		if ($organisation === null) {
			return ['organisation' => null, 'canEdit' => false, 'settings' => null, 'instanceDefaults' => $instance];
		}

		$stored = $this->storedObject(organisationUuid: $organisation['uuid']);

		return [
			'organisation'     => $organisation,
			'canEdit'          => $this->mayEdit(organisationUuid: $organisation['uuid']),
			'settings'         => $this->present(stored: $stored),
			'instanceDefaults' => $instance,
		];
	}//end view()

	/**
	 * Save the active organisation's overrides.
	 *
	 * Only an admin of the active organisation or an instance admin may
	 * write; anybody else is refused before anything is read or stored.
	 * The object is saved without an organisation value: OpenRegister
	 * stamps the active organisation on a new object, and an existing one
	 * is updated only when its stamp is the active organisation.
	 *
	 * @param array<string, mixed> $input The submitted fields; unknown fields are ignored.
	 *
	 * @return array<string, mixed> The view after the save.
	 *
	 * @throws OrganisationSettingsDeniedException When the caller may not write, or no organisation is active.
	 * @throws InvalidArgumentException          When a value is out of range.
	 * @throws RuntimeException                  When the stored state is ambiguous.
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) OrganisationSettingsInput checks the posted settings as a
	 * stateless helper; an instance would add nothing.
	 *
	 * @spec openspec/changes/multi-tenant-hardening/specs/admin-settings/spec.md#requirement-per-organisation-settings-are-stored-as-organisationsettings-objects-req-ddmth-007
	 */
	public function save(array $input): array {
		$organisation = $this->editableOrganisation();

		$fields = OrganisationSettingsInput::fields(input: $input, defaultName: $organisation['name'], maxLength: self::MAX_LENGTH);
		$this->assertHuisstijlVisible(uuid: $fields['defaultHuisstijl']);

		$options = $this->saveOptions(stored: $this->storedObject(organisationUuid: $organisation['uuid']));

		// The schema grants writes to instance admins; an organisation admin
		// is checked above, at this single write path. The organisation
		// filter stays on, so the object is stamped with and kept inside the
		// active organisation.
		$this->objectResolver->resolve()->saveObject(
			object: $fields,
			options: $options,
			_rbac: false
		);

		return $this->view();
	}//end save()

	/**
	 * The active organisation, when the caller may change its settings.
	 *
	 * @return array{uuid: string, name: string} The organisation.
	 *
	 * @throws OrganisationSettingsDeniedException When the caller may not write, or no organisation is active.
	 */
	private function editableOrganisation(): array {
		$organisation = $this->activeOrganisation();
		if ($organisation === null) {
			throw new OrganisationSettingsDeniedException(message: 'No active organisation; organisation settings cannot be saved.');
		}

		if ($this->mayEdit(organisationUuid: $organisation['uuid']) === false) {
			throw new OrganisationSettingsDeniedException(message: 'Only an admin of this organisation may change its settings.');
		}

		return $organisation;
	}//end editableOrganisation()

	/**
	 * Refuses a default huisstijl the caller cannot read in the active organisation.
	 *
	 * @param string|null $uuid The submitted default huisstijl; null sets none.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When the huisstijl does not resolve.
	 */
	private function assertHuisstijlVisible(?string $uuid): void {
		if ($uuid !== null && $this->huisstijlIsVisible(uuid: $uuid) === false) {
			throw new InvalidArgumentException('The default huisstijl is not a huisstijl of this organisation.');
		}
	}//end assertHuisstijlVisible()

	/**
	 * The save options: a create, or an update of the stored object.
	 *
	 * @param array<string, mixed>|null $stored The stored object, or null when there is none.
	 *
	 * @return ObjectSaveOptions The options.
	 */
	private function saveOptions(?array $stored): ObjectSaveOptions {
		if ($stored === null) {
			return new ObjectSaveOptions(target: new ObjectFindTarget(register: self::REGISTER, schema: EffectiveSettings::SCHEMA));
		}

		return new ObjectSaveOptions(
			target: new ObjectFindTarget(register: self::REGISTER, schema: EffectiveSettings::SCHEMA),
			uuid: (string)$stored['uuid']
		);
	}//end saveOptions()

	/**
	 * Whether the caller is an instance admin or an admin of the organisation.
	 *
	 * A failing organisation lookup refuses.
	 *
	 * @param string $organisationUuid The organisation.
	 *
	 * @return bool True when the caller may write.
	 */
	private function mayEdit(string $organisationUuid): bool {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return false;
		}

		if ($this->groupManager->isAdmin($user->getUID()) === true) {
			return true;
		}

		try {
			return $this->container->get(self::ORGANISATIONS)->isOrganisationAdmin($organisationUuid, $user->getUID()) === true;
		} catch (Throwable $e) {
			$this->logger->warning('The organisation admin check failed; refusing', ['exception' => $e->getMessage()]);
			return false;
		}
	}//end mayEdit()

	/**
	 * The caller's active organisation, or null when none resolves.
	 *
	 * @return array{uuid: string, name: string}|null
	 */
	private function activeOrganisation(): ?array {
		if ($this->userSession->getUser() === null) {
			return null;
		}

		try {
			$organisation = $this->container->get(self::ORGANISATIONS)->getActiveOrganisation();
		} catch (Throwable $e) {
			$this->logger->warning('The active organisation could not be resolved', ['exception' => $e->getMessage()]);
			return null;
		}

		return $this->organisationOf(organisation: $organisation);
	}//end activeOrganisation()

	/**
	 * The uuid and name of a resolved organisation, or null without a uuid.
	 *
	 * @param mixed $organisation The resolved organisation.
	 *
	 * @return array{uuid: string, name: string}|null
	 */
	private function organisationOf(mixed $organisation): ?array {
		if (is_object($organisation) === false || method_exists($organisation, 'getUuid') === false) {
			return null;
		}

		$uuid = trim((string)$organisation->getUuid());
		if ($uuid === '') {
			return null;
		}

		return ['uuid' => $uuid, 'name' => $this->organisationName(organisation: $organisation)];
	}//end organisationOf()

	/**
	 * The name of a resolved organisation; empty when it has none.
	 *
	 * @param object $organisation The resolved organisation.
	 *
	 * @return string The name.
	 */
	private function organisationName(object $organisation): string {
		if (method_exists($organisation, 'getName') === false) {
			return '';
		}

		return (string)$organisation->getName();
	}//end organisationName()

	/**
	 * The stored object of the organisation, or null when it has none.
	 *
	 * Read under OpenRegister's enforcement, so only the active
	 * organisation's object is visible. More than one visible object, or
	 * one stamped with another organisation, means the scope did not hold:
	 * that is refused rather than written over.
	 *
	 * @param string $organisationUuid The active organisation.
	 *
	 * @return array<string, mixed>|null The stored fields with `uuid`.
	 *
	 * @throws RuntimeException When the stored state is ambiguous.
	 */
	private function storedObject(string $organisationUuid): ?array {
		$rows = array_values((array)$this->objectResolver->resolve()->searchObjectsBySlug(
			registerSlug: self::REGISTER,
			schemaSlug: EffectiveSettings::SCHEMA,
			filters: []
		));

		if (count($rows) > 1) {
			throw new RuntimeException('More than one organisation settings object is visible; nothing is changed.');
		}

		if ($rows === []) {
			return null;
		}

		$fields = $this->storedFieldsKeepingSelf(row: $rows[0]);
		$this->assertOwnStamp(fields: $fields, organisationUuid: $organisationUuid);
		unset($fields['@self']);

		return $fields;
	}//end storedObject()

	/**
	 * Refuses stored fields stamped with another organisation.
	 *
	 * @param array<string, mixed> $fields           The stored fields with `@self`.
	 * @param string               $organisationUuid The active organisation.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the stamp is another organisation.
	 */
	private function assertOwnStamp(array $fields, string $organisationUuid): void {
		$stamp = (string)($fields['@self']['organisation'] ?? '');
		if ($stamp !== '' && $stamp !== $organisationUuid) {
			throw new RuntimeException('The visible organisation settings belong to another organisation; nothing is changed.');
		}
	}//end assertOwnStamp()

	/**
	 * Whether a huisstijl is readable by the caller, inside the active organisation.
	 *
	 * @param string $uuid The huisstijl.
	 *
	 * @return bool True when it resolves.
	 */
	private function huisstijlIsVisible(string $uuid): bool {
		try {
			$found = $this->objectResolver->resolve()->find(
				id: $uuid,
				options: new ObjectFindOptions(register: self::REGISTER, schema: 'huisstijl')
			);
		} catch (Throwable) {
			return false;
		}

		return $found !== null;
	}//end huisstijlIsVisible()

	/**
	 * The stored fields as the settings section shows them.
	 *
	 * @param array<string, mixed>|null $stored The stored object.
	 *
	 * @return array<string, mixed> Every field, null when not set.
	 */
	private function present(?array $stored): array {
		$settings = ['uuid' => null];
		foreach (OrganisationSettingsInput::FIELDS as $field) {
			$settings[$field] = ($stored[$field] ?? null);
		}

		if ($stored !== null) {
			$settings['uuid'] = (string)$stored['uuid'];
		}

		return $settings;
	}//end present()
}//end class
