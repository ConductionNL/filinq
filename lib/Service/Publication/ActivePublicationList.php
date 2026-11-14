<?php

/**
 * The list of documents that must be published actively
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Publication
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Publication;

use OCA\Filinq\Kernel\AppIdentity;
use OCP\IAppConfig;

/**
 * Which publication records fall in a category that must be published
 * actively, whether each is published, and what still blocks it.
 *
 * The categories are configuration: a JSON list of TOOI codes in the app
 * config key {@see self::CONFIG_KEY}. Nothing is listed while it is empty.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Publication
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/specs/woo-publicatie-pipeline/spec.md#requirement-documents-that-must-be-published-actively-are-listed-with-what-they-still-lack-req-scr-008
 */
class ActivePublicationList {

	/**
	 * The app config key holding the categories that must be published actively.
	 */
	public const CONFIG_KEY = 'woo_active_categories';

	/**
	 * No checked redacted copy.
	 */
	public const BLOCK_REDACTED_COPY = 'redacted_copy';

	/**
	 * A consent that is still pending.
	 */
	public const BLOCK_CONSENT = 'consent';

	/**
	 * Woo metadata that is still missing.
	 */
	public const BLOCK_METADATA = 'metadata';

	/**
	 * The publication was withdrawn.
	 */
	public const BLOCK_WITHDRAWN = 'withdrawn';

	/**
	 * The metadata a publication needs before its hand-off.
	 */
	private const REQUIRED_METADATA = ['officieleTitel', 'wooCategory', 'publicatiedatum'];

	/**
	 * Constructor
	 *
	 * @param IAppConfig $config The configured categories
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $config,
	) {

	}//end __construct()

	/**
	 * The TOOI codes of the categories that must be published actively.
	 *
	 * @return list<string> The codes, empty when none are configured or the value is unreadable.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/woo-publicatie-pipeline/spec.md#requirement-documents-that-must-be-published-actively-are-listed-with-what-they-still-lack-req-scr-008
	 */
	public function categories(): array {
		$decoded = json_decode($this->config->getValueString(AppIdentity::APP_ID, self::CONFIG_KEY, '[]'), true);
		if (is_array($decoded) === false) {
			return [];
		}

		$codes = [];
		foreach ($decoded as $code) {
			if (is_string($code) === true && trim($code) !== '') {
				$codes[] = trim($code);
			}
		}

		return array_values(array_unique($codes));

	}//end categories()

	/**
	 * The records in a category that must be published actively.
	 *
	 * @param array<int, array<string, mixed>> $records The publication records to consider
	 *
	 * @return list<array{record: array<string, mixed>, published: bool, blockers: list<string>}> One entry per record, in the order given.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/woo-publicatie-pipeline/spec.md#requirement-documents-that-must-be-published-actively-are-listed-with-what-they-still-lack-req-scr-008
	 */
	public function entries(array $records): array {
		$categories = $this->categories();
		$entries = [];
		foreach ($records as $record) {
			if (in_array((string) ($record['wooCategory'] ?? ''), $categories, true) === false) {
				continue;
			}

			$published = ($record['status'] ?? '') === 'published';
			$blockers = [];
			if ($published === false) {
				$blockers = $this->blockers(record: $record);
			}

			$entries[] = [
				'record' => $record,
				'published' => $published,
				'blockers' => $blockers,
			];
		}

		return $entries;

	}//end entries()

	/**
	 * What keeps an unpublished record from being published.
	 *
	 * @param array<string, mixed> $record The record
	 *
	 * @return list<string> The blocker codes.
	 */
	private function blockers(array $record): array {
		if (in_array($record['status'] ?? '', ['depublication_requested', 'depublished'], true) === true) {
			return [self::BLOCK_WITHDRAWN];
		}

		$blockers = [];
		if (($record['entitiesReviewed'] ?? false) !== true) {
			$blockers[] = self::BLOCK_REDACTED_COPY;
		}

		if (($record['consentClear'] ?? false) !== true) {
			$blockers[] = self::BLOCK_CONSENT;
		}

		if ($this->lacksMetadata(record: $record) === true) {
			$blockers[] = self::BLOCK_METADATA;
		}

		return $blockers;

	}//end blockers()

	/**
	 * Whether a required metadata field of the record is empty.
	 *
	 * @param array<string, mixed> $record The record
	 *
	 * @return bool
	 */
	private function lacksMetadata(array $record): bool {
		foreach (self::REQUIRED_METADATA as $field) {
			if (trim((string) ($record[$field] ?? '')) === '') {
				return true;
			}
		}

		return false;

	}//end lacksMetadata()
}//end class
