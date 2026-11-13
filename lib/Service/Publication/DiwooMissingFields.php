<?php

/**
 * The mandatory DiWoo fields a publication record still misses
 *
 * The `filinq.diwoo` calculation backend of `publicationRecord.diwooMissing`:
 * one list that drives the missing-fields column and the "Metadata
 * incomplete" filter (woo-metadata-worklist D1).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Publication
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/woo-metadata-worklist/tasks.md#1-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Publication;

/**
 * Derives the missing list from the record itself, so a field emptied by a
 * later edit shows up again: nothing is stored.
 *
 * @spec openspec/changes/woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-every-publication-record-says-which-diwoo-fields-it-misses-req-wmw-001
 */
class DiwooMissingFields {

	/**
	 * The calculated field's name on a record.
	 *
	 * @var string
	 */
	public const FIELD = 'diwooMissing';

	/**
	 * The mandatory DiWoo fields, in the order the column shows them.
	 *
	 * @var array<int, string>
	 */
	public const MANDATORY = ['wooCategory', 'documentsoort', 'publisher', 'officieleTitel', 'creatiedatum', 'publicatiedatum'];

	/**
	 * The names of the mandatory fields that are empty on a record.
	 *
	 * A value that is not a string or number, or only whitespace, is empty.
	 *
	 * @param array<string, mixed> $record The publication record.
	 *
	 * @return array<int, string> The missing field names; [] when complete.
	 *
	 * @spec openspec/changes/woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-every-publication-record-says-which-diwoo-fields-it-misses-req-wmw-001
	 */
	public function of(array $record): array {
		$missing = [];
		foreach (self::MANDATORY as $field) {
			$value = ($record[$field] ?? null);
			if ((is_string($value) === false && is_int($value) === false && is_float($value) === false) || trim((string) $value) === '') {
				$missing[] = $field;
			}
		}

		return $missing;

	}//end of()

	/**
	 * The record with its calculated missing list.
	 *
	 * @param array<string, mixed> $record The publication record.
	 *
	 * @return array<string, mixed> The record, `diwooMissing` set.
	 *
	 * @spec openspec/changes/woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-every-publication-record-says-which-diwoo-fields-it-misses-req-wmw-001
	 */
	public function annotate(array $record): array {
		$record[self::FIELD] = $this->of(record: $record);

		return $record;

	}//end annotate()
}//end class
