<?php

/**
 * Template lookup
 *
 * Finding a template: the template store and the slug resolver.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\TemplateSlugResolver;

/**
 * Finding a template: the template store and the slug resolver.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */
final class TemplateLookup {

	/**
	 * Constructor.
	 *
	 * @param TemplateService      $templates The template store
	 * @param TemplateSlugResolver $slugs     Resolves a template slug to its id
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function __construct(
		public readonly TemplateService $templates,
		public readonly TemplateSlugResolver $slugs,
	) {

	}//end __construct()
}//end class
