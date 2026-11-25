<?php

/**
 * Seal lookup
 *
 * Answers whether a file carries an active waarmerk, so the sanitize action
 * can warn that the clean copy will not carry the seal.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Sanitization
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-21-document-sanitization/tasks.md#3-5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Sanitization;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Reads the `waarmerk` objects of one file.
 */
class SealLookup {

	public const SCHEMA = 'waarmerk';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver OpenRegister's ObjectService.
	 * @param LoggerInterface               $logger         Notes an unreadable seal list.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Whether the file carries a seal that is still active.
	 *
	 * The answer only drives a warning, never a refusal: a seal list that
	 * cannot be read answers false and the action goes on unchanged.
	 *
	 * @param int $fileId The file.
	 *
	 * @return bool True when an active waarmerk names this file.
	 *
	 * @spec openspec/changes/archive/2026-11-21-document-sanitization/specs/document-sanitization/spec.md#requirement-sanitize-then-seal-ordering-is-enforced-on-the-action-surface-req-ddsan-005
	 */
	public function hasActiveSeal(int $fileId): bool {
		if ($fileId <= 0) {
			return false;
		}

		try {
			// Slugs go through searchObjectsBySlug: searchObjects answers slugs with zero rows.
			$rows = $this->objectResolver->slugSearch()->search(
				register: 'filinq',
				schema: self::SCHEMA,
				filters: ['documentFileId' => $fileId]
			);
		} catch (Throwable $e) {
			$this->logger->warning('Seals of a file could not be read', ['fileId' => $fileId, 'exception' => $e->getMessage()]);
			return false;
		}

		if (is_array($rows) === false) {
			return false;
		}

		foreach ($rows as $row) {
			$fields = self::fields(row: $row);
			if ((int) ($fields['documentFileId'] ?? 0) === $fileId && ($fields['status'] ?? '') === 'active') {
				return true;
			}
		}

		return false;

	}//end hasActiveSeal()

	/**
	 * An ObjectEntity or array as its object fields.
	 *
	 * @param mixed $row The row.
	 *
	 * @return array<string, mixed> The fields.
	 */
	private static function fields(mixed $row): array {
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$row = $row->jsonSerialize();
		}

		if (is_array($row) === false) {
			return [];
		}

		if (isset($row['object']) === true && is_array($row['object']) === true) {
			return $row['object'];
		}

		return $row;

	}//end fields()
}//end class
