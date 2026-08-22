<?php

/**
 * Admin section for Filinq settings
 *
 * @category  Sections
 * @package   OCA\Filinq\Sections
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/admin-settings/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Sections;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

/**
 * Admin section for Filinq settings
 *
 * This class defines the admin section where Filinq settings will appear
 * in the Nextcloud admin panel.
 *
 * @category Sections
 * @package  OCA\Filinq\Sections
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://github.com/conductionnl/filinq
 */
class FilinqAdmin implements IIconSection {

	/**
	 * L10N service for translations
	 *
	 * @var IL10N
	 */
	private IL10N $l10n;

	/**
	 * URL generator for creating URLs
	 *
	 * @var IURLGenerator
	 */
	private IURLGenerator $urlGenerator;

	/**
	 * Constructor for FilinqAdmin section
	 *
	 * @param IL10N $l10n L10N service for translations
	 * @param IURLGenerator $urlGenerator URL generator service
	 *
	 * @return void
	 */
	public function __construct(IL10N $l10n, IURLGenerator $urlGenerator) {
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;

	}//end __construct()

	/**
	 * Get the icon for the admin section
	 *
	 * @return string URL to the section icon
	 *
	 * @psalm-return   string
	 * @phpstan-return string
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function getIcon(): string {
		return $this->urlGenerator->imagePath(appName: 'filinq', file: 'app-dark.svg');
	}//end getIcon()

	/**
	 * Get the ID of the admin section
	 *
	 * @return string The section ID
	 *
	 * @psalm-return   string
	 * @phpstan-return string
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function getID(): string {
		return 'filinq';
	}//end getID()

	/**
	 * Get the name of the admin section
	 *
	 * @return string The translated section name
	 *
	 * @psalm-return   string
	 * @phpstan-return string
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function getName(): string {
		return $this->l10n->t('Filinq');
	}//end getName()

	/**
	 * Get the priority of the admin section
	 *
	 * @return int The section priority (0-100)
	 *
	 * @psalm-return   int
	 * @phpstan-return int
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function getPriority(): int {
		return 97;
	}//end getPriority()
}//end class
