<?php

/**
 * Confidentiality Label
 *
 * Immutable value object for a file's resolved confidentiality
 * classification, as read from Nextcloud's public system-tag API by
 * ConfidentialityLabelService.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

/**
 * A file's resolved confidentiality label + normalised level.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md
 */
final class ConfidentialityLabel {
	/**
	 * Constructor for ConfidentialityLabel
	 *
	 * @param string $label The display name of the matched label/tag (e.g. "Confidential")
	 * @param int $level The normalised level on the configured vocabulary scale
	 *
	 * @return void
	 */
	public function __construct(
		private readonly string $label,
		private readonly int $level,
	) {

	}//end __construct()

	/**
	 * Get the display label.
	 *
	 * @return string The display name of the matched label/tag
	 *
	 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md
	 */
	public function getLabel(): string {
		return $this->label;
	}//end getLabel()

	/**
	 * Get the normalised level.
	 *
	 * @return int The normalised level on the configured vocabulary scale
	 *
	 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md
	 */
	public function getLevel(): int {
		return $this->level;
	}//end getLevel()

	/**
	 * Represent as a plain array, e.g. for merging into a result payload.
	 *
	 * @return array{label: string, level: int}
	 *
	 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md
	 */
	public function toArray(): array {
		return [
			'label' => $this->label,
			'level' => $this->level,
		];

	}//end toArray()
}//end class
