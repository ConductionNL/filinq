<?php

/**
 * Term Match Run
 *
 * One pass of CustomDictionaryMatchService::match() over one text: the
 * text, the match mode, the ranges claimed so far and the occurrences
 * found. Built and used inside CustomDictionaryMatchService only.
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
 * The text, mode, claimed ranges and occurrences of one term-matching pass.
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */
final class TermMatchRun {

	/**
	 * The ranges claimed so far.
	 *
	 * @var ClaimedRanges
	 */
	public readonly ClaimedRanges $ranges;

	/**
	 * The occurrences found so far, in the order they were claimed.
	 *
	 * @var array<int, array{value: string, label: string, positionStart: int, positionEnd: int}>
	 */
	public array $occurrences = [];

	/**
	 * Constructor.
	 *
	 * @param string $text The document text to search.
	 * @param string $mode The normalised match mode.
	 */
	public function __construct(
		public readonly string $text,
		public readonly string $mode,
	) {
		$this->ranges = new ClaimedRanges();

	}//end __construct()
}//end class
