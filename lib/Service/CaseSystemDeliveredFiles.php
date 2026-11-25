<?php

/**
 * Case System Delivered Files
 *
 * Which Nextcloud files have reached the case system, for the My Documents badge.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-25-generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Kernel\Contracts\CaseSystemDeliveredFilesInterface;

/**
 * Reads the written-back case-system deliveries of a list of files in one query.
 *
 * @spec openspec/changes/archive/2026-11-25-generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
 */
class CaseSystemDeliveredFiles implements CaseSystemDeliveredFilesInterface {

	/**
	 * The delivery state once the case system holds the document.
	 */
	public const WRITTEN_BACK = 'written_back';

	/**
	 * The most deliveries one file is expected to have: one per destination.
	 */
	private const DELIVERIES_PER_FILE = 5;

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objects Resolves OpenRegister's object service.
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objects,
	) {
	}//end __construct()

	/**
	 * Which of these files are in the case system: their delivery was written back.
	 *
	 * One query for the whole list, so a page of documents costs one lookup.
	 * The answer is checked against the asked ids, whatever the query returns.
	 *
	 * @param array<int, int> $fileIds The Nextcloud file ids.
	 *
	 * @return array<int, int> The file ids among them with a written-back delivery, in the order asked.
	 *
	 * @spec openspec/changes/archive/2026-11-25-generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
	 */
	public function deliveredFileIds(array $fileIds): array {
		$asked = array_values(array_unique(array_map('intval', $fileIds)));
		if ($asked === []) {
			return [];
		}

		$found = $this->objects->resolve()->findAll(
			config: [
				'filters' => [
					'register'       => CaseSystemDeliveryService::REGISTER,
					'schema'         => CaseSystemDeliveryService::DELIVERY_SCHEMA,
					'deliveryStatus' => self::WRITTEN_BACK,
					'fileId'         => $asked,
				],
				'limit'   => (count($asked) * self::DELIVERIES_PER_FILE),
			],
			_rbac: false
		);

		$delivered = [];
		foreach ($found as $entry) {
			$row = $entry;
			if (is_object($entry) === true && method_exists($entry, 'jsonSerialize') === true) {
				$row = $entry->jsonSerialize();
			}

			if (is_array($row) === true
				&& ($row['deliveryStatus'] ?? null) === self::WRITTEN_BACK
				&& is_numeric($row['fileId'] ?? null) === true
			) {
				$delivered[(int)$row['fileId']] = true;
			}
		}

		return array_values(array_filter($asked, static fn (int $id): bool => isset($delivered[$id]) === true));

	}//end deliveredFileIds()
}//end class
