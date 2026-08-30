<?php

/**
 * WOO Profile Service
 *
 * Stores and retrieves the entity-type profile that drives WOO
 * (Wet open overheid) anonymization: which entity types should be
 * anonymized by default and which should be kept. Falls back to a
 * sensible default when no profile has been configured.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-woo-entity-category-profiles
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\IAppConfig;

/**
 * Persists and applies the WOO anonymization profile.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class WooProfileService {
	private const DEFAULT_ANONYMIZE = ['PERSON', 'BSN', 'PHONE', 'EMAIL', 'IBAN', 'ADDRESS'];
	private const DEFAULT_KEEP = ['ORGANIZATION', 'LOCATION', 'DATE'];

	/**
	 * Constructor for WooProfileService
	 *
	 * @param IAppConfig $appConfig App config store backing the persisted profile.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
	) {

	}//end __construct()

	/**
	 * Return the active WOO anonymization profile.
	 *
	 * @return array{anonymize: array<string>, keep: array<string>} Active profile (configured or default).
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-woo-entity-category-profiles
	 */
	public function getProfile(): array {
		$stored = $this->appConfig->getValueString('filinq', 'filinq_woo_entity_profiles', '');
		if ($stored !== '') {
			$decoded = json_decode($stored, true);
			if (is_array($decoded) === true
				&& isset($decoded['anonymize']) === true
				&& is_array($decoded['anonymize']) === true
				&& isset($decoded['keep']) === true
				&& is_array($decoded['keep']) === true
			) {
				return [
					'anonymize' => array_values(array_map(static fn ($v): string => (string)$v, $decoded['anonymize'])),
					'keep' => array_values(array_map(static fn ($v): string => (string)$v, $decoded['keep'])),
				];
			}
		}

		return ['anonymize' => self::DEFAULT_ANONYMIZE, 'keep' => self::DEFAULT_KEEP];
	}//end getProfile()

	/**
	 * Persist a WOO anonymization profile.
	 *
	 * @param array{anonymize: array<string>, keep: array<string>} $profile Profile to store.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-woo-entity-category-profiles
	 */
	public function saveProfile(array $profile): void {
		$this->appConfig->setValueString('filinq', 'filinq_woo_entity_profiles', json_encode($profile));

	}//end saveProfile()

	/**
	 * Check whether the given entity type is subject to anonymization under the active profile.
	 *
	 * @param string $entityType Entity type to check (e.g., "PERSON", "BSN").
	 *
	 * @return bool True when the type should be anonymized.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-woo-entity-category-profiles
	 */
	public function shouldAnonymize(string $entityType): bool {
		return in_array($entityType, $this->getProfile()['anonymize'], true);
	}//end shouldAnonymize()
}//end class
