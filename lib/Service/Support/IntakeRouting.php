<?php

/**
 * Intake routing
 *
 * What decides where an intake document goes: the default rules, the routing and the party suggestions.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCA\Filinq\Service\IntakeDefaultRuleService;
use OCA\Filinq\Service\IntakeRoutingService;
use OCA\Filinq\Service\PartySuggestionService;

/**
 * What decides where an intake document goes: the default rules, the routing and the party suggestions.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */
final class IntakeRouting {

	/**
	 * Constructor.
	 *
	 * @param IntakeDefaultRuleService $defaults Applies the default intake rules
	 * @param IntakeRoutingService     $routing  Routes a document to its dossier
	 * @param PartySuggestionService   $parties  Suggests the parties of a document
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function __construct(
		public readonly IntakeDefaultRuleService $defaults,
		public readonly IntakeRoutingService $routing,
		public readonly PartySuggestionService $parties,
	) {

	}//end __construct()
}//end class
