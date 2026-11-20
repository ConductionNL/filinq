<?php

/**
 * Where scope decisions and their outcome are kept.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Review
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-a-page-a-page-range-or-a-whole-document-is-redacted-in-one-action-req-ddimr-009
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Review;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\Filinq\Service\ObjectResultExtractor;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCA\OpenRegister\Kernel\Contracts\Value\SaveActor;
use RuntimeException;
use Throwable;

/**
 * Scope decisions live on the document's `documentReview`, their outcome on
 * its `anonymizationLink`: `withheld` and `withheldBases` for a whole
 * document, `burnedRegionCount` and the output for burned pages.
 *
 * Every write throws on failure, so a decision is never reported as stored
 * or a document as withheld when it is not.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Review
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-a-page-a-page-range-or-a-whole-document-is-redacted-in-one-action-req-ddimr-009
 */
class ScopeDecisionStore {

	/**
	 * The register.
	 */
	public const REGISTER = 'filinq';

	/**
	 * The schema that carries the decisions.
	 */
	public const REVIEW_SCHEMA = 'documentReview';

	/**
	 * The schema that carries the outcome.
	 */
	public const LINK_SCHEMA = 'anonymizationLink';

	/**
	 * Constructor.
	 *
	 * @param OpenRegisterServiceLocator $locator Resolver for OpenRegister's ObjectService.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly OpenRegisterServiceLocator $locator,
	) {

	}//end __construct()

	/**
	 * The scope decisions of a document.
	 *
	 * @param int $fileId The document.
	 *
	 * @return list<array<string, mixed>> The decisions, oldest first.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-6.1
	 */
	public function decisionsFor(int $fileId): array {
		$decisions = ($this->find(schema: self::REVIEW_SCHEMA, filters: ['fileId' => $fileId])['scopeDecisions'] ?? []);
		if (is_array($decisions) === false) {
			return [];
		}

		return array_values(array_filter($decisions, 'is_array'));

	}//end decisionsFor()

	/**
	 * Add a decision to the document's review.
	 *
	 * @param int                  $fileId   The document.
	 * @param array<string, mixed> $decision The validated decision.
	 *
	 * @return array<string, mixed> The decision as stored.
	 *
	 * @throws RuntimeException When the review cannot be written.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-6.1
	 */
	public function append(int $fileId, array $decision): array {
		$review = $this->find(schema: self::REVIEW_SCHEMA, filters: ['fileId' => $fileId]);
		if ($review === []) {
			$review = ['fileId' => $fileId];
		}

		$review['scopeDecisions'] = array_merge($this->decisionsOf(review: $review), [$decision]);
		$this->save(schema: self::REVIEW_SCHEMA, object: $review, system: false);

		return $decision;

	}//end append()

	/**
	 * Mark the document withheld on its link: no output, the grounds recorded.
	 *
	 * @param int          $fileId The document.
	 * @param list<string> $bases  The grounds.
	 * @param string       $userId Who withheld it.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the link cannot be written.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-6.2
	 */
	public function withhold(int $fileId, array $bases, string $userId): void {
		$link = $this->linkFor(fileId: $fileId);
		$link['withheld'] = true;
		$link['withheldBases'] = array_values($bases);
		$link['status'] = 'withheld';
		$link['anonymizedAt'] = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
		if ($userId !== '') {
			$link['anonymizedBy'] = $userId;
		}

		$this->save(schema: self::LINK_SCHEMA, object: $link, system: true);

	}//end withhold()

	/**
	 * Record a burn on the document's link: its output and the regions actually burned.
	 *
	 * @param int $fileId            The document.
	 * @param int $outputFileId      The `_anonymized` sibling.
	 * @param int $burnedRegionCount The regions the seam burned.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the link cannot be written.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-6.2
	 */
	public function recordBurn(int $fileId, int $outputFileId, int $burnedRegionCount): void {
		$link = $this->linkFor(fileId: $fileId);
		$link['anonymizedFileId'] = $outputFileId;
		$link['burnedRegionCount'] = $burnedRegionCount;
		$link['imageRedactionPending'] = false;
		$link['withheld'] = false;
		$link['status'] = 'anonymized';
		$link['runCount'] = ((int) ($link['runCount'] ?? 0) + 1);
		$link['anonymizedAt'] = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);

		$this->save(schema: self::LINK_SCHEMA, object: $link, system: true);

	}//end recordBurn()

	/**
	 * The stored link of a document, or a new one.
	 *
	 * @param int $fileId The document.
	 *
	 * @return array<string, mixed> The link.
	 */
	private function linkFor(int $fileId): array {
		$link = $this->find(schema: self::LINK_SCHEMA, filters: ['sourceFileId' => $fileId]);
		if ((int) ($link['sourceFileId'] ?? -1) !== $fileId) {
			return ['sourceFileId' => $fileId];
		}

		return $link;

	}//end linkFor()

	/**
	 * The decisions already on a review.
	 *
	 * @param array<string, mixed> $review The review.
	 *
	 * @return list<array<string, mixed>> The decisions.
	 */
	private function decisionsOf(array $review): array {
		$decisions = ($review['scopeDecisions'] ?? []);
		if (is_array($decisions) === false) {
			return [];
		}

		return array_values(array_filter($decisions, 'is_array'));

	}//end decisionsOf()

	/**
	 * The first object of a schema matching the filters, read by slug.
	 *
	 * @param string               $schema  The schema slug.
	 * @param array<string, mixed> $filters The filters.
	 *
	 * @return array<string, mixed> The object including its `@self`, or [] when there is none.
	 *
	 * @throws RuntimeException When OpenRegister cannot be read; a write must not proceed on a guess.
	 */
	private function find(string $schema, array $filters): array {
		try {
			$results = $this->objects()->searchObjectsBySlug(
				registerSlug: self::REGISTER,
				schemaSlug: $schema,
				filters: $filters
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The ' . $schema . ' could not be read: ' . $e->getMessage(), code: 0, previous: $e);
		}

		return ((new ObjectResultExtractor())->firstRow(result: $results) ?? []);

	}//end find()

	/**
	 * Save an object.
	 *
	 * @param string               $schema The schema slug.
	 * @param array<string, mixed> $object The object.
	 * @param bool                 $system Whether the write is system-asserted, as the link writes are.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the write fails.
	 */
	private function save(string $schema, array $object, bool $system): void {
		$self = ($object['@self'] ?? []);
		if (is_array($self) === false) {
			$self = [];
		}

		$object['@self'] = array_merge($self, ['register' => self::REGISTER, 'schema' => $schema]);
		$target = new ObjectFindTarget(register: self::REGISTER, schema: $schema);

		try {
			if ($system === true) {
				// The link is server-only, like every anonymisation link: the
				// caller reached this file in their own folder first.
				$this->objects()->saveObject(
					object: $object,
					options: new ObjectSaveOptions(target: $target, actor: new SaveActor(unowned: true)),
					_rbac: false
				);
				return;
			}

			$this->objects()->saveObject(object: $object, options: new ObjectSaveOptions(target: $target));
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The ' . $schema . ' could not be written, so nothing changed: ' . $e->getMessage(), code: 0, previous: $e);
		}

	}//end save()

	/**
	 * OpenRegister's ObjectService.
	 *
	 * @return mixed The service.
	 */
	private function objects(): mixed {
		return $this->locator->get(className: 'OCA\OpenRegister\Service\ObjectService');

	}//end objects()
}//end class
