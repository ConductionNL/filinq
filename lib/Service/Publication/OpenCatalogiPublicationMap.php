<?php

/**
 * OpenCatalogi publication map
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

/**
 * How a Filinq publication becomes an OpenCatalogi `publication` object.
 *
 * The one place that names OpenCatalogi's fields. They are read from
 * OpenCatalogi's `lib/Settings/publication_register.json` on development
 * (schema `publication` 0.0.4, 29 Sep 2026), and a unit test pins them
 * against a copy of that schema, so a rename there fails the suite here.
 *
 * OpenCatalogi's publication has no fields for the Woo information
 * category, document type or publisher yet; those stay on Filinq's record
 * and are not written into another app's object under names it does not
 * declare.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Publication
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/woo-publicatie-pipeline/spec.md
 */
class OpenCatalogiPublicationMap {

	/**
	 * OpenCatalogi's register slug.
	 */
	public const REGISTER = 'publication';

	/**
	 * OpenCatalogi's schema slug.
	 */
	public const SCHEMA = 'publication';

	/**
	 * The OpenCatalogi fields Filinq writes.
	 */
	public const FIELDS = [
		'title',
		'summary',
		'publicationDate',
		'depublicationDate',
		'retentionExpiresAt',
		'retentionNote',
	];

	/**
	 * The publication object for a record.
	 *
	 * @param array<string, mixed> $record The Filinq publication record
	 *
	 * @return array<string, mixed> The fields to write, only those in FIELDS.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.3
	 */
	public function toPublication(array $record): array {
		$publication = [
			'title' => (string) ($record['officieleTitel'] ?? ''),
			'summary' => (string) ($record['documentsoort'] ?? ''),
		];

		$date = (string) ($record['publicatiedatum'] ?? '');
		if ($date !== '') {
			$publication['publicationDate'] = $date . 'T00:00:00+00:00';
		}

		return $publication;

	}//end toPublication()

	/**
	 * The fields that withdraw a publication now.
	 *
	 * @param string $now An ISO date-time
	 *
	 * @return array<string, string> depublicationDate.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.4
	 */
	public function withdrawal(string $now): array {
		return ['depublicationDate' => $now];

	}//end withdrawal()

	/**
	 * The fields that carry a destruction date, with the note OpenCatalogi
	 * requires for a manual retention override (RET-003).
	 *
	 * @param string $date   The destruction date, Y-m-d
	 * @param string $source Where the date came from
	 *
	 * @return array<string, string> retentionExpiresAt and retentionNote.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.4
	 */
	public function destruction(string $date, string $source): array {
		return [
			'retentionExpiresAt' => $date . 'T00:00:00+00:00',
			'retentionNote' => 'Vernietigingsdatum uit het bronsysteem (' . $source . '), Archiefwet 1995',
		];

	}//end destruction()
}//end class
