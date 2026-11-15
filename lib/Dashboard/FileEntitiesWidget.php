<?php

/**
 * File Entities Dashboard Widget
 *
 * Nextcloud Dashboard widget showing a table of processed files
 * with entity counts and anonymization status.
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
 * Dashboard widget for file entities overview
 *
 * @category Dashboard
 * @package  OCA\Filinq\Dashboard
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class FileEntitiesWidget extends FilinqDashboardWidget {
	/**
	 * Returns the unique widget identifier
	 *
	 * @return string
	 *
	 * @spec openspec/specs/dashboard/spec.md
	 */
	public function getId(): string {
		/* FROZEN at the old app id. This string is not ours once a user has
		   arranged their dashboard: Nextcloud's Dashboard app persists the
		   chosen widget ids in each user's own layout. Renaming it does not
		   move that layout - the widget simply stops matching and silently
		   disappears from the dashboard of every user who had added it.
		   Same rule as the register slug: an identifier stored in data we do
		   not own does not move with the rename. */
		return 'docudesk-file-entities';
	}//end getId()

	/**
	 * Returns the widget display title
	 *
	 * @return string
	 *
	 * @spec openspec/specs/dashboard/spec.md
	 */
	public function getTitle(): string {
		return 'File Entities';
	}//end getTitle()

	/**
	 * Returns the widget display order
	 *
	 * @return int
	 *
	 * @spec openspec/specs/dashboard/spec.md
	 */
	public function getOrder(): int {
		return 21;
	}//end getOrder()
}//end class
