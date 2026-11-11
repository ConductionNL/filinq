<?php

/**
 * LCS Walk
 *
 * Walks the suffix LCS table of two word lists into equal, delete and insert ops.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Comparison
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-comparison/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Comparison;

/**
 * One backtrack over the table: a word both sides share is an equal op; otherwise
 * the side whose skip keeps the longer common subsequence goes first, a tie
 * deleting before inserting. Built and used only by WordDiffer::backtrack().
 *
 * @category Service
 * @package  OCA\Filinq\Service\Comparison
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-comparison/spec.md
 */
final class LcsWalk {

	/**
	 * Position in the left words.
	 *
	 * @var integer
	 */
	private int $leftPos = 0;

	/**
	 * Position in the right words.
	 *
	 * @var integer
	 */
	private int $rightPos = 0;

	/**
	 * Constructor.
	 *
	 * @param array<int, string>          $a   Left words
	 * @param array<int, string>          $b   Right words
	 * @param array<int, array<int, int>> $lcs The suffix LCS length table of the two
	 */
	public function __construct(
		private readonly array $a,
		private readonly array $b,
		private readonly array $lcs,
	) {

	}//end __construct()

	/**
	 * Whether words are left on both sides.
	 *
	 * @return bool
	 *
	 * @spec openspec/specs/document-comparison/spec.md
	 */
	public function inBoth(): bool {
		return $this->leftPos < count($this->a) && $this->rightPos < count($this->b);

	}//end inBoth()

	/**
	 * The next op, moving past the words it covers.
	 *
	 * @return array{op:string, left?:string, right?:string}
	 *
	 * @spec openspec/specs/document-comparison/spec.md
	 */
	public function step(): array {
		if ($this->a[$this->leftPos] === $this->b[$this->rightPos]) {
			return $this->equal();
		}

		if ($this->lcs[($this->leftPos + 1)][$this->rightPos] >= $this->lcs[$this->leftPos][($this->rightPos + 1)]) {
			return $this->delete();
		}

		return $this->insert();

	}//end step()

	/**
	 * The left words not walked yet.
	 *
	 * @return array<int, string>
	 *
	 * @spec openspec/specs/document-comparison/spec.md
	 */
	public function leftRest(): array {
		return array_slice($this->a, $this->leftPos);

	}//end leftRest()

	/**
	 * The right words not walked yet.
	 *
	 * @return array<int, string>
	 *
	 * @spec openspec/specs/document-comparison/spec.md
	 */
	public function rightRest(): array {
		return array_slice($this->b, $this->rightPos);

	}//end rightRest()

	/**
	 * An equal op for the shared word at both positions.
	 *
	 * @return array{op:string, left:string, right:string}
	 */
	private function equal(): array {
		$op = [
			'op' => 'equal',
			'left' => $this->a[$this->leftPos],
			'right' => $this->b[$this->rightPos],
		];
		$this->leftPos++;
		$this->rightPos++;

		return $op;

	}//end equal()

	/**
	 * A delete op for the left word.
	 *
	 * @return array{op:string, left:string}
	 */
	private function delete(): array {
		$op = [
			'op' => 'delete',
			'left' => $this->a[$this->leftPos],
		];
		$this->leftPos++;

		return $op;

	}//end delete()

	/**
	 * An insert op for the right word.
	 *
	 * @return array{op:string, right:string}
	 */
	private function insert(): array {
		$op = [
			'op' => 'insert',
			'right' => $this->b[$this->rightPos],
		];
		$this->rightPos++;

		return $op;

	}//end insert()
}//end class
