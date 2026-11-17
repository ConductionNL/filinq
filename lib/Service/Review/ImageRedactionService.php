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
		$detection = $this->detect(file: $file, ocrFields: $ocrFields);

		return $this->withRegions(entities: $entities, detection: $detection);

	}//end afterExtraction()

	/**
	 * Send an image-bearing file to the seam, before the entity rows are read back.
	 *
	 * The seam stores what it finds as entity relations, so detecting first
	 * lets those rows take the same proposals and policy as a text finding.
	 *
	 * @param File                 $file      The file.
	 * @param array<string, mixed> $ocrFields What the OCR fallback reported.
	 *
	 * @return array{regions: list<array<string, mixed>>, fields: array<string, mixed>} The regions and the fields to add.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-3.2
	 */
	public function detect(File $file, array $ocrFields = []): array {
		if ($this->isImageBearing(file: $file, ocrFields: $ocrFields) === false) {
			return ['regions' => [], 'fields' => []];
		}

		if ($this->seam->available() === false) {
			return ['regions' => [], 'fields' => ['imageDetectionSkipped' => ['reason' => self::REASON_BACKEND_UNAVAILABLE]]];
		}

		try {
			$outcome = $this->seam->detect(file: $file);
		} catch (Throwable $e) {
			return [
				'regions' => [],
				'fields' => ['imageDetectionSkipped' => ['reason' => self::REASON_BACKEND_UNAVAILABLE, 'detail' => $e->getMessage()]],
			];
		}

		$fields = [
			'objectDetection' => $outcome['objectDetection'],
			'objectDetectionReason' => $outcome['objectDetectionReason'],
		];
		if ($outcome['detected'] === false) {
			$fields['imageDetectionSkipped'] = ['reason' => self::REASON_NOT_DETECTED, 'detail' => (string) $outcome['reason']];

			return ['regions' => [], 'fields' => $fields];
		}

		$fields['imageRegionCount'] = count($outcome['regions']);

		return ['regions' => $outcome['regions'], 'fields' => $fields];

	}//end detect()

	/**
	 * The entity rows with a detection's regions attached, and its fields.
	 *
	 * @param list<array<string, mixed>>                                                  $entities  The entity rows.
	 * @param array{regions: list<array<string, mixed>>, fields: array<string, mixed>} $detection What detect() answered.
	 *
	 * @return array{entities: list<array<string, mixed>>, fields: array<string, mixed>} The rows and the fields to add.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-3.2
	 */
	public function withRegions(array $entities, array $detection): array {
		return ['entities' => $this->attachRegions(entities: $entities, regions: $detection['regions']), 'fields' => $detection['fields']];

	}//end withRegions()

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
		$byRelation = self::relationIndex(entities: $entities);
		foreach ($regions as $region) {
			$box = ['page' => ($region['page'] ?? null)] + (array) ($region['box'] ?? []);
			$relation = (string) ($region['relationId'] ?? '');
			$index = self::indexOf(byRelation: $byRelation, relation: $relation);
			if ($index !== null) {
				$entities[$index]['origin'] = 'image';
				$entities[$index]['boxes'] = array_merge((array) ($entities[$index]['boxes'] ?? []), [$box]);
				continue;
			}

			$entities[] = self::regionRow(region: $region, box: $box);
			if ($relation !== '') {
				$byRelation[$relation] = (count($entities) - 1);
			}
		}

		return $entities;

	}//end attachRegions()

	/**
	 * The index of every entity row by its relation id.
	 *
	 * @param list<array<string, mixed>> $entities The rows.
	 *
	 * @return array<string, int> Row index per relation id.
	 */
	private static function relationIndex(array $entities): array {
		$byRelation = [];
		foreach ($entities as $index => $entity) {
			$relation = ($entity['relationId'] ?? null);
			if ($relation !== null) {
				$byRelation[(string) $relation] = $index;
			}
		}

		return $byRelation;

	}//end relationIndex()

	/**
	 * The row index a region's relation id points at, or null.
	 *
	 * @param array<string, int> $byRelation Row index per relation id.
	 * @param string             $relation   The region's relation id, '' when it has none.
	 *
	 * @return int|null The index.
	 */
	private static function indexOf(array $byRelation, string $relation): ?int {
		if ($relation === '') {
			return null;
		}

		return ($byRelation[$relation] ?? null);

	}//end indexOf()

	/**
	 * A new entity row for a region no text finding matched.
	 *
	 * The shape EntityDetectionService::normalizeEntities() gives a text finding.
	 *
	 * @param array<string, mixed> $region The seam's region.
	 * @param array<string, mixed> $box    The region's page and box.
	 *
	 * @return array<string, mixed> The row.
	 */
	private static function regionRow(array $region, array $box): array {
		return [
			'type' => (string) ($region['entityType'] ?? ''),
			'value' => '',
			'confidence' => (float) ($region['confidence'] ?? 0.0),
			'relationId' => ($region['relationId'] ?? null),
			'bases' => null,
			'skipAnonymization' => false,
			'origin' => 'image',
			'boxes' => [$box],
		];

	}//end regionRow()

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
		if ($this->hasImageContent(file: $file, standalone: $standalone, decided: $decided, ocrFields: $ocrFields) === false) {
			return [];
		}

		if ($standalone === true && $this->seam->available() === true) {
			return ['imageRedactionPending' => false, 'burnedRegionCount' => $decided];
		}

		return ['imageRedactionPending' => true, 'burnedRegionCount' => 0];

	}//end anonymizeFields()

	/**
	 * Whether a file carries image content an anonymise run must account for.
	 *
	 * @param File                 $file       The source file.
	 * @param bool                 $standalone Whether the file is a standalone image.
	 * @param int                  $decided    The decided regions the seam holds for it.
	 * @param array<string, mixed> $ocrFields  What the OCR fallback reported, when known.
	 *
	 * @return bool True for a standalone image, a file with decided regions, or an image-bearing file.
	 */
	private function hasImageContent(File $file, bool $standalone, int $decided, array $ocrFields): bool {
		if ($standalone === true || $decided > 0) {
			return true;
		}

		return $this->isImageBearing(file: $file, ocrFields: $ocrFields);

	}//end hasImageContent()
}//end class
