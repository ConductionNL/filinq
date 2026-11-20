<?php

/**
 * Settings backends
 *
 * Where the admin settings come from: the settings service and the anonymiser backend state.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
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

namespace OCA\Filinq\Controller\Support;

use OCA\Filinq\Kernel\Contracts\AnonymiserBackendStateClientInterface;
use OCA\Filinq\BackgroundJob\IntegrityScanJob;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use OCA\Filinq\Service\IntegrityScanService;

/**
 * Where the admin settings come from: the settings service and the anonymiser backend state.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/admin-settings/spec.md
 */
final class SettingsBackends {

	/**
	 * Constructor.
	 *
	 * @param SettingsServiceInterface              $settings   The app settings
	 * @param AnonymiserBackendStateClientInterface $anonymiser The anonymiser backend state
	 * @param IntegrityScanService                  $integrityScan    The whole-collection integrity scan
	 * @param IntegrityScanJob                      $integrityNotices Notifies the administrators of a run's findings
	 *
	 * @return void
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function __construct(
		public readonly SettingsServiceInterface $settings,
		public readonly AnonymiserBackendStateClientInterface $anonymiser,
		public readonly IntegrityScanService $integrityScan,
		public readonly IntegrityScanJob $integrityNotices,
	) {

	}//end __construct()
}//end class
