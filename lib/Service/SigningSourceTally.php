<?php

/**
 * Signing Source Tally
 *
 * Which apps have raised signing requests on this instance, and how many each.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/one-signing-rail-for-the-fleet/specs/signing-rail-consolidation/spec.md#requirement-an-admin-can-see-which-apps-use-the-rail-req-ddsrc-004
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;

/**
 * Counts the signing requests per `sourceApp`, the provenance a delegated
 * request carries (REQ-DDSRC-004). A request raised inside filinq has no
 * source app and is not counted: the surface is about which other apps use
 * the rail. An instance where nothing has been raised, or where the signing
 * register is not configured, answers an empty list, never an error.
 *
 * @spec openspec/changes/one-signing-rail-for-the-fleet/specs/signing-rail-consolidation/spec.md#requirement-an-admin-can-see-which-apps-use-the-rail-req-ddsrc-004
 */
class SigningSourceTally {

	/**
	 * Requests read per page.
	 *
	 * @var int
	 */
	public const PAGE_SIZE = 500;

	/**
	 * Pages read at most, so one count can never walk an unbounded table.
	 *
	 * @var int
	 */
	public const MAX_PAGES = 200;

	/**
	 * Constructor.
	 *
	 * @param SettingsServiceInterface $settingsService Resolves the object service and the signingRequest binding.
	 *
	 * @spec openspec/changes/one-signing-rail-for-the-fleet/specs/signing-rail-consolidation/spec.md#requirement-an-admin-can-see-which-apps-use-the-rail-req-ddsrc-004
	 */
	public function __construct(
		private readonly SettingsServiceInterface $settingsService,
	) {

	}//end __construct()

	/**
	 * The source apps with their request counts, most requests first, then by name.
	 *
	 * @return array{sources: list<array{app: string, count: int}>, total: int, truncated: bool}
	 *
	 * @spec openspec/changes/one-signing-rail-for-the-fleet/specs/signing-rail-consolidation/spec.md#requirement-an-admin-can-see-which-apps-use-the-rail-req-ddsrc-004
	 */
	public function bySourceApp(): array {
		$objectService = $this->settingsService->getObjectService();
		$binding       = $this->settingsService->resolveSigningRequestBinding();
		if ($objectService === null || $binding === null) {
			return ['sources' => [], 'total' => 0, 'truncated' => false];
		}

		$counts    = [];
		$truncated = true;
		for ($page = 1; $page <= self::MAX_PAGES; $page++) {
			$query   = $objectService->buildSearchQuery(
				requestParams: ['_limit' => self::PAGE_SIZE, '_page' => $page],
				register: $binding['register'],
				schema: $binding['schema']
			);
			$results = ($objectService->searchObjectsPaginated(query: $query, _rbac: false)['results'] ?? []);
			foreach ($results as $result) {
				$app = trim((string)(self::asArray(object: $result)['sourceApp'] ?? ''));
				if ($app !== '') {
					$counts[$app] = (($counts[$app] ?? 0) + 1);
				}
			}

			if (count($results) < self::PAGE_SIZE) {
				$truncated = false;
				break;
			}
		}

		$sources = [];
		foreach ($counts as $app => $count) {
			$sources[] = ['app' => (string)$app, 'count' => $count];
		}

		usort(
			$sources,
			static fn (array $left, array $right): int => [$right['count'], $left['app']] <=> [$left['count'], $right['app']]
		);

		return ['sources' => $sources, 'total' => array_sum($counts), 'truncated' => $truncated];

	}//end bySourceApp()

	/**
	 * One search result as an array.
	 *
	 * @param mixed $object An entity or an array.
	 *
	 * @return array<string, mixed>
	 */
	private static function asArray(mixed $object): array {
		if (is_object($object) === true && method_exists($object, 'jsonSerialize') === true) {
			return (array)$object->jsonSerialize();
		}

		return (array)$object;

	}//end asArray()
}//end class
