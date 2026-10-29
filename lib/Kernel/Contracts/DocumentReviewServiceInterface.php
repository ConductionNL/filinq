<?php

/**
 * Contract of DocumentReviewService
 *
 * The public surface of the DocumentReviewService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
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

namespace OCA\Filinq\Kernel\Contracts;

use RuntimeException;

/**
 * Contract of DocumentReviewService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface DocumentReviewServiceInterface {
	/**
	 * The schema holding the generated documents.
	 *
	 * @var string
	 */
	public const SCHEMA = 'generatedDocument';

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
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function reviewDate(string $interval, string $from = ''): string;

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
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function schedule(array $document, string $interval): array;

	/**
	 * Whether one document is due for review on a given day.
	 *
	 * A document reviewed AFTER its review date is not due; a document reviewed
	 * before it still is. Reading it the other way round would let a review
	 * done last year clear a date that has come round again.
	 *
	 * @param array<string, mixed> $document The document.
	 * @param string $day The day to ask about, ISO 8601, or an empty string for today.
	 *
	 * @return bool True when it is due.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function isDue(array $document, string $day = ''): bool;

	/**
	 * Every document due for review.
	 *
	 * @param string $day The day to ask about, or an empty string for today.
	 *
	 * @return array<int, array<string, mixed>> The due documents.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function listDue(string $day = ''): array;

	/**
	 * Record that somebody reviewed a document, and set the next date.
	 *
	 * @param string $uuid The document.
	 *
	 * @return array<string, mixed> The document, no longer due.
	 *
	 * @throws RuntimeException When there is no such document.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function markReviewed(string $uuid): array;
}//end interface
