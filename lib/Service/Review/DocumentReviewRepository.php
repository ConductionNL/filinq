<?php

/**
 * Reads and writes the per-document checked mark.
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
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Review;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\ReadsStoredObjectRows;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectDeletion;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Stores `documentReview` objects in the `filinq` register, one per file.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Review
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#requirement-per-document-checked-gate-req-ddarw-007
 */
class DocumentReviewRepository {
	use ReadsStoredObjectRows;

	/**
	 * The schema these rows live in.
	 *
	 * @var string
	 */
	public const SCHEMA = 'documentReview';

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
	 * The most recent mark for one file, or null when there is none.
	 *
	 * A read that fails returns null, and the gate treats null as unchecked:
	 * an OpenRegister that cannot answer stops a commit rather than letting it through.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<string, mixed>|null The mark, with `uuid`.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
	 */
	public function findFor(int $fileId): ?array {
		try {
			$results = $this->objectResolver->slugSearch()->search(
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA,
				filters: ['fileId' => $fileId]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[DocumentReviewRepository] could not read the review mark; the document counts as unchecked',
				context: ['fileId' => $fileId, 'error' => $e->getMessage()]
			);
			return null;
		}

		if (is_array($results) === false) {
			return null;
		}

		return $this->latestOf(results: $results, fileId: $fileId);

	}//end findFor()

	/**
	 * The most recent mark of one file among search results, or null when none is for it.
	 *
	 * @param array<int, mixed> $results The search results.
	 * @param int               $fileId  The Nextcloud file id.
	 *
	 * @return array<string, mixed>|null The latest mark's fields.
	 */
	private function latestOf(array $results, int $fileId): array|null {
		$latest = null;
		foreach ($results as $row) {
			$fields = $this->storedFields(row: $row);
			if ((int)($fields['fileId'] ?? 0) === $fileId && $this->isLater(fields: $fields, than: $latest) === true) {
				$latest = $fields;
			}
		}

		return $latest;

	}//end latestOf()

	/**
	 * Whether a mark was checked later than another, or the other is absent.
	 *
	 * @param array<string, mixed>      $fields The mark.
	 * @param array<string, mixed>|null $than   The mark so far.
	 *
	 * @return bool True when it is later.
	 */
	private function isLater(array $fields, array|null $than): bool {
		return $than === null || (string)($fields['checkedOn'] ?? '') > (string)($than['checkedOn'] ?? '');

	}//end isLater()

	/**
	 * Write a new mark, with OpenRegister RBAC on.
	 *
	 * @param array<string, mixed> $mark The mark.
	 *
	 * @return array<string, mixed> The stored mark.
	 *
	 * @throws RuntimeException When the write fails.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
	 */
	public function save(array $mark): array {
		try {
			$stored = $this->objectResolver->resolve()->saveObject(
				object: $mark,
				options: new ObjectSaveOptions(
					target: new ObjectFindTarget(register: IntakeRepository::REGISTER, schema: self::SCHEMA)
				)
			);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'The review mark could not be recorded: '.$e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		return $this->storedFields(row: $stored);

	}//end save()

	/**
	 * Delete one mark with the app's own authority: for replacing a mark the
	 * caller has just replaced by saving a new one under its own rights.
	 *
	 * @param string $uuid The mark's uuid.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When OpenRegister refuses the delete.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
	 */
	public function delete(string $uuid): void {
		$deletion = new ObjectDeletion(
			uuid: $uuid,
			target: new ObjectFindTarget(register: IntakeRepository::REGISTER, schema: self::SCHEMA)
		);
		try {
			$deleted = $this->objectResolver->resolve()->deleteObject(deletion: $deletion, _rbac: false);
		} catch (Throwable $e) {
			throw $this->removalFailed(exception: $e);
		}

		$this->assertRemoved(deleted: $deleted, uuid: $uuid);

	}//end delete()

	/**
	 * Delete one mark as the calling user, so the `documentReview` schema's
	 * delete rule decides who may remove it.
	 *
	 * @param string $uuid The mark's uuid.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When OpenRegister refuses the delete.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
	 */
	public function deleteAsCaller(string $uuid): void {
		$deletion = new ObjectDeletion(
			uuid: $uuid,
			target: new ObjectFindTarget(register: IntakeRepository::REGISTER, schema: self::SCHEMA)
		);
		try {
			$deleted = $this->objectResolver->resolve()->deleteObject(deletion: $deletion, _rbac: true);
		} catch (Throwable $e) {
			throw $this->removalFailed(exception: $e);
		}

		$this->assertRemoved(deleted: $deleted, uuid: $uuid);

	}//end deleteAsCaller()

	/**
	 * The error a failed delete is reported as.
	 *
	 * @param Throwable $exception What OpenRegister threw.
	 *
	 * OpenRegister refuses a delete the schema does not allow with code 403;
	 * that code is kept, so the caller can answer a refusal as one.
	 *
	 * @return RuntimeException The error.
	 */
	private function removalFailed(Throwable $exception): RuntimeException {
		$code = 0;
		if ($exception->getCode() === 403) {
			$code = 403;
		}

		return new RuntimeException(message: 'The review mark could not be removed: '.$exception->getMessage(), code: $code, previous: $exception);

	}//end removalFailed()

	/**
	 * Refuse a delete OpenRegister answered with false.
	 *
	 * @param mixed  $deleted What OpenRegister answered.
	 * @param string $uuid    The mark's uuid.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When it did not remove the mark.
	 */
	private function assertRemoved(mixed $deleted, string $uuid): void {
		if ($deleted === false) {
			throw new RuntimeException(message: 'OpenRegister did not remove the review mark '.$uuid.'.');
		}

	}//end assertRemoved()
}//end class
