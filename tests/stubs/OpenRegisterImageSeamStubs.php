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
