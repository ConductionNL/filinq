<?php

/**
 * Intake Signals
 *
 * What the worklist tells a registrar about an arrival: its handling deadline
 * and whether its content arrived before.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCA\Filinq\Service\Intake\ContentHasher;
use OCA\Filinq\Service\Intake\IntakeDeadlines;

/**
 * The deadline and the content hash of an arrival.
 *
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md
 */
final class IntakeSignals {

	/**
	 * Constructor.
	 *
	 * @param IntakeDeadlines $deadlines The handling deadline from the rules.
	 * @param ContentHasher   $hasher    The SHA-256 of the arriving file.
	 *
	 * @return void
	 */
	public function __construct(
		public readonly IntakeDeadlines $deadlines,
		public readonly ContentHasher $hasher,
	) {

	}//end __construct()
}//end class
