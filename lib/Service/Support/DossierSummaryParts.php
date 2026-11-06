<?php

/**
 * Dossier summary parts
 *
 * The four collaborators a dossier summary reads through: the object
 * repository, the legal basis labels, the placeholder ranking and the
 * entity collection.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCA\Filinq\Service\BaseLabelResolver;
use OCA\Filinq\Service\DossierEntityCollector;
use OCA\Filinq\Service\DossierObjectRepository;
use OCA\Filinq\Service\DossierPlaceholderRanker;

/**
 * The four collaborators a dossier summary reads through: the object
 * repository, the legal basis labels, the placeholder ranking and the
 * entity collection.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md
 */
final class DossierSummaryParts {

	/**
	 * Constructor.
	 *
	 * @param DossierObjectRepository  $repository    Reads the OpenRegister objects
	 * @param BaseLabelResolver        $labelResolver Resolves legal basis labels
	 * @param DossierPlaceholderRanker $ranker        Ranks the dossier placeholders
	 * @param DossierEntityCollector   $collector     Collects the anonymised entities
	 *
	 * @return void
	 *
	 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md
	 */
	public function __construct(
		public readonly DossierObjectRepository $repository,
		public readonly BaseLabelResolver $labelResolver,
		public readonly DossierPlaceholderRanker $ranker,
		public readonly DossierEntityCollector $collector,
	) {

	}//end __construct()
}//end class
