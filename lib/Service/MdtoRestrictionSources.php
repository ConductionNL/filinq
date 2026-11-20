<?php

/**
 * MDTO Restriction Sources
 *
 * The Filinq state a record's use restrictions are derived from.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-filinq-context-prefills-the-supplement-as-confirmable-proposals-req-ddtmm-003
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The consent records, matching publication prohibitions and anonymised
 * renditions of one record, as read from the Filinq register.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-filinq-context-prefills-the-supplement-as-confirmable-proposals-req-ddtmm-003
 */
final class MdtoRestrictionSources {

	/**
	 * Constructor.
	 *
	 * @param array<int, array<string, mixed>> $consents     publicationConsent objects of the record.
	 * @param array<int, array<string, mixed>> $prohibitions publicationProhibition objects that match the record.
	 * @param array<int, array<string, mixed>> $renditions   anonymizationLink objects whose source is the record's file.
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-filinq-context-prefills-the-supplement-as-confirmable-proposals-req-ddtmm-003
	 */
	public function __construct(
		public readonly array $consents=[],
		public readonly array $prohibitions=[],
		public readonly array $renditions=[],
	) {

	}//end __construct()
}//end class
