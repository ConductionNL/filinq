<?php

/**
 * Anonymization services
 *
 * The anonymisation runner and the listing of files it can run on.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller\Support;

use OCA\Filinq\Kernel\Contracts\AnonymizationServiceInterface;
use OCA\Filinq\Kernel\Contracts\FileListingServiceInterface;

/**
 * The anonymisation runner and the listing of files it can run on.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 */
final class AnonymizationServices {

	/**
	 * Constructor.
	 *
	 * @param AnonymizationServiceInterface $anonymization Runs anonymisation
	 * @param FileListingServiceInterface   $files         Lists the files
	 *
	 * @return void
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function __construct(
		public readonly AnonymizationServiceInterface $anonymization,
		public readonly FileListingServiceInterface $files,
	) {

	}//end __construct()
}//end class
