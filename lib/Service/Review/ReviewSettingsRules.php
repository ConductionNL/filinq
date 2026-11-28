<?php

/**
 * The save rules of the review workbench's admin settings.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Review
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Review;

use RuntimeException;

/**
 * Refuses a settings save with an unknown gate mode or a certain-from the floor does not allow.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Review
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#requirement-the-screen-tells-a-certain-finding-from-an-uncertain-one-by-organisation-settings-req-ddarw-013
 */
class ReviewSettingsRules {

	/**
	 * Constructor.
	 *
	 * @param CertaintyThresholds|null $thresholds The floor and certain-from; null checks the gate mode only.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ?CertaintyThresholds $thresholds = null,
	) {

	}//end __construct()

	/**
	 * Refuse the save when a review setting in it is not allowed; settings without them pass.
	 *
	 * Checked before anything is written, so a refused save changes nothing.
	 *
	 * @param array<string, mixed> $data The settings data to update.
	 *
	 * @return void
	 *
	 * @throws RuntimeException With code 400 naming what is wrong.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
	 */
	public function assertValid(array $data): void {
		(new CheckedGateModeRule())->assertValid(data: $data);
		$this->thresholds?->assertValid(data: $data);

	}//end assertValid()
}//end class
