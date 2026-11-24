<?php

/**
 * Filinq Office Template Reading
 *
 * The collaborators that read a template with the caller's access and render or serve its office source.
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

use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Kernel\Contracts\TemplateServiceInterface;


/**
 * The collaborators that read a template with the caller's access and render or serve its office source.
 *
 * @category Service
 * @package  OCA\Filinq\Service\OfficeTemplate
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 */
final class OfficeTemplateReading {

	/**
	 * Constructor.
	 *
	 * @param TemplateServiceInterface $templates Reads templates with the caller's access.
	 * @param OfficeTemplateRenderer   $renderer  Fills and converts office templates.
	 * @param OfficeSourceStore        $store     The stored sources.
	 * @param DataResolverService      $data      Resolves preview data references.
	 */
	public function __construct(
		public readonly TemplateServiceInterface $templates,
		public readonly OfficeTemplateRenderer $renderer,
		public readonly OfficeSourceStore $store,
		public readonly DataResolverService $data,
	) {
	}//end __construct()
}//end class
