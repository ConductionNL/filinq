<?php

/**
 * Anonymization Dashboard Widget
 *
 * Nextcloud Dashboard widget for quick document anonymization.
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

use OCA\Filinq\AppInfo\Application;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IWidget;
use OCP\IURLGenerator;
use OCP\Util;

/**
 * Dashboard widget for document anonymization
 *
 * @category Dashboard
 * @package  OCA\Filinq\Dashboard
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class AnonymizationWidget implements IWidget, IIconWidget {
	/**
	 * Constructor for AnonymizationWidget
	 *
	 * @param IURLGenerator $urlGenerator The URL generator service
	 */
	public function __construct(
		private readonly IURLGenerator $urlGenerator,
	) {

	}//end __construct()

	/**
	 * Returns the unique widget identifier
	 *
	 * @return string
	 *
	 * @spec openspec/specs/dashboard/spec.md
	 */
	public function getId(): string {
		/* FROZEN at the old app id — see FileEntitiesWidget::getId(). The
		   Dashboard app persists this string in each user's own layout, so
		   renaming it silently removes the widget from every dashboard that
		   already has it. */
		return 'docudesk-anonymization';
	}//end getId()

	/**
	 * Returns the widget display title
	 *
	 * @return string
	 *
	 * @spec openspec/specs/dashboard/spec.md
	 */
	public function getTitle(): string {
		return 'Document Anonymization';
	}//end getTitle()

	/**
	 * Returns the widget display order
	 *
	 * @return int
	 *
	 * @spec openspec/specs/dashboard/spec.md
	 */
	public function getOrder(): int {
		return 20;
	}//end getOrder()

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
			$this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg')
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
		Util::addScript(Application::APP_ID, Application::APP_ID . '-shared-vendor');
		Util::addScript(Application::APP_ID, Application::APP_ID . '-shared-nc-vue');
		Util::addScript(Application::APP_ID, 'filinq-dashboard');

	}//end load()
}//end class
