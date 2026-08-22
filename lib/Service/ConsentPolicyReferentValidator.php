<?php

/**
 * ConsentPolicyReferentValidator — scope contract + policyMatch referent checks.
 *
 * Two service-level checks the `publicationConsent` schema cannot express:
 *
 *   - the per-`scope` field-set contract (`scope=document` needs a
 *     `documentId`; `scope=entity` needs `matchRules` + `consentMethod` and
 *     must carry neither `documentId` nor `policyMatch`);
 *   - the polymorphic-referent rule: a caller-supplied `policyMatch` may only
 *     point at a `publicationProhibition`, or at a `publicationConsent` whose
 *     `scope` is `entity`. That one needs an OpenRegister lookup, which is why
 *     it cannot live in the stateless ConsentScopeValidator.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use InvalidArgumentException;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Validates publicationConsent scope rules and policyMatch referents.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
 */
class ConsentPolicyReferentValidator {
	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger Logger for error reporting.
	 * @param ContainerInterface $container Container for DI.
	 * @param IAppManager $appManager App manager interface.
	 * @param ObjectResultExtractor $resultExtractor Coerces OpenRegister results to plain rows.
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly ContainerInterface $container,
		private readonly IAppManager $appManager,
		private readonly ObjectResultExtractor $resultExtractor = new ObjectResultExtractor(),
	) {

	}//end __construct()

	/**
	 * Validate publication consent data against the scope rules.
	 *
	 * @param array<string, mixed> $data Consent data to validate.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When data violates scope constraints.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
	 */
	public function validatePublicationConsentData(array $data): void {
		$scope = ($data['scope'] ?? null);

		if ($scope === 'document') {
			$this->assertDocumentScopeData(data: $data);
			return;
		}

		if ($scope === 'entity') {
			$this->assertEntityScopeData(data: $data);
		}

	}//end validatePublicationConsentData()

	/**
	 * Enforce the scope=document field-set contract.
	 *
	 * @param array<string, mixed> $data Consent data to validate.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When the contract is violated.
	 */
	private function assertDocumentScopeData(array $data): void {
		if (empty($data['documentId']) === true) {
			throw new InvalidArgumentException(message: 'scope=document requires a non-empty documentId');
		}

		// A caller-supplied policyMatch must point at a permitted
		// referent (restored after 917b80e7 wiped the check).
		$policyMatch = ($data['policyMatch'] ?? null);
		if (is_string($policyMatch) === true && $policyMatch !== '') {
			$this->assertPolicyMatchReferentValid(uuid: $policyMatch);
		}

	}//end assertDocumentScopeData()

	/**
	 * Enforce the scope=entity field-set contract.
	 *
	 * @param array<string, mixed> $data Consent data to validate.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When the contract is violated.
	 */
	private function assertEntityScopeData(array $data): void {
		if (isset($data['documentId']) === true) {
			throw new InvalidArgumentException(message: 'scope=entity must not include documentId');
		}

		if (empty($data['matchRules']) === true) {
			throw new InvalidArgumentException(message: 'scope=entity requires a non-empty matchRules array');
		}

		if (empty($data['consentMethod']) === true) {
			throw new InvalidArgumentException(message: 'scope=entity requires a non-empty consentMethod');
		}

		if (isset($data['policyMatch']) === true) {
			throw new InvalidArgumentException(message: 'scope=entity must not include policyMatch');
		}

	}//end assertEntityScopeData()

	/**
	 * Verify a policyMatch UUID points at a permitted referent.
	 *
	 * Permitted: a `publicationProhibition` record, or a `publicationConsent`
	 * record with `scope: "entity"`. Rejects: a `publicationConsent` with
	 * `scope: "document"` (or missing scope). Dangling UUIDs are not blocked
	 * here — the spec leaves that to OpenRegister's referential-integrity
	 * surface — but they are logged.
	 *
	 * @param string $uuid The candidate UUID.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException If the referent's scope is not entity.
	 */
	private function assertPolicyMatchReferentValid(string $uuid): void {
		try {
			$objectService = $this->getObjectService();

			$prohibitionHits = $objectService->findAll(
				config: [
					'filters' => [
						'register' => 'consent',
						'schema' => 'publicationProhibition',
						'uuid' => $uuid,
					],
					'limit' => 1,
				],
				_rbac: false
			);
			if ($this->resultExtractor->firstRow(result: $prohibitionHits) !== null) {
				return;
			}

			$consentHits = $objectService->findAll(
				config: [
					'filters' => [
						'register' => 'consent',
						'schema' => 'publicationConsent',
						'uuid' => $uuid,
					],
					'limit' => 1,
				],
				_rbac: false
			);

			$consentObject = $this->resultExtractor->firstRow(result: $consentHits);
			if ($consentObject === null) {
				$msg = 'policyMatch UUID "%s" does not resolve to a known prohibition or entity-scope publicationConsent record.';
				throw new InvalidArgumentException(message: sprintf($msg, $uuid));
			}

			$referentScope = (string)($consentObject['scope'] ?? 'document');
			if ($referentScope !== 'entity') {
				throw new InvalidArgumentException(
					message: sprintf(
						'policyMatch points at a publicationConsent with scope=%s; only entity-scope records are permitted.',
						$referentScope
					)
				);
			}
		} catch (InvalidArgumentException $e) {
			throw $e;
		} catch (Exception $e) {
			// Treat lookup failure as a hard error rather than a silent
			// pass — a write referencing a `policyMatch` we cannot
			// validate must not be persisted, even if the underlying
			// ObjectService threw an infrastructure error. Surfacing
			// the failure (mapped to HTTP 5xx by the controller) is
			// strictly safer than masking it with a warning log.
			$this->logger->error(
				'ConsentService: policyMatch referent lookup failed — rejecting write',
				['policyMatch' => $uuid, 'error' => $e->getMessage()]
			);
			throw new InvalidArgumentException(
				message: sprintf(
					'policyMatch UUID "%s" could not be validated against the policy registry: %s',
					$uuid,
					$e->getMessage()
				),
				previous: $e
			);
		}//end try

	}//end assertPolicyMatchReferentValid()

	/**
	 * Get the ObjectService from OpenRegister.
	 *
	 * @return \OCA\OpenRegister\Service\ObjectService The ObjectService instance.
	 *
	 * @throws RuntimeException If OpenRegister is not available.
	 */
	private function getObjectService(): \OCA\OpenRegister\Service\ObjectService {
		if (in_array(needle: 'openregister', haystack: $this->appManager->getInstalledApps(), strict: true) === true) {
			return $this->container->get(id: 'OCA\OpenRegister\Service\ObjectService');
		}

		throw new RuntimeException(message: 'OpenRegister service is not available.');
	}//end getObjectService()
}//end class
