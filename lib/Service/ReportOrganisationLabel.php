<?php

/**
 * Report Organisation Label
 *
 * Names the organisation a generated report covers, so every report states
 * its scope by name and UUID.
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

use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Resolves the name of an organisation a report covers, from the caller's own organisations.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/multi-tenant-hardening/spec.md#requirement-organisation-scoped-dashboards-and-reporting-req-ddmth-005
 */
class ReportOrganisationLabel {

	/**
	 * OpenRegister's organisation service, resolved by name so Filinq loads without it.
	 *
	 * @var string
	 */
	private const ORGANISATIONS = 'OCA\OpenRegister\Service\OrganisationService';

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container Resolves OpenRegister's organisation service.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ContainerInterface $container,
	) {
	}//end __construct()

	/**
	 * The UUID and name of the organisation an object is stamped with.
	 *
	 * The name comes from the caller's own organisations only, so a report never
	 * names an organisation the caller does not belong to: the UUID still stands
	 * and the name is empty. An unstamped object, or an organisation service that
	 * cannot be read, gives empty values rather than a guess.
	 *
	 * @param string|null $uuid The organisation stamp of the reported object.
	 *
	 * @return array{uuid: string, name: string} The label.
	 *
	 * @spec openspec/changes/multi-tenant-hardening/tasks.md#3-backend-per-organisation-behaviour
	 */
	public function describe(?string $uuid): array {
		$uuid = trim((string)$uuid);
		if ($uuid === '') {
			return ['uuid' => '', 'name' => ''];
		}

		return ['uuid' => $uuid, 'name' => $this->nameOf(uuid: $uuid)];
	}//end describe()

	/**
	 * The name of one of the caller's organisations.
	 *
	 * @param string $uuid The organisation UUID.
	 *
	 * @return string The name, or '' when the caller has no such organisation.
	 */
	private function nameOf(string $uuid): string {
		try {
			$organisations = $this->container->get(self::ORGANISATIONS)->getUserOrganisations();
		} catch (Throwable) {
			return '';
		}

		foreach ((array)$organisations as $organisation) {
			if (is_object($organisation) === false
				|| is_callable([$organisation, 'getUuid']) === false
				|| is_callable([$organisation, 'getName']) === false
			) {
				continue;
			}

			if ((string)$organisation->getUuid() === $uuid) {
				return (string)$organisation->getName();
			}
		}

		return '';
	}//end nameOf()
}//end class
