<?php

/**
 * Register setup
 *
 * Finding the app's registers and setting them up: register discovery and the settings initializer.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/admin-settings/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCA\Filinq\Service\RegisterDiscoveryService;
use OCA\Filinq\Service\SettingsInitializer;

/**
 * Finding the app's registers and setting them up: register discovery and the settings initializer.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/admin-settings/spec.md
 */
final class RegisterSetup {

	/**
	 * Constructor.
	 *
	 * @param RegisterDiscoveryService $discovery   Finds the registers
	 * @param SettingsInitializer      $initializer Sets up the initial settings
	 *
	 * @return void
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function __construct(
		public readonly RegisterDiscoveryService $discovery,
		public readonly SettingsInitializer $initializer,
	) {

	}//end __construct()
}//end class
