<?php

/**
 * Policy match rule
 *
 * One {type, value} entry of a publication policy rule.
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
 * The match type and value of one policy match rule.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 */
final class PolicyMatchRule {

	/**
	 * Constructor.
	 *
	 * @param string $type  Match type ('exact', 'normalized', 'bsn', 'kvk')
	 * @param string $value Match value (literal or wildcard)
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $type,
		public readonly string $value,
	) {

	}//end __construct()
}//end class
