<?php

/**
 * Effective Settings
 *
 * The settings that may differ per organisation (multi-tenant-hardening D4,
 * REQ-DDMTH-008), resolved in one order for every consumer: the active
 * organisation's `organisationSettings` value, then the instance setting in
 * IAppConfig, then the code default. A null override inherits.
 *
 * The organisation is never named here. OpenRegister scopes the search to the
 * caller's active organisation, so the object it returns is that
 * organisation's own; another organisation's object is never seen.
 *
 * Instance-wide settings (register wiring, enrichment toggles, the signing
 * provider) are deliberately not resolved here: they stay instance-global.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/multi-tenant-hardening/tasks.md#task-3.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Resolves a per-organisation setting: organisation, then instance, then code.
 *
 * @spec openspec/changes/multi-tenant-hardening/tasks.md#task-3.1
 */
class EffectiveSettings {
	use ReadsStoredObjectRows;

	/**
	 * The schema of an organisation's overrides.
	 *
	 * @var string
	 */
	public const SCHEMA = 'organisationSettings';

	/**
	 * The objection period, in days.
	 *
	 * @var string
	 */
	public const CONSENT_PERIOD_DAYS = 'consentPeriodDays';

	/**
	 * The WOO anonymization profile.
	 *
	 * @var string
	 */
	public const ANONYMIZATION_PROFILE = 'anonymizationProfile';

	/**
	 * The huisstijl generation uses when a request names none.
	 *
	 * @var string
	 */
	public const DEFAULT_HUISSTIJL = 'defaultHuisstijl';

	/**
	 * Per setting: the instance IAppConfig key (null for none) and the code default.
	 *
	 * @var array<string, array{0: string|null, 1: mixed}>
	 */
	private const SETTINGS = [
		self::CONSENT_PERIOD_DAYS => ['publication_objection_period_days', 28],
		self::ANONYMIZATION_PROFILE => ['filinq_woo_entity_profiles', null],
		self::DEFAULT_HUISSTIJL => [null, null],
	];

	/**
	 * The active organisation's overrides, read once per request.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $overrides = null;

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService.
	 * @param IAppConfig                    $config         The instance settings.
	 * @param LoggerInterface               $logger         Logger.
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly IAppConfig $config,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The effective value of a per-organisation setting.
	 *
	 * @param string $name `consentPeriodDays`, `anonymizationProfile` or `defaultHuisstijl`.
	 *
	 * @return mixed The organisation's value, else the instance value, else the code default;
	 *               null for an unknown name.
	 *
	 * @spec openspec/changes/multi-tenant-hardening/tasks.md#task-3.1
	 */
	public function get(string $name): mixed {
		if (array_key_exists($name, self::SETTINGS) === false) {
			return null;
		}

		$override = ($this->overrides()[$name] ?? null);
		if ($override !== null && $override !== '') {
			return $override;
		}

		[$key, $default] = self::SETTINGS[$name];
		if ($key === null) {
			return $default;
		}

		$instance = trim($this->config->getValueString('filinq', $key, ''));
		if ($instance === '') {
			return $default;
		}

		return $instance;

	}//end get()

	/**
	 * The instance objection period an organisation without an override inherits.
	 *
	 * The instance setting when it is a whole number from 1 to 365, else the
	 * code default; never an organisation's override.
	 *
	 * @return int The days.
	 *
	 * @spec openspec/changes/multi-tenant-hardening/specs/admin-settings/spec.md#requirement-effective-settings-resolve-organisation-override-then-instance-default-req-ddmth-008
	 */
	public function instanceConsentPeriodDays(): int {
		[$key, $default] = self::SETTINGS[self::CONSENT_PERIOD_DAYS];
		$days            = trim($this->config->getValueString('filinq', $key, ''));
		if (preg_match('/^\d+$/', $days) !== 1 || (int)$days < 1 || (int)$days > 365) {
			return (int)$default;
		}

		return (int)$days;

	}//end instanceConsentPeriodDays()

	/**
	 * The objection period in days, from 1 to 365; 28 when no setting gives a usable one.
	 *
	 * @return int The days.
	 *
	 * @spec openspec/changes/multi-tenant-hardening/tasks.md#task-3.1
	 */
	public function consentPeriodDays(): int {
		$days = $this->get(name: self::CONSENT_PERIOD_DAYS);
		if (is_numeric($days) === false || (int)$days < 1 || (int)$days > 365) {
			return (int)self::SETTINGS[self::CONSENT_PERIOD_DAYS][1];
		}

		return (int)$days;

	}//end consentPeriodDays()

	/**
	 * The active organisation's own anonymization profile override, or null to use the instance's.
	 *
	 * @return array<string, mixed>|null The profile, `anonymize` and `keepVisible`.
	 *
	 * @spec openspec/changes/multi-tenant-hardening/tasks.md#task-3.1
	 */
	public function organisationAnonymizationProfile(): ?array {
		$profile = ($this->overrides()[self::ANONYMIZATION_PROFILE] ?? null);
		if (is_array($profile) === false || $profile === []) {
			return null;
		}

		return $profile;

	}//end organisationAnonymizationProfile()

	/**
	 * The huisstijl generation uses when a request names none, or null.
	 *
	 * @return string|null The huisstijl uuid.
	 *
	 * @spec openspec/changes/multi-tenant-hardening/tasks.md#task-3.1
	 */
	public function defaultHuisstijl(): ?string {
		$huisstijl = $this->get(name: self::DEFAULT_HUISSTIJL);
		if (is_string($huisstijl) === false || trim($huisstijl) === '') {
			return null;
		}

		return trim($huisstijl);

	}//end defaultHuisstijl()

	/**
	 * The active organisation's overrides; none when it has no object or it cannot be read.
	 *
	 * @return array<string, mixed>
	 */
	private function overrides(): array {
		if ($this->overrides !== null) {
			return $this->overrides;
		}

		$this->overrides = [];
		try {
			$rows = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: []
			);
		} catch (Throwable $e) {
			// Unreadable overrides inherit: the instance setting still holds.
			$this->logger->warning('The organisation settings could not be read', ['exception' => $e->getMessage()]);
			return $this->overrides;
		}

		$rows = array_values((array)$rows);
		if (count($rows) > 1) {
			// One object per organisation; more means the scope did not hold,
			// and no organisation's override is applied rather than a stranger's.
			$this->logger->warning('More than one organisation settings object is visible; none is applied', ['count' => count($rows)]);
			return $this->overrides;
		}

		if ($rows !== []) {
			$this->overrides = $this->storedFields(row: $rows[0]);
		}

		return $this->overrides;

	}//end overrides()
}//end class
