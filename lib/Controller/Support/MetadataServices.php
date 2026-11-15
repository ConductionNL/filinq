<?php

/**
 * Metadata services
 *
 * The metadata reader and the inbound classification a metadata endpoint answers from.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller\Support;

use OCA\Filinq\Kernel\Contracts\InboundClassificationServiceInterface;
use OCA\Filinq\Kernel\Contracts\MetadataServiceInterface;

/**
 * The metadata reader and the inbound classification a metadata endpoint answers from.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */
final class MetadataServices {

	/**
	 * Constructor.
	 *
	 * @param MetadataServiceInterface              $metadata       Metadata extraction and enrichment
	 * @param InboundClassificationServiceInterface $classification Inbound document classification
	 *
	 * @return void
	 *
	 * @spec openspec/specs/metadata-enrichment/spec.md
	 */
	public function __construct(
		public readonly MetadataServiceInterface $metadata,
		public readonly InboundClassificationServiceInterface $classification,
	) {

	}//end __construct()
}//end class
