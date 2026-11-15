<?php

/**
 * Filinq Dashboard Registrar
 *
 * The Dashboard component's own registration: the widgets Filinq offers on
 * the Nextcloud dashboard.
 *
 * @category  Dashboard
 * @package   OCA\Filinq\Dashboard
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

namespace OCA\Filinq\Dashboard;

use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * Registers Filinq's dashboard widgets.
 *
 * @category Dashboard
 * @package  OCA\Filinq\Dashboard
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/dashboard/spec.md
 */
class DashboardRegistrar {
	/**
	 * Register the dashboard widgets.
	 *
	 * @param IRegistrationContext $context The registration context.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/dashboard/spec.md
	 */
	public function register(IRegistrationContext $context): void {
		$context->registerDashboardWidget(AnonymizationWidget::class);
		$context->registerDashboardWidget(FileEntitiesWidget::class);

	}//end register()
}//end class
