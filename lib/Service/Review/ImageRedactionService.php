<?php

/**
 * Image submission and review plumbing around OpenRegister's image seam.
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
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-missing-image-capability-degrades-fail-flagged-never-fail-silent-req-ddimr-002
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Review;

use OCP\Files\File;
use Throwable;

/**
 * Says what happened to a file's image content, never leaving it silent.
 *
 * On extract an image-bearing file (a standalone image, or a PDF the OCR
 * fallback had to read) goes to the seam; when it cannot, the result carries
 * `imageDetectionSkipped` with the reason. Found regions are attached to the
 * entity rows they belong to as `origin: image` and `boxes`, additively. On
 * anonymise the run carries `imageRedactionPending` whenever image content
 * may still show what was redacted, and `burnedRegionCount` only for regions
 * the seam actually burned.
 *
 * Detection and burning are OpenRegister's (decision D2); this class only
 * decides what to submit and how to report it.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Review
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-missing-image-capability-degrades-fail-flagged-never-fail-silent-req-ddimr-002
 */
class ImageRedactionService {

	/**
	 * The seam is not there, or did not answer.
	 */
	public const REASON_BACKEND_UNAVAILABLE = 'backend_unavailable';

	/**
	 * The seam was asked but could not look at the image.
	 */
	public const REASON_NOT_DETECTED = 'not_detected';

	/**
	 * Constructor.
	 *
	 * @param ImageSeamGateway $seam OpenRegister's image seam.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ImageSeamGateway $seam,
	) {

	}//end __construct()

	/**
	 * Whether a file carries image content: a standalone image, or a PDF that needed OCR.
	 *
	 * @param File                 $file      The file.
	 * @param array<string, mixed> $ocrFields What the OCR fallback reported on extract.
	 *
	 * @return bool True when the file has pixels that may hold personal data.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-3.3
	 */
	public function isImageBearing(File $file, array $ocrFields = []): bool {
		if (in_array((string) $file->getMimeType(), DocumentPageCounter::IMAGE_TYPES, true) === true) {
			return true;
		}

		return (($ocrFields['ocr']['ran'] ?? false) === true);

	}//end isImageBearing()

	/**
	 * The extract fields of the image leg, with the entity rows its regions belong to.
	 *
	 * @param File                     $file      The file.
	 * @param list<array<string, mixed>> $entities The consolidated entity rows.
	 * @param array<string, mixed>     $ocrFields What the OCR fallback reported.
	 *
	 * @return array{entities: list<array<string, mixed>>, fields: array<string, mixed>} The rows and the fields to add.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-3.2
	 */
	public function afterExtraction(File $file, array $entities, array $ocrFields = []): array {
		if ($this->isImageBearing(file: $file, ocrFields: $ocrFields) === false) {
			return ['entities' => $entities, 'fields' => []];
		}

		if ($this->seam->available() === false) {
			return ['entities' => $entities, 'fields' => ['imageDetectionSkipped' => ['reason' => self::REASON_BACKEND_UNAVAILABLE]]];
		}

		try {
			$outcome = $this->seam->detect(file: $file);
		} catch (Throwable $e) {
			return [
				'entities' => $entities,
				'fields' => ['imageDetectionSkipped' => ['reason' => self::REASON_BACKEND_UNAVAILABLE, 'detail' => $e->getMessage()]],
			];
		}

		$fields = [
			'objectDetection' => $outcome['objectDetection'],
			'objectDetectionReason' => $outcome['objectDetectionReason'],
		];
		if ($outcome['detected'] === false) {
			$fields['imageDetectionSkipped'] = ['reason' => self::REASON_NOT_DETECTED, 'detail' => (string) $outcome['reason']];

			return ['entities' => $entities, 'fields' => $fields];
		}

		$fields['imageRegionCount'] = count($outcome['regions']);

		return ['entities' => $this->attachRegions(entities: $entities, regions: $outcome['regions']), 'fields' => $fields];

	}//end afterExtraction()

	/**
	 * Attach each region to the entity row of its relation as `origin: image` and `boxes`.
	 *
	 * A region whose relation is not among the rows (a detector finding such
	 * as a signature) becomes its own row, so nothing the seam stored escapes
	 * the review.
	 *
	 * @param list<array<string, mixed>> $entities The entity rows.
	 * @param list<array<string, mixed>> $regions  The seam's regions.
	 *
	 * @return list<array<string, mixed>> The rows.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-3.2
	 */
	public function attachRegions(array $entities, array $regions): array {
		$byRelation = [];
		foreach ($entities as $index => $entity) {
			$relation = ($entity['relationId'] ?? null);
			if ($relation !== null) {
				$byRelation[(string) $relation] = $index;
			}
		}

		foreach ($regions as $region) {
			$box = ['page' => ($region['page'] ?? null)] + (array) ($region['box'] ?? []);
			$relation = (string) ($region['relationId'] ?? '');
			if ($relation !== '' && isset($byRelation[$relation]) === true) {
				$index = $byRelation[$relation];
				$entities[$index]['origin'] = 'image';
				$entities[$index]['boxes'] = array_merge((array) ($entities[$index]['boxes'] ?? []), [$box]);
				continue;
			}

			$entities[] = [
				'entityType' => (string) ($region['entityType'] ?? ''),
				'text' => '',
				'confidence' => (float) ($region['confidence'] ?? 0.0),
				'relationId' => ($region['relationId'] ?? null),
				'origin' => 'image',
				'boxes' => [$box],
				'included' => true,
			];
			if ($relation !== '') {
				$byRelation[$relation] = (count($entities) - 1);
			}
		}//end foreach

		return $entities;

	}//end attachRegions()

	/**
	 * The anonymise fields: whether image content is still pending, and what was burned.
	 *
	 * The seam burns decided regions of a standalone image inside the
	 * anonymise call. It does not burn the pixels of a PDF yet, so a PDF with
	 * decided regions is pending, and so is every image-bearing file without
	 * the seam.
	 *
	 * @param File                 $file      The source file.
	 * @param array<string, mixed> $ocrFields What the OCR fallback reported, when known.
	 *
	 * @return array{imageRedactionPending?: bool, burnedRegionCount?: int} The fields; none for a file without image content.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-3.3
	 */
	public function anonymizeFields(File $file, array $ocrFields = []): array {
		$decided = $this->seam->decidedRegionCount(file: $file);
		$standalone = in_array((string) $file->getMimeType(), DocumentPageCounter::IMAGE_TYPES, true);
		if ($standalone === false && $decided === 0 && $this->isImageBearing(file: $file, ocrFields: $ocrFields) === false) {
			return [];
		}

		if ($this->seam->available() === false) {
			return ['imageRedactionPending' => true, 'burnedRegionCount' => 0];
		}

		if ($standalone === true) {
			return ['imageRedactionPending' => false, 'burnedRegionCount' => $decided];
		}

		return ['imageRedactionPending' => true, 'burnedRegionCount' => 0];

	}//end anonymizeFields()
}//end class
