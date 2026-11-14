<?php

/**
 * Digitisation Project Service
 *
 * Decides, when a paper original has been scanned, whether a person checks
 * the scan against the paper or whether it is recorded as passed without a
 * look.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-sampled-share-is-checked-by-a-person-req-pad-004
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Random\Randomizer;

/**
 * The sampling draw (design D4).
 *
 * A scanned original goes to the checking queue with the project's check
 * share, and always when its separator could not be read or the scan has no
 * pages: those are the scans most likely to be wrong. An original not drawn
 * is recorded as passed by `sample-not-drawn`, so the record says that
 * nobody looked at it rather than that somebody did.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-sampled-share-is-checked-by-a-person-req-pad-004
 */
class DigitisationProjectService {

	/**
	 * The check share of a project that declares none, in percent.
	 */
	public const DEFAULT_CHECK_SHARE = 10;

	/**
	 * Who "checked" an original that was not drawn.
	 */
	public const NOT_DRAWN = 'sample-not-drawn';

	/**
	 * The status of a scanned original waiting in the checking queue.
	 */
	public const SCANNED = 'scanned';

	/**
	 * The status of a checked original.
	 */
	public const CHECKED = 'checked';

	/**
	 * The random source.
	 *
	 * @var Randomizer
	 */
	private readonly Randomizer $random;

	/**
	 * Constructor.
	 *
	 * @param Randomizer|null $random The random source; a secure one when none is given.
	 */
	public function __construct(?Randomizer $random=null) {
		$this->random = ($random ?? new Randomizer());

	}//end __construct()

	/**
	 * What to write on an original that has just been scanned.
	 *
	 * @param array<string, mixed> $project           The digitisation project (`checkShare`).
	 * @param bool                 $separatorReadable Whether the separator before it was read.
	 * @param int                  $pageCount         How many pages the scan has.
	 * @param string               $now               The moment, ISO 8601.
	 *
	 * @return array<string, mixed> `status` `scanned` (in the checking queue), or
	 *                              `status` `checked` with a passed check by `sample-not-drawn`.
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-sampled-share-is-checked-by-a-person-req-pad-004
	 */
	public function afterScan(array $project, bool $separatorReadable, int $pageCount, string $now): array {
		if ($this->drawn(project: $project, separatorReadable: $separatorReadable, pageCount: $pageCount) === true) {
			return ['status' => self::SCANNED];
		}

		return [
			'status' => self::CHECKED,
			'check' => ['result' => 'passed', 'checkedBy' => self::NOT_DRAWN, 'checkedAt' => $now],
		];

	}//end afterScan()

	/**
	 * Whether a person checks this scan.
	 *
	 * @param array<string, mixed> $project           The project.
	 * @param bool                 $separatorReadable Whether the separator was read.
	 * @param int                  $pageCount         The scan's page count.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-sampled-share-is-checked-by-a-person-req-pad-004
	 */
	public function drawn(array $project, bool $separatorReadable, int $pageCount): bool {
		if ($separatorReadable === false || $pageCount <= 0) {
			return true;
		}

		$share = self::checkShare(project: $project);
		if ($share <= 0) {
			return false;
		}

		return $this->random->getInt(1, 100) <= $share;

	}//end drawn()

	/**
	 * A project's check share in percent, 0 to 100; the default when it declares none or nonsense.
	 *
	 * @param array<string, mixed> $project The project.
	 *
	 * @return int
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-sampled-share-is-checked-by-a-person-req-pad-004
	 */
	public static function checkShare(array $project): int {
		$share = ($project['checkShare'] ?? null);
		if (is_numeric($share) === false) {
			return self::DEFAULT_CHECK_SHARE;
		}

		return max(0, min(100, (int) round((float) $share)));

	}//end checkShare()
}//end class
