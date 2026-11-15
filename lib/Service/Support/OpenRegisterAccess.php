<?php

/**
 * OpenRegister access
 *
 * What a service needs to reach OpenRegister: the app manager that says it is installed and the container that hands out its services.
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

use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;

/**
 * What a service needs to reach OpenRegister: the app manager that says it is installed and the container that hands out its services.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */
final class OpenRegisterAccess {

	/**
	 * Constructor.
	 *
	 * @param IAppManager        $apps      Says whether OpenRegister is installed
	 * @param ContainerInterface $container Hands out OpenRegister services
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function __construct(
		public readonly IAppManager $apps,
		public readonly ContainerInterface $container,
	) {

	}//end __construct()
}//end class
