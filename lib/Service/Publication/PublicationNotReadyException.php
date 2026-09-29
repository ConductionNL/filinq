<?php

/**
 * Publication not ready
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Publication
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Publication;

use RuntimeException;

/**
 * A hand-off refused because a check failed at that moment.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Publication
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/woo-publicatie-pipeline/specs/woo-publicatie-pipeline/spec.md
 */
class PublicationNotReadyException extends RuntimeException {

	/**
	 * Constructor
	 *
	 * @param list<string> $reasons What blocks the hand-off
	 *
	 * @return void
	 */
	public function __construct(
		private readonly array $reasons,
	) {
		parent::__construct('The publication is not ready to hand off', 409);

	}//end __construct()

	/**
	 * What blocks the hand-off.
	 *
	 * @return list<string> The reasons.
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.3
	 */
	public function getReasons(): array {
		return $this->reasons;

	}//end getReasons()
}//end class
