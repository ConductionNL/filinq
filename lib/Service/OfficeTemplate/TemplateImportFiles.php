<?php

/**
 * Filinq Template Import Files
 *
 * Where an import's files are kept and read: the source store and the users' Files.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use OCP\Files\IRootFolder;


/**
 * Where an import's files are kept and read: the source store and the users' Files.
 *
 * @category Service
 * @package  OCA\Filinq\Service\OfficeTemplate
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 */
final class TemplateImportFiles {

	/**
	 * Constructor.
	 *
	 * @param OfficeSourceStore $store      Stores and reads the uploaded archive.
	 * @param IRootFolder       $rootFolder Reads a Files folder.
	 */
	public function __construct(
		public readonly OfficeSourceStore $store,
		public readonly IRootFolder $rootFolder,
	) {
	}//end __construct()
}//end class
