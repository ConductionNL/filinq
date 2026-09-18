<?php

/**
 * The record that a person looked at this detection run.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Redaction;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Reads and writes `redactionReviewMark` rows in the `filinq` register.
 */
class RedactionReviewMarkRepository {

	/**
	 * The schema these rows live in.
	 *
	 * @var string
	 */
	public const SCHEMA = 'redactionReviewMark';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param LoggerInterface               $logger         Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The mark covering one document, or null when there is none.
	 *
	 * 🔴 A READ THAT FAILS RETURNS NULL, AND NULL REFUSES. The gate treats an
	 * absent mark as "nobody has checked this", so an OpenRegister that is down
	 * stops output rather than waving it through. That is the opposite of the
	 * usual convenience and it is the only safe direction here.
	 *
	 * @param string $document The document the mark is about.
	 *
	 * @return array<string, mixed>|null The mark.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function findFor(string $document): ?array {
		if (trim($document) === '') {
			return null;
		}

		try {
			$results = $this->objectResolver->resolve()->searchObjects(
				query: [
					'@self' => [
						'register' => IntakeRepository::REGISTER,
						'schema' => self::SCHEMA,
					],
					'document' => $document,
				]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[RedactionReviewMarkRepository] could not read the review mark, so output is refused',
				context: [
					'file' => __FILE__,
					'line' => __LINE__,
					'document' => $document,
					'error' => $e->getMessage(),
				]
			);

			return null;
		}

		if (is_array($results) === false || $results === []) {
			return null;
		}

		return $this->latestOf(rows: $results);

	}//end findFor()

	/**
	 * Write one mark.
	 *
	 * @param array<string, mixed> $mark The mark.
	 *
	 * @return array<string, mixed> The stored mark.
	 *
	 * @throws RuntimeException When the write fails.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function save(array $mark): array {
		try {
			$stored = $this->objectResolver->resolve()->saveObject(
				object: $mark,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA
			);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'The check could not be recorded, so the document stays unapproved: '.$e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		return $this->normalise(row: $stored);

	}//end save()

	/**
	 * The most recently checked row of several.
	 *
	 * @param array<int, mixed> $rows The rows.
	 *
	 * @return array<string, mixed>|null The latest.
	 *
	 * @spec exclude Selection helper behind findFor().
	 */
	private function latestOf(array $rows): ?array {
		$latest = null;
		$latestAt = '';
		foreach ($rows as $row) {
			$fields = $this->normalise(row: $row);
			if ($fields === []) {
				continue;
			}

			$checkedAt = (string)($fields['checkedAt'] ?? '');
			if ($latest === null || $checkedAt > $latestAt) {
				$latest = $fields;
				$latestAt = $checkedAt;
			}
		}

		return $latest;

	}//end latestOf()

	/**
	 * Read one OpenRegister row into the flat shape this app uses.
	 *
	 * @param mixed $row The row.
	 *
	 * @return array<string, mixed> The mark.
	 *
	 * @spec exclude Shape adapter over an OpenRegister response.
	 */
	private function normalise(mixed $row): array {
		$data = $row;
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$data = $row->jsonSerialize();
		}

		if (is_array($data) === false) {
			return [];
		}

		$fields = $data;
		if (isset($data['object']) === true && is_array($data['object']) === true) {
			$fields = $data['object'];
		}

		$fields['uuid'] = (string)($fields['uuid'] ?? ($data['uuid'] ?? ($data['@self']['id'] ?? ($data['id'] ?? ''))));

		return $fields;

	}//end normalise()
}//end class
