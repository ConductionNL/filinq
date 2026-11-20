<?php

/**
 * OpenRegister's image seam, from its signatures on openregister main
 * (lib/Service/File/ImageRedactionService.php, lib/Service/File/ImageRegion.php),
 * for tests that run without OpenRegister installed. Only the members filinq
 * calls are present; the burn records what it was given.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\OpenRegister\Service\File;

use OCP\Files\File;

if (class_exists(ImageRegion::class) === false) {
	/**
	 * A region on a page or a standalone image.
	 */
	final class ImageRegion {

		/**
		 * Constructor, as on openregister main.
		 *
		 * @param int|null                                      $page       The page, null for a standalone image.
		 * @param array{x: float, y: float, w: float, h: float} $box        The box.
		 * @param string                                        $entityType What the region holds.
		 * @param float                                         $confidence How sure the finder was.
		 * @param string                                        $source     Who found it.
		 * @param int|null                                      $relationId The stored relation.
		 */
		public function __construct(
			public readonly ?int $page,
			public readonly array $box,
			public readonly string $entityType,
			public readonly float $confidence,
			public readonly string $source,
			public readonly ?int $relationId = null,
		) {
		}
	}
}

if (class_exists(ImageDetection::class) === false) {
	/**
	 * A detection outcome; toArray() keeps openregister main's keys.
	 */
	final class ImageDetection {

		/**
		 * Constructor, as on openregister main.
		 *
		 * @param bool                 $detected  Whether the file was detected.
		 * @param list<ImageRegion>    $regions   The regions.
		 * @param array<string, mixed> $detectors Each detector's status.
		 * @param string|null          $reason    Why it was not detected.
		 */
		public function __construct(
			public readonly bool $detected,
			public readonly array $regions = [],
			public readonly array $detectors = [],
			public readonly ?string $reason = null,
		) {
		}

		/**
		 * The outcome as an array.
		 *
		 * @return array<string, mixed> The outcome.
		 */
		public function toArray(): array {
			$ran = array_filter($this->detectors, static fn (array $d): bool => ($d['status'] ?? '') === 'ran');

			return [
				'detected' => $this->detected,
				'reason' => $this->reason,
				'regions' => array_map(
					static fn (ImageRegion $r): array => ['page' => $r->page, 'box' => $r->box, 'entityType' => $r->entityType, 'confidence' => $r->confidence, 'source' => $r->source, 'relationId' => $r->relationId],
					$this->regions
				),
				'detectors' => $this->detectors,
				'objectDetection' => $ran === [] ? 'not-run' : 'ran',
				'objectDetectionReason' => $ran === [] ? 'No region detector ran.' : null,
			];
		}
	}
}

if (class_exists(ImageRedactionService::class) === false) {
	/**
	 * The seam; the test sets what a burn answers.
	 */
	class ImageRedactionService {

		public const STANDALONE_TYPES = ['image/png', 'image/jpeg', 'image/tiff', 'image/webp'];

		/**
		 * The regions of the last burn.
		 *
		 * @var list<ImageRegion>
		 */
		public array $burned = [];

		/**
		 * What the next burn answers: a file, or a throwable to throw.
		 *
		 * @var mixed
		 */
		public mixed $answer = null;

		/**
		 * What the next detection answers.
		 *
		 * @var ImageDetection|null
		 */
		public ?ImageDetection $detection = null;

		/**
		 * The decided regions.
		 *
		 * @var list<ImageRegion>
		 */
		public array $decided = [];

		/**
		 * Detect, as on openregister main.
		 *
		 * @param File $file The file.
		 *
		 * @return ImageDetection The outcome.
		 */
		public function detectImage(File $file): ImageDetection {
			return $this->detection ?? new ImageDetection(detected: false, reason: 'No OCR words with positions were provided for this file, so it was not detected.');
		}

		/**
		 * The decided regions, as on openregister main.
		 *
		 * @param File $file The file.
		 *
		 * @return list<ImageRegion> The regions.
		 */
		public function decidedRegions(File $file): array {
			return $this->decided;
		}

		/**
		 * Burn the regions, as on openregister main.
		 *
		 * @param File              $file    The image.
		 * @param list<ImageRegion> $regions The regions.
		 *
		 * @return File The sibling.
		 */
		public function redactImage(File $file, array $regions): File {
			$this->burned = $regions;
			if ($this->answer instanceof \Throwable) {
				throw $this->answer;
			}

			return $this->answer;
		}
	}
}
