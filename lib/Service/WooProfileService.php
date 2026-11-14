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

use OCA\Filinq\Kernel\Contracts\AnonymizationProfileServiceInterface;
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
class WooProfileService implements AnonymizationProfileServiceInterface {
	private const DEFAULT_ANONYMIZE = ['PERSON', 'BSN', 'PHONE', 'EMAIL', 'IBAN', 'ADDRESS'];
	private const DEFAULT_KEEP = ['ORGANIZATION', 'LOCATION', 'DATE'];

	/**
	 * The app config key holding the profiles per document type, as a JSON map of type to profile.
	 *
	 * @var string
	 */
	private const TYPE_PROFILES_KEY = 'filinq_woo_entity_profiles_by_type';

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
			$profile = $this->normalised(candidate: json_decode($stored, true));
			if ($profile !== null) {
				return $profile;
			}
		}

		return ['anonymize' => self::DEFAULT_ANONYMIZE, 'keep' => self::DEFAULT_KEEP];
	}//end getProfile()

	/**
	 * The profile a review of a document of this type starts from.
	 *
	 * A document type with its own profile uses it; any other type, and a
	 * document without a type, uses the default profile ({@see getProfile()}).
	 *
	 * @param string|null $documentType The document type, or null when the document has none.
	 *
	 * @return array{anonymize: array<string>, keep: array<string>} The profile.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/batch-anonymization/spec.md#requirement-an-anonymisation-profile-is-kept-per-document-type-req-scr-013
	 */
	public function getProfileFor(?string $documentType): array {
		$type = trim((string)$documentType);
		if ($type !== '') {
			$profiles = $this->typeProfiles();
			if (isset($profiles[$type]) === true) {
				return $profiles[$type];
			}
		}

		return $this->getProfile();
	}//end getProfileFor()

	/**
	 * Every profile linked to a document type, by type.
	 *
	 * @return array<string, array{anonymize: array<string>, keep: array<string>}> The profiles.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/batch-anonymization/spec.md#requirement-an-anonymisation-profile-is-kept-per-document-type-req-scr-013
	 */
	public function typeProfiles(): array {
		$decoded = json_decode($this->appConfig->getValueString('filinq', self::TYPE_PROFILES_KEY, ''), true);
		if (is_array($decoded) === false) {
			return [];
		}

		$profiles = [];
		foreach ($decoded as $type => $candidate) {
			$profile = $this->normalised(candidate: $candidate);
			if (is_string($type) === true && trim($type) !== '' && $profile !== null) {
				$profiles[$type] = $profile;
			}
		}

		ksort($profiles);
		return $profiles;
	}//end typeProfiles()

	/**
	 * Link a profile to a document type, replacing the one it had.
	 *
	 * @param string                                               $documentType The document type.
	 * @param array{anonymize: array<string>, keep: array<string>} $profile      The profile.
	 *
	 * @throws \InvalidArgumentException When the type is empty or the profile lacks its two lists.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/batch-anonymization/spec.md#requirement-an-anonymisation-profile-is-kept-per-document-type-req-scr-013
	 */
	public function saveProfileFor(string $documentType, array $profile): void {
		$type = trim($documentType);
		$normalised = $this->normalised(candidate: $profile);
		if ($type === '' || $normalised === null) {
			throw new \InvalidArgumentException('A profile needs a document type and both an anonymize and a keep list');
		}

		$profiles = $this->typeProfiles();
		$profiles[$type] = $normalised;
		$this->storeTypeProfiles(profiles: $profiles);
	}//end saveProfileFor()

	/**
	 * Unlink the profile of a document type; its documents use the default profile again.
	 *
	 * @param string $documentType The document type.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/batch-anonymization/spec.md#requirement-an-anonymisation-profile-is-kept-per-document-type-req-scr-013
	 */
	public function removeProfileFor(string $documentType): void {
		$profiles = $this->typeProfiles();
		unset($profiles[trim($documentType)]);
		$this->storeTypeProfiles(profiles: $profiles);
	}//end removeProfileFor()

	/**
	 * Store the profiles per document type.
	 *
	 * @param array<string, array{anonymize: array<string>, keep: array<string>}> $profiles The profiles.
	 *
	 * @return void
	 */
	private function storeTypeProfiles(array $profiles): void {
		$this->appConfig->setValueString('filinq', self::TYPE_PROFILES_KEY, (string)json_encode((object)$profiles));
	}//end storeTypeProfiles()

	/**
	 * A profile with both lists as lists of strings, or null when it is not one.
	 *
	 * @param mixed $candidate The decoded value.
	 *
	 * @return array{anonymize: array<string>, keep: array<string>}|null
	 */
	private function normalised(mixed $candidate): ?array {
		if (is_array($candidate) === false
			|| is_array($candidate['anonymize'] ?? null) === false
			|| is_array($candidate['keep'] ?? null) === false
		) {
			return null;
		}

		return [
			'anonymize' => array_values(array_map(static fn ($v): string => (string)$v, $candidate['anonymize'])),
			'keep' => array_values(array_map(static fn ($v): string => (string)$v, $candidate['keep'])),
		];
	}//end normalised()

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
	 * @param string      $entityType   Entity type to check (e.g., "PERSON", "BSN").
	 * @param string|null $documentType The document's type, whose own profile applies; null for the default profile.
	 *
	 * @return bool True when the type should be anonymized.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-woo-entity-category-profiles
	 */
	public function shouldAnonymize(string $entityType, ?string $documentType=null): bool {
		return in_array($entityType, $this->getProfileFor(documentType: $documentType)['anonymize'], true);
	}//end shouldAnonymize()
}//end class
