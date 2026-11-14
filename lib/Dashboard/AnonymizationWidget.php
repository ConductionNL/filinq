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

/**
 * Dashboard widget for document anonymization
 *
 * @category Dashboard
 * @package  OCA\Filinq\Dashboard
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class AnonymizationWidget extends FilinqDashboardWidget {
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
}//end class
