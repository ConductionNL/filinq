<?php

/**
 * Document Object Service Resolver
 *
 * Small injectable seam that resolves OpenRegister's `ObjectService` from the
 * server container, guarded by an installed-apps check. Shared by the document
 * generation collaborators so the lookup lives in exactly one place.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
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

namespace OCA\Filinq\Service;

use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Resolves OpenRegister's ObjectService, or fails loudly when it is absent.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class DocumentObjectServiceResolver {
	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container Container for dependency injection
	 * @param IAppManager $appManager App manager interface
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly IAppManager $appManager,
	) {

	}//end __construct()

	/**
	 * Get the ObjectService from OpenRegister.
	 *
	 * @return \OCA\OpenRegister\Service\ObjectService The ObjectService instance
	 *
	 * @throws RuntimeException If OpenRegister is not available
	 */
	public function resolve(): \OCA\OpenRegister\Service\ObjectService {
		if (in_array(
			needle: 'openregister',
			haystack: $this->appManager->getInstalledApps(),
			strict: true
		) === true
		) {
			return $this->container->get('OCA\OpenRegister\Service\ObjectService');
		}

		throw new RuntimeException(message: 'OpenRegister service is not available.');
	}//end resolve()
}//end class
