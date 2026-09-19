<?php

/**
 * Document Review Service
 *
 * A released document comes back. A beleidsregel with a review interval of
 * twelve months is due twelve months after it was released, and it STAYS due
 * until somebody reviews it: an unread notification changes nothing, because
 * the list is the record of what still needs looking at and the notification is
 * only a tap on the shoulder.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Computes review dates, lists what is due, and records a review.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */
class DocumentReviewService {

	/**
	 * The schema holding the generated documents.
	 *
	 * @var string
	 */
	public const SCHEMA = 'generatedDocument';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The date a document with this interval falls due.
	 *
	 * @param string $interval An ISO 8601 duration, such as P12M.
	 * @param string $from The moment it was released, ISO 8601, or an empty string for now.
	 *
	 * @return string The review date, ISO 8601.
	 *
	 * @throws RuntimeException When the interval is not a duration.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function reviewDate(string $interval, string $from = ''): string {
		$interval = trim($interval);
		if ($interval === '') {
			throw new RuntimeException(message: 'A review interval is a duration such as P12M.');
		}

		try {
			$duration = new DateInterval($interval);
		} catch (Exception $e) {
			throw new RuntimeException(
				message: 'A review interval is a duration such as P12M, not "' . $interval . '".',
				code: 0,
				previous: $e
			);
		}

		try {
			$start = new DateTimeImmutable();
			if ($from !== '') {
				$start = new DateTimeImmutable($from);
			}
		} catch (Exception $e) {
			throw new RuntimeException(
				message: 'The release moment is not a date: "' . $from . '".',
				code: 0,
				previous: $e
			);
		}

		return $start->add($duration)->format(DateTimeInterface::ATOM);

	}//end reviewDate()

	/**
	 * Set the review interval on a document, and the date that follows from it.
	 *
	 * @param array<string, mixed> $document The generated document.
	 * @param string $interval The interval.
	 *
	 * @return array<string, mixed> The document, with its review date.
	 *
	 * @throws RuntimeException When the interval is not a duration.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function schedule(array $document, string $interval): array {
		$document['reviewInterval'] = $interval;
		$document['reviewDate'] = $this->reviewDate(
			interval: $interval,
			from: (string)($document['generatedAt'] ?? '')
		);

		return $document;

	}//end schedule()

	/**
	 * Whether one document is due for review on a given day.
	 *
	 * A document reviewed AFTER its review date is not due; a document reviewed
	 * before it still is. Reading it the other way round would let a review
	 * done last year clear a date that has come round again.
	 *
	 * @param array<string, mixed> $document The document.
	 * @param string $on The day to ask about, ISO 8601, or an empty string for today.
	 *
	 * @return bool True when it is due.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function isDue(array $document, string $on = ''): bool {
		$date = trim((string)($document['reviewDate'] ?? ''));
		if ($date === '') {
			return false;
		}

		try {
			$due = new DateTimeImmutable($date);
			$moment = new DateTimeImmutable();
			if ($on !== '') {
				$moment = new DateTimeImmutable($on);
			}
		} catch (Exception $e) {
			return false;
		}

		if ($due > $moment) {
			return false;
		}

		$reviewed = trim((string)($document['reviewedAt'] ?? ''));
		if ($reviewed === '') {
			return true;
		}

		try {
			return (new DateTimeImmutable($reviewed) < $due);
		} catch (Exception $e) {
			return true;
		}

	}//end isDue()

	/**
	 * Every document due for review.
	 *
	 * @param string $on The day to ask about, or an empty string for today.
	 *
	 * @return array<int, array<string, mixed>> The due documents.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function listDue(string $on = ''): array {
		try {
			// 🔴 SLUGS GO THROUGH `searchObjectsBySlug`, NEVER `searchObjects`.
			// `searchObjects` answers a slug with zero rows and no error, so
			// the review-due screen reported `total: 0` forever and a
			// document past its review date reached nobody.
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: []
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[DocumentReviewService] could not read the generated documents',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);

			return [];
		}

		if (is_array($results) === false) {
			return [];
		}

		$due = [];
		foreach ($results as $result) {
			$document = $this->normalise(row: $result);
			if ($this->isDue(document: $document, on: $on) === true) {
				$due[] = $document;
			}
		}

		return $due;

	}//end listDue()

	/**
	 * Record that somebody reviewed a document, and set the next date.
	 *
	 * @param string $uuid The document.
	 *
	 * @return array<string, mixed> The document, no longer due.
	 *
	 * @throws RuntimeException When there is no such document.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function markReviewed(string $uuid): array {
		try {
			$found = $this->objectResolver->resolve()->find(
				id: $uuid,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA
			);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'Could not read that document: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		if ($found === null) {
			throw new RuntimeException(message: 'There is no generated document with that id.');
		}

		$document = $this->normalise(row: $found);
		$now = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
		$document['reviewedAt'] = $now;

		$interval = trim((string)($document['reviewInterval'] ?? ''));
		if ($interval !== '') {
			$document['reviewDate'] = $this->reviewDate(interval: $interval, from: $now);
		}

		// The uuid goes out of the payload, not into it: OpenRegister takes it
		// as an argument, and a schema with hardValidation refuses a field it
		// never declared. `reviewedBy` is deliberately NOT written for the same
		// reason; who reviewed is in the audit trail, which is the one place
		// that cannot be edited afterwards.
		unset($document['uuid']);

		try {
			$this->objectResolver->resolve()->saveObject(
				object: $document,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA,
				uuid: $uuid
			);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'Could not record the review: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		$document['uuid'] = $uuid;

		return $document;

	}//end markReviewed()

	/**
	 * Read one OpenRegister row into the flat shape this app uses.
	 *
	 * @param mixed $row The row.
	 *
	 * @return array<string, mixed> The document.
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
