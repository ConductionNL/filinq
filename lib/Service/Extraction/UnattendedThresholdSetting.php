<?php

/**
 * Unattended Threshold Setting
 *
 * The save-time check of `extraction_unattended_threshold` (REQ-ERP-005): an
 * admin may store a number from 0.6 to 1.0 and nothing else. The read side
 * (UnattendedVerdict::threshold()) falls back to 0.9 on a bad value; this
 * refuses the bad value before it is stored, so the page never shows one.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Extraction
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-extraction-receipt-photo-and-invoice-agent/tasks.md#task-3.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Extraction;

use RuntimeException;

/**
 * Refuses an unattended threshold outside its bounds.
 *
 * @spec openspec/changes/archive/2026-11-25-extraction-receipt-photo-and-invoice-agent/tasks.md#task-3.3
 */
class UnattendedThresholdSetting {

	/**
	 * The refusal.
	 *
	 * @var string
	 */
	public const MESSAGE = 'Choose a value between 0.6 and 1.0.';

	/**
	 * Refuse the settings when they carry a threshold outside 0.6 to 1.0.
	 *
	 * @param array<string, mixed> $data The settings being saved.
	 *
	 * @return void
	 *
	 * @throws RuntimeException (400) When the threshold is not a number from 0.6 to 1.0.
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) UnattendedVerdict::isValidThreshold() is the one reading of a valid threshold.
	 *
	 * @spec openspec/changes/archive/2026-11-25-extraction-receipt-photo-and-invoice-agent/tasks.md#task-3.3
	 */
	public function assertValid(array $data): void {
		if (array_key_exists(UnattendedVerdict::SETTING_KEY, $data) === false
			|| UnattendedVerdict::isValidThreshold(value: $data[UnattendedVerdict::SETTING_KEY]) === true
		) {
			return;
		}

		throw new RuntimeException(message: self::MESSAGE, code: 400);
	}//end assertValid()
}//end class
