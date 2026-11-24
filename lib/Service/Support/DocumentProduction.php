<?php

/**
 * Document production
 *
 * The render pipeline a generation runs through and the store of its jobs.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCA\Filinq\Service\DocumentJobStore;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\Vendor\VendorRenderService;

/**
 * The render pipeline a generation runs through and the store of its jobs.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 */
final class DocumentProduction {

	/**
	 * Constructor.
	 *
	 * @param DocumentRenderPipeline $pipeline Renders the merged template to the output formats
	 * @param DocumentJobStore       $jobs     Keeps the asynchronous generation jobs
	 * @param VendorRenderService|null $vendor Hands a `vendor:<slug>` render to integriq
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
	 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-a-vendor-render-goes-to-integriq-and-never-falls-back-req-tev-002
	 */
	public function __construct(
		public readonly DocumentRenderPipeline $pipeline,
		public readonly DocumentJobStore $jobs,
		public readonly ?VendorRenderService $vendor = null,
	) {

	}//end __construct()
}//end class
