<?php

/**
 * Validation Fallback Context
 *
 * The services and log context one validation-verdict fallback run uses.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Kernel\Contracts\DocumentValidationServiceInterface;
use OCA\Filinq\Kernel\Contracts\MetadataServiceInterface;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;

/**
 * What a validation run validates with, writes through, reads files from and logs to.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */
final class ValidationFallbackContext {

	/**
	 * Constructor.
	 *
	 * @param DocumentValidationServiceInterface $validationService The validation service.
	 * @param MetadataServiceInterface           $metadataService   The metadata save path.
	 * @param IRootFolder                        $rootFolder        Root folder for file access.
	 * @param LoggerInterface                    $logger            Logger.
	 * @param string                             $logContext        Log context.
	 *
	 * @return void
	 */
	public function __construct(
		public readonly DocumentValidationServiceInterface $validationService,
		public readonly MetadataServiceInterface $metadataService,
		public readonly IRootFolder $rootFolder,
		public readonly LoggerInterface $logger,
		public readonly string $logContext,
	) {

	}//end __construct()
}//end class
