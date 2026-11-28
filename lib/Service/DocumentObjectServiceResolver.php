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

use OCA\OpenRegister\Contract\ObjectServiceInterface;
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
		return $this->objectService();
	}//end resolve()

	/**
	 * OpenRegister's ObjectService from the container, once OpenRegister is installed.
	 *
	 * @return mixed The ObjectService.
	 *
	 * @throws RuntimeException If OpenRegister is not available
	 */
	private function objectService(): mixed {
		if (in_array(
			needle: 'openregister',
			haystack: $this->appManager->getInstalledApps(),
			strict: true
		) === true
		) {
			return $this->container->get('OCA\OpenRegister\Service\ObjectService');
		}

		throw new RuntimeException(message: 'OpenRegister service is not available.');
	}//end objectService()

	/**
	 * The same ObjectService, typed as OpenRegister's published contract (ADR-084),
	 * for callers that hand it on to something that takes the contract, such as
	 * SystemContextSeam.
	 *
	 * @return ObjectServiceInterface The ObjectService.
	 *
	 * @throws RuntimeException If OpenRegister is not available
	 *
	 * @spec openspec/specs/openregister-bridge/spec.md
	 */
	public function contract(): ObjectServiceInterface {
		return $this->objectService();
	}//end contract()

	/**
	 * A slug search on the resolved ObjectService, in the form the installed OpenRegister declares.
	 *
	 * Final, so a test double of this resolver still runs it over its own resolve().
	 *
	 * @param bool|null $_rbac         Whether OpenRegister applies role-based access; its default when null.
	 * @param bool|null $_multitenancy Whether OpenRegister scopes to the active organisation; its default when null.
	 *
	 * @return SlugSearch The search.
	 *
	 * @throws RuntimeException If OpenRegister is not available
	 *
	 * @SuppressWarnings(PHPMD.CamelCaseParameterName) The names mirror OpenRegister's own parameters.
	 * @SuppressWarnings(PHPMD.CamelCaseVariableName)  The names mirror OpenRegister's own parameters.
	 *
	 * @spec openspec/specs/openregister-bridge/spec.md
	 */
	final public function slugSearch(?bool $_rbac=null, ?bool $_multitenancy=null): SlugSearch {
		return new SlugSearch(objects: $this->resolve(), _rbac: $_rbac, _multitenancy: $_multitenancy);
	}//end slugSearch()
}//end class
