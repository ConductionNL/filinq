<?php

/**
 * Custom Dictionary Access Gate
 *
 * The fail-closed organisation-membership gate behind
 * {@see CustomDictionaryService}. A record without an organisation, an
 * anonymous caller, or any failure resolving OpenRegister's
 * `OrganisationService` is treated as inaccessible — never as
 * "everyone may access it". Extracted from CustomDictionaryService so the
 * authorisation decision lives in one small, separately testable class.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCP\App\IAppManager;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Fail-closed organisation gate for custom dictionaries and terms.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */
class CustomDictionaryAccessGate {

	/**
	 * OpenRegister's app id, used for the install-presence check.
	 *
	 * @var string
	 */
	private const OPENREGISTER_APP_ID = 'openregister';

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container DI container for lazy OpenRegister service resolution.
	 * @param IAppManager $appManager App manager (OpenRegister availability check).
	 * @param IUserSession $userSession Current-user lookup for the organisation gate.
	 * @param LoggerInterface $logger Structured logger.
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly IAppManager $appManager,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Whether OpenRegister is installed. Callers use this to return an
	 * explanatory unavailable state instead of crashing (REQ-DDCDR-004).
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function isAvailable(): bool {
		return in_array(self::OPENREGISTER_APP_ID, $this->appManager->getInstalledApps(), true);
	}//end isAvailable()

	/**
	 * Fail-closed organisation-membership check for one record.
	 *
	 * A record without an organisation, or any failure resolving
	 * OpenRegister's `OrganisationService`, is treated as inaccessible —
	 * never as "everyone may access it".
	 *
	 * @param array<string, mixed> $record The record to check.
	 *
	 * @return bool True when the current caller may read/write this record.
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function callerHasAccess(array $record): bool {
		$organisationUuid = (string)($this->selfMeta(record: $record)['organisation'] ?? '');
		if ($organisationUuid === '') {
			return false;
		}

		if ($this->userSession->getUser() === null) {
			return false;
		}

		try {
			$organisationService = $this->getOrganisationService();
			return $organisationService->hasAccessToOrganisation($organisationUuid);
		} catch (Throwable $e) {
			$this->logger->warning(
				'[CustomDictionaryAccessGate] organisation-access check failed; denying access (fail-closed).',
				['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);
			return false;
		}

	}//end callerHasAccess()

	/**
	 * Extract the `@self` metadata block from a record, defensively.
	 *
	 * @param array<string, mixed> $record The record.
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function selfMeta(array $record): array {
		$self = $record['@self'] ?? [];
		if (is_array($self) === false) {
			return [];
		}

		return $self;
	}//end selfMeta()

	/**
	 * Lazily resolve OpenRegister's `OrganisationService` by FQCN via the
	 * DI container (same cross-app pattern used throughout DocuDesk), so
	 * this class stays loadable without OpenRegister installed.
	 *
	 * @return object OpenRegister's `OrganisationService`.
	 *
	 * @throws RuntimeException When OpenRegister is not installed.
	 */
	private function getOrganisationService(): object {
		if ($this->isAvailable() === false) {
			throw new RuntimeException('OpenRegister is not available.');
		}

		return $this->container->get('OCA\OpenRegister\Service\OrganisationService');
	}//end getOrganisationService()
}//end class
