<?php

/**
 * Whether the person who checked a document may also anonymise it.
 *
 * The admin chooses (REQ-SET-12): `woo` holds Woo runs to four eyes, `always`
 * every run, `off` none. This class turns that choice and one run's Woo flag
 * into a yes or no, so the write path asks one question and the setting is
 * read in one place.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-four-eyes-setting/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Redaction;

use OCA\Filinq\Service\SettingsService;

/**
 * Applies the admin's four-eyes choice to one run.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Redaction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/redaction-four-eyes-setting/specs/redaction-output-guarantee/spec.md
 */
class FourEyesPolicy {
	/**
	 * Constructor.
	 *
	 * @param SettingsService $settings Where the admin's choice is kept.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SettingsService $settings,
	) {
	}//end __construct()

	/**
	 * Whether the checker and the anonymiser must differ for this run.
	 *
	 * @param bool $wooRun Whether the run is a Woo run.
	 *
	 * @return bool True when four eyes are required.
	 *
	 * @spec openspec/changes/redaction-four-eyes-setting/tasks.md#task-2
	 */
	public function appliesTo(bool $wooRun): bool {
		return match ($this->settings->getFourEyesMode()) {
			'always' => true,
			'off' => false,
			default => $wooRun,
		};
	}//end appliesTo()
}//end class
