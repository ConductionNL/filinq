<?php

/**
 * Anonymiser Backend State Client
 *
 * Delegates backend-state queries to OpenRegister's AnonymisationBackendService.
 * DocuDesk must not query IAppManager or AppAPI directly — all detection is
 * centralised in OpenRegister per ADR-017. If the companion service is not yet
 * deployed (e.g. during a phased rollout), falls back to method='regex' so the
 * admin warning is shown rather than silently suppressed.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-1
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Wraps the OpenRegister AnonymisationBackendService::getState() call.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-1
 */
class AnonymiserBackendStateClient {

	/**
	 * Fully-qualified class name of the OpenRegister backend service.
	 *
	 * @var string
	 */
	private const OR_SERVICE = 'OCA\OpenRegister\Service\AnonymisationBackendService';

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container DI container used for lazy service resolution.
	 * @param LoggerInterface $logger Logger for debug/warning output.
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Retrieve the current anonymisation backend state.
	 *
	 * Delegates to `OCA\OpenRegister\Service\AnonymisationBackendService::getState()`.
	 * Falls back to `['method' => 'regex', 'appApiInstalled' => false]` when the
	 * companion service is not yet deployed, so the admin warning is shown rather
	 * than silently suppressed.
	 *
	 * The returned array contains at least:
	 * - `method`         (string) — one of 'regex', 'openanonymiser', 'presidio', 'llm', or a URL.
	 * - `appApiInstalled` (bool)  — whether the app_api ExApp host is installed on this instance.
	 *
	 * @return array<string, mixed> State array from OpenRegister, or safe defaults.
	 *
	 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-1
	 */
	public function getState(): array {
		try {
			$service = $this->container->get(self::OR_SERVICE);
			// @phpstan-ignore-next-line
			$state = $service->getState();
			return $state;
		} catch (\Throwable $e) {
			$this->logger->debug(
				'AnonymisationBackendService not available; defaulting to regex state',
				['exception' => $e->getMessage()]
			);
			return [
				'method' => 'regex',
				'appApiInstalled' => false,
			];
		}//end try

	}//end getState()
}//end class
