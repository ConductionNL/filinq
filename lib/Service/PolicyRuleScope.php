<?php

/**
 * Policy Rule Scope
 *
 * Which organisation's prohibitions and standing consents an anonymisation
 * applies. On a user request it is the user's active organisation; a user
 * whose organisation cannot be resolved gets none (fail-closed, REQ-DDMTH-003).
 * Without a user session (a background job) the rules of every organisation
 * apply, which the system-context seam allows on non-user paths (REQ-DDMTH-002).
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/multi-tenant-hardening/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Resolves the organisation whose policy rules apply to the current request.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/multi-tenant-hardening/spec.md
 */
class PolicyRuleScope {

	/**
	 * OpenRegister's organisation service, resolved lazily from the container.
	 *
	 * @var string
	 */
	private const ORGANISATIONS = 'OCA\OpenRegister\Service\OrganisationService';

	/**
	 * Constructor.
	 *
	 * @param IUserSession       $userSession The person making the request, if any.
	 * @param ContainerInterface $container   Resolves OpenRegister's organisation service.
	 * @param LoggerInterface    $logger      Where an unresolvable organisation is noted.
	 */
	public function __construct(
		private readonly IUserSession $userSession,
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * The organisation whose rules apply.
	 *
	 * @return string|null The organisation UUID on a user request, '' when the
	 *                     user's organisation cannot be resolved (no rules apply),
	 *                     or null without a user session (every organisation's rules apply).
	 *
	 * @spec openspec/changes/multi-tenant-hardening/specs/multi-tenant-hardening/spec.md
	 */
	public function organisation(): ?string {
		if ($this->userSession->getUser() === null) {
			return null;
		}

		try {
			$organisation = $this->container->get(self::ORGANISATIONS)->getActiveOrganisation();
		} catch (Throwable $e) {
			$this->logger->warning('Filinq could not resolve the active organisation for the policy rules', ['exception' => $e->getMessage()]);
			return '';
		}

		if (is_object($organisation) === false || is_callable([$organisation, 'getUuid']) === false) {
			return '';
		}

		return trim((string)$organisation->getUuid());
	}//end organisation()
}//end class
