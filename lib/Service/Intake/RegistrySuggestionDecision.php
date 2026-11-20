<?php

/**
 * Registry Suggestion Decision
 *
 * A registrar's answer to one registry suggestion on an intake document:
 * accepted (as offered or corrected) or dismissed. Accepting writes the
 * fields the way an accepted party suggestion is written, through the party
 * decision record, and marks the suggestion written; dismissing marks it
 * dismissed. Both name the session user and are saved on the document, so
 * its audit trail carries them.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Intake
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-person-accepts-or-dismisses-a-registry-suggestion-req-ibr-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Intake;

use OCA\Filinq\Exception\IntakeRefusedException;
use OCA\Filinq\Kernel\Contracts\IntakeRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\PartySuggestionServiceInterface;
use OCA\Filinq\Service\IntakeAuthorizationGate;
use OCA\Filinq\Service\IntakeRepository;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUserSession;

/**
 * Accepts or dismisses one registry suggestion.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Intake
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-person-accepts-or-dismisses-a-registry-suggestion-req-ibr-004
 */
class RegistrySuggestionDecision {

	/**
	 * The decisions a registrar can take.
	 *
	 * @var list<string>
	 */
	public const DECISIONS = ['accepted', 'dismissed'];

	/**
	 * The fields a registrar may correct before accepting.
	 *
	 * @var list<string>
	 */
	public const CORRECTABLE = ['name', 'address', 'kvkNumber'];

	/**
	 * Constructor.
	 *
	 * @param IntakeRepositoryInterface $repository  The intake document store.
	 * @param IntakeAuthorizationGate   $gate        The write-rights gate.
	 * @param PartySuggestionServiceInterface $parties     Where an accepted party is written.
	 * @param IUserSession              $userSession The session user who decides.
	 * @param ITimeFactory              $clock       When they decided.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IntakeRepositoryInterface $repository,
		private readonly IntakeAuthorizationGate $gate,
		private readonly PartySuggestionServiceInterface $parties,
		private readonly IUserSession $userSession,
		private readonly ITimeFactory $clock,
	) {

	}//end __construct()

	/**
	 * Accept or dismiss the registry suggestion at one position of a document.
	 *
	 * @param string               $uuid     The intake document.
	 * @param int                  $index    The suggestion's position in `registrySuggestion`.
	 * @param string               $decision `accepted` or `dismissed`.
	 * @param array<string, mixed> $fields   Corrections to name, address or KvK number; others are ignored.
	 *
	 * @return array<string, mixed> The saved intake document.
	 *
	 * @throws IntakeRefusedException When the decision, document, suggestion or rights do not allow it.
	 *
	 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-person-accepts-or-dismisses-a-registry-suggestion-req-ibr-004
	 */
	public function decide(string $uuid, int $index, string $decision, array $fields = []): array {
		if (in_array($decision, self::DECISIONS, true) === false) {
			throw new IntakeRefusedException(message: 'A registry suggestion is accepted or dismissed.', status: 400);
		}

		$userId = (string)($this->userSession->getUser()?->getUID() ?? '');
		if ($userId === '') {
			throw new IntakeRefusedException(message: 'Only a signed-in person decides on a registry suggestion.', status: 401);
		}

		$document = $this->repository->findByUuid(uuid: $uuid);
		if ($document === null) {
			throw new IntakeRefusedException(message: 'This document is not in the intake inbox.', status: 404);
		}

		if ($this->gate->mayWrite(register: IntakeRepository::REGISTER, schema: IntakeRepository::SCHEMA) === false) {
			throw new IntakeRefusedException(
				message: 'You may not write ' . IntakeRepository::SCHEMA . ' in ' . IntakeRepository::REGISTER . '.',
				status: 403
			);
		}

		$suggestions = $document['registrySuggestion'] ?? [];
		if (is_array($suggestions) === false || is_array($suggestions[$index] ?? null) === false) {
			throw new IntakeRefusedException(message: 'This document has no such registry suggestion.', status: 404);
		}

		$suggestion = $suggestions[$index];
		if (($suggestion['written'] ?? false) === true || ($suggestion['dismissed'] ?? false) === true) {
			throw new IntakeRefusedException(message: 'This registry suggestion was already decided.', status: 409);
		}

		$suggestion['decidedBy'] = $userId;
		$suggestion['decidedAt'] = $this->clock->getDateTime()->format(DATE_ATOM);
		if ($decision === 'dismissed') {
			$suggestion['dismissed'] = true;
		}

		if ($decision === 'accepted') {
			$suggestion = $this->accept(document: $document, suggestion: $suggestion, fields: $fields, uuid: $uuid);
		}

		$suggestions[$index] = $suggestion;
		$document['registrySuggestion'] = array_values($suggestions);

		return $this->repository->save(document: $document, uuid: $uuid);

	}//end decide()

	/**
	 * Write the accepted fields as the document's party and mark the suggestion written.
	 *
	 * @param array<string, mixed> $document   The intake document.
	 * @param array<string, mixed> $suggestion The suggestion being accepted.
	 * @param array<string, mixed> $fields     The registrar's corrections.
	 * @param string               $uuid       The intake document's uuid.
	 *
	 * @return array<string, mixed> The suggestion, written.
	 */
	private function accept(array $document, array $suggestion, array $fields, string $uuid): array {
		$offered = [];
		if (is_array($suggestion['fields'] ?? null) === true) {
			$offered = $suggestion['fields'];
		}

		$accepted = $offered;
		foreach (self::CORRECTABLE as $key) {
			if (is_string($fields[$key] ?? null) === true && trim($fields[$key]) !== '') {
				$accepted[$key] = trim($fields[$key]);
			}
		}

		$decision = 'accepted';
		if ($accepted !== $offered) {
			$decision = 'edited';
		}

		$this->parties->recordDecision(
			sender: (string)($document['sender'] ?? ''),
			decision: $decision,
			suggested: $offered,
			accepted: $accepted,
			intakeDocument: $uuid
		);

		$suggestion['accepted'] = $accepted;
		$suggestion['written'] = true;

		return $suggestion;

	}//end accept()
}//end class
