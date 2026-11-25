<?php

/**
 * Filinq Office Source Tools
 *
 * The collaborators that check, read, store and convert an office source file.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;


/**
 * The collaborators that check, read, store and convert an office source file.
 *
 * @category Service
 * @package  OCA\Filinq\Service\OfficeTemplate
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
 */
final class OfficeSourceTools {

	/**
	 * Constructor.
	 *
	 * @param OfficeSourceGuard          $guard       Checks the uploaded file.
	 * @param MergeTagReader             $reader      Reads the tags and the text.
	 * @param MergeTagClassifier         $classifier  Checks the tags against the schema.
	 * @param OfficeSourceStore          $store       Stores the source file.
	 * @param LibreOfficeHeadlessBackend $libreOffice Converts an ODT to DOCX.
	 */
	public function __construct(
		public readonly OfficeSourceGuard $guard,
		public readonly MergeTagReader $reader,
		public readonly MergeTagClassifier $classifier,
		public readonly OfficeSourceStore $store,
		public readonly LibreOfficeHeadlessBackend $libreOffice,
	) {
	}//end __construct()
}//end class
