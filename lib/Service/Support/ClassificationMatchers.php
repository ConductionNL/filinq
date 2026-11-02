<?php

/**
 * Classification matchers
 *
 * What an inbound document is matched against once its type is known: the correspondent ranker and the dossier matcher.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/inbound-auto-classification/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCA\Filinq\Service\Classification\CorrespondentRanker;
use OCA\Filinq\Service\Classification\DossierMatcher;

/**
 * What an inbound document is matched against once its type is known: the correspondent ranker and the dossier matcher.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/inbound-auto-classification/spec.md
 */
final class ClassificationMatchers {

	/**
	 * Constructor.
	 *
	 * @param CorrespondentRanker $ranker  Ranks likely correspondents
	 * @param DossierMatcher      $matcher Matches the document to a dossier
	 *
	 * @return void
	 *
	 * @spec openspec/specs/inbound-auto-classification/spec.md
	 */
	public function __construct(
		public readonly CorrespondentRanker $ranker,
		public readonly DossierMatcher $matcher,
	) {

	}//end __construct()
}//end class
