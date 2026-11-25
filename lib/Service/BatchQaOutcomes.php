<?php

/**
 * Batch QA Outcomes: what became of each QA-sampled file, read from its documentReview.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * A sampled file is routed to the review workbench's checked gate: its
 * `documentReview` (keyed on the source file id) is the human check. The
 * outcome is read from it, never stored by hand: no review yet is `pending`,
 * a review that added manual entities is `corrected`, a review without them
 * is `confirmed`. The read goes through the schema's own cascade: any
 * authenticated user may read a review.
 *
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-sampling-qa-routed-to-human-review-req-ddras-007
 */
class BatchQaOutcomes {

	/**
	 * No human check yet.
	 */
	public const PENDING = 'pending';

	/**
	 * Checked, nothing added.
	 */
	public const CONFIRMED = 'confirmed';

	/**
	 * Checked, with manual entities added.
	 */
	public const CORRECTED = 'corrected';

	/**
	 * The schema of the workbench's checked gate.
	 */
	public const SCHEMA = 'documentReview';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService.
	 * @param LoggerInterface               $logger         Logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The batch with every sampled file's outcome read from its review.
	 *
	 * @param array<string, mixed> $batch The batch.
	 *
	 * @return array<string, mixed> The batch, sampled files carrying their current `qaOutcome`.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-sampling-qa-routed-to-human-review-req-ddras-007
	 */
	public function refresh(array $batch): array {
		foreach ((array)($batch['files'] ?? []) as $index => $file) {
			if (($file['qaSampled'] ?? false) !== true) {
				continue;
			}

			$outcome = $this->outcomeFor(fileId: (int)($file['fileId'] ?? 0));
			if ($outcome !== null) {
				$batch['files'][$index]['qaOutcome'] = $outcome;
			}
		}

		return $batch;

	}//end refresh()

	/**
	 * The outcome of one sampled file, or null when the review could not be read.
	 *
	 * A failed read keeps the stored outcome rather than reporting a check
	 * nobody could confirm.
	 *
	 * @param int $fileId The source file id.
	 *
	 * @return string|null
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-sampling-qa-routed-to-human-review-req-ddras-007
	 */
	public function outcomeFor(int $fileId): ?string {
		if ($fileId <= 0) {
			return null;
		}

		try {
			$rows = (new SlugSearch(objects: $this->objectResolver->resolve()))->search(
				register: BatchStateRepository::REGISTER,
				schema: self::SCHEMA,
				filters: ['fileId' => $fileId]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				'BatchQaOutcomes: could not read the review of a sampled file',
				['fileId' => $fileId, 'error' => $e->getMessage()]
			);
			return null;
		}

		if (is_array($rows) === false || $rows === []) {
			return self::PENDING;
		}

		$review = $rows[0];
		if (is_object($review) === true && method_exists($review, 'jsonSerialize') === true) {
			$review = $review->jsonSerialize();
		}

		if ((int)(((array)$review)['manualEntityCount'] ?? 0) > 0) {
			return self::CORRECTED;
		}

		return self::CONFIRMED;

	}//end outcomeFor()
}//end class
