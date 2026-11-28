<?php

/**
 * Folder enumeration sources
 *
 * What a folder batch lists its source files with: the output layout, the confidentiality labels and the app configuration.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCA\Filinq\Service\ConfidentialityLabelService;
use OCA\Filinq\Service\Conversion\OutputLayoutResolver;
use OCA\Filinq\Service\Review\ReviewRenditionRepository;
use OCP\IAppConfig;

/**
 * What a folder batch lists its source files with: the output layout, the confidentiality labels and the app configuration.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md
 */
final class FolderEnumerationSources {

	/**
	 * Constructor.
	 *
	 * @param OutputLayoutResolver        $layout               Recognises legacy anonymised outputs
	 * @param ConfidentialityLabelService $confidentialityLabel Reads the confidentiality labels
	 * @param IAppConfig                  $appConfig            Holds the prioritised analysis flag
	 * @param ReviewRenditionRepository|null $renditions        Recognises the review renditions filinq wrote next to their sources
	 *
	 * @return void
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md
	 */
	public function __construct(
		public readonly OutputLayoutResolver $layout,
		public readonly ConfidentialityLabelService $confidentialityLabel,
		public readonly IAppConfig $appConfig,
		public readonly ?ReviewRenditionRepository $renditions = null,
	) {

	}//end __construct()
}//end class
