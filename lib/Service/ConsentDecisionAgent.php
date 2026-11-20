<?php

/**
 * Filinq Consent Decision Agent
 *
 * The one way an agent reaches consent records: record the outcome of one
 * consent request, by id, behind Hermiq's approval gate
 * (hermiq-ai-tooling REQ-DDHAT-002). It is a keyhole: no search, no list, no
 * get, and an answer that carries status fields only. The caller must be a
 * consent officer, exactly as the in-app update route demands, and a record
 * the caller may not reach answers the same `not_found` as one that does not
 * exist. The transition rules are the in-app ones, unchanged, and every
 * status event it writes says `via: mcp` beside the principal it ran as
 * (REQ-DDHAT-003).
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/hermiq-ai-tooling/tasks.md#2-the-consent-decision-tool-code
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use InvalidArgumentException;
use OCA\Filinq\Kernel\Contracts\ConsentCrudServiceInterface;
use OCA\OpenRegister\Mcp\Attribute\McpTool;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\IGroupManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Records one consent decision for an agent.
 *
 * @spec openspec/changes/hermiq-ai-tooling/specs/filinq-mcp-surface/spec.md#requirement-an-approval-gated-consent-decision-tool-exists-req-ddhat-002
 */
class ConsentDecisionAgent {

	/**
	 * How an agent's change is attributed, as the anonymisation intake does.
	 */
	public const VIA = AnonymizationService::MCP_TRIGGER;

	/**
	 * The only fields an answer carries.
	 */
	public const STATUS_FIELDS = ['consentStatus', 'notificationStatus', 'publicationDecision'];

	/**
	 * Constructor.
	 *
	 * @param ConsentCrudServiceInterface $consents    Reads and updates consent records with the in-app rules.
	 * @param IUserSession                $userSession The principal the agent runs as.
	 * @param IGroupManager               $groups      Whether that principal is a consent officer.
	 * @param LoggerInterface             $logger      Records each agent decision with `triggeredBy: mcp`.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ConsentCrudServiceInterface $consents,
		private readonly IUserSession $userSession,
		private readonly IGroupManager $groups,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	#[McpTool(
		name: 'recordConsentDecision',
		subject: 'consentDecision',
		action: 'update',
		description: 'Record the outcome of one consent request you were told about: give the consent record id '
			. '(consentId), the new consentStatus (consent_given, objection_received, no_response or anonymized) '
			. 'and, for consent_given or objection_received, how the answer reached the organisation (evidence). '
			. 'Only a consent officer may do this, and a person must approve it first. The answer holds status '
			. 'fields only. It never lists, searches or shows consent records.',
		readOnlyHint: false,
		destructiveHint: false,
		idempotentHint: true,
		scope: 'update'
	)]
	/**
	 * Record one consent decision.
	 *
	 * `not_found` for a caller who is not a consent officer, an unconfigured
	 * consent register, an unknown id and a record the caller may not reach,
	 * all in the same shape. `refused` with the rule's sentence for a move the
	 * consent flow does not allow, missing evidence, or a standing consent the
	 * caller may not change. `recorded` with the status fields otherwise.
	 *
	 * @param string $consentId     The consent record's id.
	 * @param string $consentStatus The status to record.
	 * @param string $evidence      How the answer reached the organisation, when the status needs it.
	 *
	 * @return array<string, mixed> `status`, `consentId`, and the status fields when recorded or `reason` when refused.
	 *
	 * @spec openspec/changes/hermiq-ai-tooling/specs/filinq-mcp-surface/spec.md#requirement-an-approval-gated-consent-decision-tool-exists-req-ddhat-002
	 * @spec openspec/changes/hermiq-ai-tooling/specs/filinq-mcp-surface/spec.md#requirement-agent-initiated-writes-are-attributable-req-ddhat-003
	 */
	public function recordConsentDecision(string $consentId, string $consentStatus, string $evidence = ''): array {
		$reach = $this->reachable(consentId: $consentId);
		if ($reach === null) {
			return $this->notFound(consentId: $consentId);
		}

		[$user, $config] = $reach;

		$this->logger->info(
			'Filinq: agent consent decision',
			['consentId' => $consentId, 'consentStatus' => $consentStatus, 'triggeredBy' => self::VIA, 'uid' => $user->getUID()]
		);

		try {
			$saved = $this->consents->updateConsentStatus(
				$consentId,
				$config['register'],
				$config['schema'],
				['consentStatus' => $consentStatus, 'evidence' => $evidence],
				$user,
				self::VIA
			);
		} catch (InvalidArgumentException $e) {
			return ['status' => 'refused', 'consentId' => $consentId, 'reason' => $e->getMessage()];
		} catch (OCSForbiddenException $e) {
			return ['status' => 'refused', 'consentId' => $consentId, 'reason' => 'This standing consent cannot be changed by you.'];
		} catch (Throwable $e) {
			$this->logger->warning(
				'Filinq: agent consent decision failed',
				['consentId' => $consentId, 'triggeredBy' => self::VIA, 'uid' => $user->getUID(), 'exception' => $e->getMessage()]
			);
			return ['status' => 'failed', 'consentId' => $consentId];
		}

		return ['status' => 'recorded', 'consentId' => $consentId] + $this->statusFields(record: $saved);
	}//end recordConsentDecision()

	/**
	 * The principal and the consent register, when the caller is a consent
	 * officer and the record exists; null for every other case, so they all
	 * answer the same not-found.
	 *
	 * @param string $consentId The id asked for.
	 *
	 * @return array{0: \OCP\IUser, 1: array{register: string, schema: string}}|null
	 */
	private function reachable(string $consentId): ?array {
		$user   = $this->userSession->getUser();
		$config = $this->consents->getConsentConfig();
		if ($user === null || $config === null || trim($consentId) === '' || $this->isOfficer(uid: $user->getUID()) === false) {
			return null;
		}

		try {
			$existing = $this->consents->getConsent($consentId, $config['register'], $config['schema']);
		} catch (Throwable $e) {
			return null;
		}

		if ($existing === null) {
			return null;
		}

		return [$user, $config];
	}//end reachable()

	/**
	 * The status fields of a saved record, nothing else of it.
	 *
	 * @param array<string, mixed> $record The saved record.
	 *
	 * @return array<string, string>
	 */
	private function statusFields(array $record): array {
		$fields = [];
		foreach (self::STATUS_FIELDS as $field) {
			if (is_string($record[$field] ?? null) === true) {
				$fields[$field] = $record[$field];
			}
		}

		return $fields;
	}//end statusFields()

	/**
	 * Whether a user is a consent officer: an administrator or a member of an
	 * officer group, as the in-app update route requires.
	 *
	 * @param string $uid The user.
	 *
	 * @return bool
	 */
	private function isOfficer(string $uid): bool {
		if ($this->groups->isAdmin($uid) === true) {
			return true;
		}

		foreach (ConsentOfficers::GROUPS as $group) {
			if ($this->groups->isInGroup($uid, $group) === true) {
				return true;
			}
		}

		return false;
	}//end isOfficer()

	/**
	 * The one answer for every record the caller cannot reach.
	 *
	 * @param string $consentId The id asked for.
	 *
	 * @return array{status: string, consentId: string}
	 */
	private function notFound(string $consentId): array {
		return ['status' => 'not_found', 'consentId' => $consentId];
	}//end notFound()
}//end class
