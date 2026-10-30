<?php

/**
 * Object Event Services
 *
 * The services an OpenRegister object event is handled with.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Kernel\Contracts\MetadataServiceInterface;
use OCA\Filinq\Kernel\Contracts\PolicyRetroactiveServiceInterface;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * The metadata, settings, logging and retroactive-policy services one object event is handled with.
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */
class ObjectEventServices {

	/**
	 * @param MetadataServiceInterface          $metadataService The metadata service
	 * @param SettingsServiceInterface          $settingsService The settings service
	 * @param LoggerInterface                   $logger          The logger
	 * @param PolicyRetroactiveServiceInterface $retroactive     Retroactive policy applicator
	 */
	public function __construct(
		public readonly MetadataServiceInterface $metadataService,
		public readonly SettingsServiceInterface $settingsService,
		public readonly LoggerInterface $logger,
		public readonly PolicyRetroactiveServiceInterface $retroactive,
	) {
	}//end __construct()
}//end class
