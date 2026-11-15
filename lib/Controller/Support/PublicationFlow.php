<?php

/**
 * Publication flow
 *
 * The publication pipeline and the store of publications it writes.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/woo-publicatie-pipeline/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller\Support;

use OCA\Filinq\Kernel\Contracts\PublicationPipelineServiceInterface;
use OCA\Filinq\Kernel\Contracts\PublicationStoreInterface;

/**
 * The publication pipeline and the store of publications it writes.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/woo-publicatie-pipeline/spec.md
 */
final class PublicationFlow {

	/**
	 * Constructor.
	 *
	 * @param PublicationPipelineServiceInterface $pipeline Runs a publication
	 * @param PublicationStoreInterface           $store    Stored publications
	 *
	 * @return void
	 *
	 * @spec openspec/specs/woo-publicatie-pipeline/spec.md
	 */
	public function __construct(
		public readonly PublicationPipelineServiceInterface $pipeline,
		public readonly PublicationStoreInterface $store,
	) {

	}//end __construct()
}//end class
