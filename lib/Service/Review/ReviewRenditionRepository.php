<?php

/**
 * Storage of the current review rendition per source file.
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
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Review;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\ReadsStoredObjectRows;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCA\OpenRegister\Kernel\Contracts\Value\SaveActor;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Stores `reviewRendition` objects in the `filinq` register, one per source file.
 *
 * The anonymisation link exists only once a file has been anonymised, while
 * the rendition is prepared before detection. The current rendition is kept
 * here so the checked mark and the commit gate can compare against it, and the
 * link copies it when the commit runs.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Review
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#requirement-review-runs-on-one-pdf-rendition-with-a-text-layer-and-the-link-records-it-req-ddarw-012
 */
class ReviewRenditionRepository {
	use ReadsStoredObjectRows;

	/**
	 * The schema these rows live in.
	 *
	 * @var string
	 */
	public const SCHEMA = 'reviewRendition';

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
	 * The current rendition of one source file, or null when none was prepared or it cannot be read.
	 *
	 * A failed read returns null. A check that stored a rendition then no
	 * longer matches, so the gate refuses rather than letting a commit through.
	 *
	 * @param int $sourceFileId The source's Nextcloud file id.
	 *
	 * @return array<string, mixed>|null The rendition, with `uuid` and `sourceFileId`.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.1
	 */
	public function findFor(int $sourceFileId): ?array {
		try {
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: ['sourceFileId' => $sourceFileId]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[ReviewRenditionRepository] could not read the review rendition',
				context: ['sourceFileId' => $sourceFileId, 'error' => $e->getMessage()]
			);
			return null;
		}

		foreach ((array) $results as $row) {
			$fields = $this->storedFields(row: $row);
			// The filter is the search's; the match is ours.
			if ((int) ($fields['sourceFileId'] ?? 0) === $sourceFileId) {
				return $fields;
			}
		}

		return null;

	}//end findFor()

	/**
	 * Store a source's rendition, replacing the one it had.
	 *
	 * @param int                  $sourceFileId The source's Nextcloud file id.
	 * @param array<string, mixed> $rendition    The six rendition fields.
	 *
	 * @return array<string, mixed> The stored row.
	 *
	 * @throws RuntimeException When OpenRegister refuses the write.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.1
	 */
	public function saveFor(int $sourceFileId, array $rendition): array {
		$object = ['sourceFileId' => $sourceFileId] + $rendition;
		unset($object['uuid']);

		$uuid     = null;
		$existing = $this->findFor(sourceFileId: $sourceFileId);
		if ($existing !== null && (string) ($existing['uuid'] ?? '') !== '') {
			$uuid = (string) $existing['uuid'];
		}

		try {
			// Server only: nobody writes a rendition by hand, so a check cannot be
			// made to match a rendition the reviewer never saw.
			$stored = $this->objectResolver->resolve()->saveObject(
				object: $object,
				options: new ObjectSaveOptions(
					target: new ObjectFindTarget(register: IntakeRepository::REGISTER, schema: self::SCHEMA),
					uuid: $uuid,
					actor: new SaveActor(unowned: true)
				),
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'The review rendition could not be recorded: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		return $this->storedFields(row: $stored);

	}//end saveFor()
}//end class
