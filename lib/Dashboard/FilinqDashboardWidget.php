<?php

/**
 * Filinq Dashboard Widget
 *
 * What every Filinq Nextcloud Dashboard widget shares: the icon, the link to
 * the Filinq dashboard page and the scripts it loads. Each widget names its
 * own id, title and order.
 *
 * @category  Dashboard
 * @package   OCA\Filinq\Dashboard
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/dashboard/spec.md
 * @spec openspec/specs/dashboard/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Dashboard;

use OCA\Filinq\Kernel\AppIdentity;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IWidget;
use OCP\IURLGenerator;
use OCP\Util;

/**
 * Shared base of the Filinq dashboard widgets
 *
 * @category Dashboard
 * @package  OCA\Filinq\Dashboard
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/dashboard/spec.md
 */
abstract class FilinqDashboardWidget implements IWidget, IIconWidget {
	/**
	 * Constructor for a Filinq dashboard widget
	 *
	 * @param IURLGenerator $urlGenerator The URL generator service
	 */
	public function __construct(
		private readonly IURLGenerator $urlGenerator,
	) {

	}//end __construct()

	/**
	 * Returns the CSS icon class for the widget
	 *
	 * @return string
	 *
	 * @spec openspec/specs/dashboard/spec.md
	 */
	public function getIconClass(): string {
		return 'icon-filinq';
	}//end getIconClass()

	/**
	 * Returns the URL to the widget icon
	 *
	 * @return string
	 *
	 * @spec openspec/specs/dashboard/spec.md
	 */
	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(AppIdentity::APP_ID, 'app-dark.svg')
		);

	}//end getIconUrl()

	/**
	 * Returns the URL the widget links to
	 *
	 * @return string|null
	 *
	 * @spec openspec/specs/dashboard/spec.md
	 */
	public function getUrl(): ?string {
		return $this->urlGenerator->linkToRouteAbsolute('filinq.dashboard.page');
	}//end getUrl()

	/**
	 * Loads the widget scripts and styles
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 *
	 * @spec openspec/specs/dashboard/spec.md
	 */
	public function load(): void {
		// Shared vendor chunks emitted by webpack splitChunks (see webpack.config.js).
		Util::addScript(AppIdentity::APP_ID, AppIdentity::APP_ID . '-shared-vendor');
		Util::addScript(AppIdentity::APP_ID, AppIdentity::APP_ID . '-shared-nc-vue');
		Util::addScript(AppIdentity::APP_ID, 'filinq-dashboard');

	}//end load()
}//end class
