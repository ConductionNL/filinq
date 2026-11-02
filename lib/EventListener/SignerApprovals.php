<?php

/**
 * Signer Approvals
 *
 * Decides what counts as a signed approval on an OR sequence task: an
 * approving outcome and a named signer. Task values are read through
 * callable getters, so this class loads with OR absent.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

/**
 * What counts as a signed approval.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */
class SignerApprovals {

	/**
	 * The outcomes that approve a signing step. OR's task vocabulary spells a
	 * completed approval `approved`; every other non-rejecting outcome is
	 * not a decision to sign.
	 *
	 * @var array<int, string>
	 */
	private const APPROVING_OUTCOMES = ['approved'];

	/**
	 * Whether an outcome approves a signing step.
	 *
	 * @param string $outcome The task's outcome.
	 *
	 * @return bool True when the outcome approves.
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function isApprovingOutcome(string $outcome): bool {
		return in_array(strtolower(trim($outcome)), self::APPROVING_OUTCOMES, true);
	}//end isApprovingOutcome()

	/**
	 * Whether every task carries an approving outcome and a non-blank signer.
	 *
	 * @param array<int, object> $tasks The tasks.
	 *
	 * @return bool True when each task is a signed approval.
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function areSigned(array $tasks): bool {
		foreach ($tasks as $task) {
			if ($this->isSigned(task: $task) === false) {
				return false;
			}
		}

		return true;
	}//end areSigned()

	/**
	 * Whether a task carries an approving outcome and a non-blank completing user.
	 *
	 * @param object $task The task.
	 *
	 * @return bool True for a signed approval.
	 */
	private function isSigned(object $task): bool {
		$outcome = (string) ($this->value(task: $task, getter: 'getOutcome') ?? '');
		$signer = trim((string) ($this->value(task: $task, getter: 'getCompletedBy') ?? ''));

		return $this->isApprovingOutcome(outcome: $outcome) === true && $signer !== '';
	}//end isSigned()

	/**
	 * One getter's value, or null when it is not callable.
	 *
	 * @param object $task   The task.
	 * @param string $getter The getter name.
	 *
	 * @return mixed The value.
	 */
	private function value(object $task, string $getter): mixed {
		if (is_callable([$task, $getter]) === false) {
			return null;
		}

		return $task->$getter();
	}//end value()
}//end class
