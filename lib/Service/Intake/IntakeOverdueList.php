<?php

/**
 * Intake Overdue List
 *
 * The documents past their handling deadline that their rule does not count
 * as handled yet: the worklist's Overdue mode.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Intake
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-the-worklist-shows-what-is-overdue-req-iws-002
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Intake;

use OCA\Filinq\Kernel\Contracts\IntakeRepositoryInterface;
use OCA\Filinq\Service\IntakeDefaultRuleService;

/**
 * Lists what is overdue, under each rule's meaning of handled.
 *
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-the-worklist-shows-what-is-overdue-req-iws-002
 */
class IntakeOverdueList {

	/**
	 * Constructor.
	 *
	 * @param IntakeRepositoryInterface $repository The intake documents.
	 * @param IntakeDefaultRuleService  $rules      What handled means, per rule.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IntakeRepositoryInterface $repository,
		private readonly IntakeDefaultRuleService $rules,
	) {

	}//end __construct()

	/**
	 * Every document past its handling deadline that is not handled yet, as of today (UTC).
	 *
	 * @return array<int, array<string, mixed>> The overdue documents, the longest overdue first.
	 *
	 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-the-worklist-shows-what-is-overdue-req-iws-002
	 */
	public function list(): array {
		return $this->repository->findOverdue(
			today: gmdate('Y-m-d'),
			handledWhen: $this->rules->handledWhenByRule()
		);

	}//end list()
}//end class
