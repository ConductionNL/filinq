<?php

/**
 * Prohibition Override Committer
 *
 * Commits the overrides the prohibition gate validated: for each released
 * override it writes the Filinq audit entry FIRST and only then PATCHes the
 * OpenRegister EntityRelation. AVG Art. 30 / 10-year archival means an override
 * released into OpenRegister without a Filinq audit record is a compliance
 * violation, so the audit write is fail-closed.
 *
 * Extracted verbatim from AnonymizationService.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Writes the audit entry and the OpenRegister skip flag for released overrides.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 */
class ProhibitionOverrideCommitter {
	/**
	 * Register slug for the prohibition override audit schema.
	 *
	 * `filinq`, not `consent`: this app declares ONE register holding all 23
	 * schemas. The five it used to declare are retired, and the objects were
	 * moved across by OCA\Filinq\Repair\ConsolidateRegisters.
	 *
	 * @var string
	 */
	private const OVERRIDE_AUDIT_REGISTER = 'filinq';

	/**
	 * Schema slug for the prohibition override audit entries.
	 *
	 * @var string
	 */
	private const OVERRIDE_AUDIT_SCHEMA = 'prohibitionOverrideAudit';

	/**
	 * Constructor for ProhibitionOverrideCommitter
	 *
	 * @param LoggerInterface $logger Logger for commit failures.
	 * @param OpenRegisterServiceLocator $locator Resolver for OpenRegister services and mappers.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly OpenRegisterServiceLocator $locator,
	) {

	}//end __construct()

	/**
	 * Commit validated override entries: write audit + PATCH OR skip flag.
	 *
	 * Processes overrides sequentially. Writes the Filinq audit entry BEFORE
	 * the OR PATCH for each override. On OR PATCH failure, stops processing
	 * further overrides and throws RuntimeException (HTTP 500).
	 *
	 * @param array<string, array<string, mixed>> $released Validated overrides keyed by 'ruleId|entityId'.
	 * @param int $fileId Nextcloud file ID (for audit entry).
	 * @param string $userId UID of the acting user.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the audit write or the OR PATCH fails.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
	 */
	public function commit(array $released, int $fileId, string $userId): void {
		$now = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);

		try {
			$objectService = $this->locator->get(
				className: 'OCA\OpenRegister\Service\ObjectService'
			);
			$entityRelationMapper = $this->locator->get(
				className: 'OCA\OpenRegister\Db\EntityRelationMapper'
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				'ProhibitionGate: OR services unavailable — skipping override commit',
				['error' => $e->getMessage()]
			);
			return;
		}

		foreach ($released as $override) {
			$match = $override['match'];
			$relationId = (int)($match['entityRelationId'] ?? 0);

			$this->writeAudit(
				objectService: $objectService,
				auditEntry: [
					'ruleId' => $match['ruleId'],
					'entityRelationId' => $relationId,
					'fileId' => $fileId,
					'reason' => $override['reason'],
					'acknowledgedBy' => $userId,
					'acknowledgedAt' => $now,
				]
			);

			$this->patchSkipFlag(
				entityRelationMapper: $entityRelationMapper,
				relationId: $relationId,
				ruleId: (string)$match['ruleId'],
				fileId: $fileId
			);
		}//end foreach

	}//end commit()

	/**
	 * Write the Filinq audit entry for one released override.
	 *
	 * If the audit write fails we MUST NOT proceed to the OR PATCH — fail-closed.
	 *
	 * @param mixed $objectService OpenRegister ObjectService.
	 * @param array<string, mixed> $auditEntry The audit record to persist.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the audit write fails.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
	 */
	private function writeAudit(mixed $objectService, array $auditEntry): void {
		try {
			$objectService->saveObject(
				object: $auditEntry,
				register: self::OVERRIDE_AUDIT_REGISTER,
				schema: self::OVERRIDE_AUDIT_SCHEMA
			);
		} catch (Throwable $e) {
			$this->logger->error(
				'ProhibitionGate: failed to write audit entry — aborting override commit',
				[
					'ruleId' => $auditEntry['ruleId'],
					'relationId' => $auditEntry['entityRelationId'],
					'fileId' => $auditEntry['fileId'],
					'error' => $e->getMessage(),
				]
			);
			throw new RuntimeException(
				'ProhibitionGate: refusing to commit override without audit entry: ' . $e->getMessage(),
				500,
				$e
			);
		}//end try

	}//end writeAudit()

	/**
	 * PATCH the OpenRegister EntityRelation with skipAnonymization=true.
	 *
	 * @param mixed $entityRelationMapper OpenRegister EntityRelationMapper.
	 * @param int $relationId The relation to patch; ignored when not positive.
	 * @param string $ruleId The rule id (for the log context).
	 * @param int $fileId The file id (for the log context).
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the OR PATCH fails.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
	 */
	private function patchSkipFlag(
		mixed $entityRelationMapper,
		int $relationId,
		string $ruleId,
		int $fileId,
	): void {
		if ($relationId <= 0) {
			return;
		}

		try {
			$entityRelationMapper->updateDecisionMetadata(
				$relationId,
				['skipAnonymization' => true]
			);
		} catch (Throwable $e) {
			$this->logger->error(
				'ProhibitionGate: OR PATCH failed — stopping override processing',
				[
					'ruleId' => $ruleId,
					'relationId' => $relationId,
					'fileId' => $fileId,
					'error' => $e->getMessage(),
				]
			);
			throw new RuntimeException(
				'ProhibitionGate: failed to update EntityRelation skip flag: ' . $e->getMessage(),
				500,
				$e
			);
		}//end try

	}//end patchSkipFlag()
}//end class
