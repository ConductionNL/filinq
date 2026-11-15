<?php

/**
 * Suggestion signals
 *
 * The three signals a ledger account suggestion is ranked from: the supplier identity, the booking history and the category keywords.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCA\Filinq\Service\Suggestion\CategoryKeywordMapper;
use OCA\Filinq\Service\Suggestion\HistoryRanker;
use OCA\Filinq\Service\Suggestion\SupplierIdentityResolver;

/**
 * The three signals a ledger account suggestion is ranked from: the supplier identity, the booking history and the category keywords.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */
final class SuggestionSignals {

	/**
	 * Constructor.
	 *
	 * @param SupplierIdentityResolver $identity Resolves the supplier
	 * @param HistoryRanker            $history  Ranks accounts by earlier bookings
	 * @param CategoryKeywordMapper    $keywords Maps keywords to categories
	 *
	 * @return void
	 *
	 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
	 */
	public function __construct(
		public readonly SupplierIdentityResolver $identity,
		public readonly HistoryRanker $history,
		public readonly CategoryKeywordMapper $keywords,
	) {

	}//end __construct()
}//end class
