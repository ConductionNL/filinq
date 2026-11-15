<?php

/**
 * Signer Step Task
 *
 * Which sequence task a signer decided on, and the signing request it belongs to.
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
 * The sequence, the task, its 1-based position and the signing-request UUID.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */
final class SignerStepTask {

	/**
	 * Constructor.
	 *
	 * @param string $sequenceUuid UUID of the OR task sequence.
	 * @param string $taskUuid     UUID of the task.
	 * @param int    $position     Ordinal of the position (1-based).
	 * @param string $objectUuid   Signing-request UUID.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function __construct(
		public readonly string $sequenceUuid,
		public readonly string $taskUuid,
		public readonly int $position,
		public readonly string $objectUuid,
	) {

	}//end __construct()
}//end class
