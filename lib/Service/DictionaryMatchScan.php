<?php

/**
 * Dictionary Match Scan
 *
 * One custom-dictionary detection pass over one file's chunks.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The dictionaries, the file and the catalogue mapper of one pass, plus what
 * the pass has seen so far: the absolute ranges already matched and the
 * catalogue entity id per matched value.
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */
final class DictionaryMatchScan {

	/**
	 * Absolute `[start, end]` ranges already matched.
	 *
	 * @var array<int, array{0: int, 1: int}>
	 */
	private array $claimed = [];

	/**
	 * Catalogue entity id per matched value.
	 *
	 * @var array<string, int>
	 */
	private array $entityIds = [];

	/**
	 * @param array<int, mixed> $dictionaries Active dictionaries + terms.
	 * @param int               $fileId       The Nextcloud file ID.
	 * @param mixed             $entityMapper OpenRegister `GdprEntityMapper` instance.
	 */
	public function __construct(
		public readonly array $dictionaries,
		public readonly int $fileId,
		public readonly mixed $entityMapper,
	) {
	}//end __construct()

	/**
	 * Claim `[start, end)` unless it overlaps a range claimed before.
	 *
	 * @param int $start Absolute match start.
	 * @param int $end   Absolute match end.
	 *
	 * @return bool True when the range was free and is now claimed.
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function claim(int $start, int $end): bool {
		foreach ($this->claimed as [$claimedStart, $claimedEnd]) {
			if ($start < $claimedEnd && $end > $claimedStart) {
				return false;
			}
		}

		$this->claimed[] = [$start, $end];
		return true;
	}//end claim()

	/**
	 * The catalogue entity id remembered for a value, or null.
	 *
	 * @param string $value The matched value.
	 *
	 * @return int|null The entity id.
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function entityIdOf(string $value): ?int {
		return $this->entityIds[$value] ?? null;
	}//end entityIdOf()

	/**
	 * Remember the catalogue entity id for a value.
	 *
	 * @param string $value    The matched value.
	 * @param int    $entityId The entity id.
	 *
	 * @return int The entity id.
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function remember(string $value, int $entityId): int {
		$this->entityIds[$value] = $entityId;
		return $entityId;
	}//end remember()
}//end class
