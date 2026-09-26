<?php

/**
 * Saved View Reader
 *
 * Reads the records a saved view returns. The view belongs to OpenRegister, and
 * so does its query: registers, schemas and filters. This class resolves the
 * view, hands its query to the object service and gives back what came out.
 *
 * 🔴 A MISSING view and an EMPTY view are different answers, and this class
 * keeps them apart: a missing view answers null and an empty one answers an
 * empty list. A caller that only counted rows would read a deleted view as a
 * quiet week, and nobody would go looking.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\App\IAppManager;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Resolves a saved view and reads the records it returns.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */
class SavedViewReader {

	/**
	 * OpenRegister's view service, named as a string.
	 *
	 * Naming it inline rather than importing it keeps this app bootable where
	 * OpenRegister is absent, which is the same reason the object resolver
	 * exists.
	 *
	 * @var string
	 */
	private const VIEW_SERVICE = 'OCA\\OpenRegister\\Service\\ViewService';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param ContainerInterface $container The server container.
	 * @param IAppManager $appManager Installed apps, for the guard.
	 * @param IUserSession $userSession The current session; a view is read as somebody.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly ContainerInterface $container,
		private readonly IAppManager $appManager,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The records one saved view returns.
	 *
	 * @param string $view The view id or slug.
	 * @param string $owner The user the view is read as, or an empty string for the session user.
	 *
	 * @return array<int, mixed>|null The records, or null when the view does not resolve.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function records(string $view, string $owner = ''): ?array {
		if ($view === '') {
			return null;
		}

		if ($owner === '') {
			$user = $this->userSession->getUser();
			if ($user !== null) {
				$owner = $user->getUID();
			}
		}

		$query = $this->queryOf(view: $view, owner: $owner);
		if ($query === null) {
			return null;
		}

		try {
			$results = $this->objectResolver->resolve()->searchObjects(query: $query);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[SavedViewReader] the view resolved but its records could not be read',
				context: ['file' => __FILE__, 'line' => __LINE__, 'view' => $view, 'error' => $e->getMessage()]
			);

			return null;
		}

		if (is_array($results) === false) {
			return [];
		}

		return $results;

	}//end records()

	/**
	 * The query one view holds, or null when the view does not resolve.
	 *
	 * @param string $view The view id or slug.
	 * @param string $owner The user asking.
	 *
	 * @return array<string, mixed>|null The query.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	private function queryOf(string $view, string $owner): ?array {
		if (in_array(
			needle: 'openregister',
			haystack: $this->appManager->getInstalledApps(),
			strict: true
		) === false
		) {
			return null;
		}

		try {
			$viewService = $this->container->get(self::VIEW_SERVICE);
			$found = $viewService->find($view, $owner);
		} catch (Throwable $e) {
			$this->logger->info(
				message: '[SavedViewReader] the view does not resolve',
				context: ['file' => __FILE__, 'line' => __LINE__, 'view' => $view, 'error' => $e->getMessage()]
			);

			return null;
		}

		if ($found === null) {
			return null;
		}

		if (method_exists($found, 'getQuery') === false) {
			return null;
		}

		$query = $found->getQuery();
		if (is_array($query) === false) {
			return null;
		}

		return $query;

	}//end queryOf()
}//end class
