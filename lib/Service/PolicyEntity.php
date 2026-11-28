<?php

/**
 * Policy entity
 *
 * A detected entity as the publication policy matcher reads it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The text, type, identifiers and normalised text of one detected entity.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 */
final class PolicyEntity {

	/**
	 * Constructor.
	 *
	 * @param string               $text        Entity literal text
	 * @param string               $type        'PERSON', 'ORGANIZATION', 'OTHER', or '' when not known
	 * @param array<string, mixed> $identifiers Structured identifiers (BSN, KvK)
	 * @param string               $normalised  Lower-cased and accent-stripped text
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $text,
		public readonly string $type,
		public readonly array $identifiers,
		public readonly string $normalised,
	) {

	}//end __construct()
}//end class
