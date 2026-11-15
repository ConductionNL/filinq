<?php

/**
 * Word Differ
 *
 * Computes the word-level structured diff behind the document comparison
 * response: a longest-common-subsequence over word tokens, backtracked into an
 * op list and coalesced into ordered hunks with left/right offsets.
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
 * Computes a word-level structured diff coalesced into hunks.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Comparison
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-comparison/spec.md
 */
class WordDiffer {
	/**
	 * Compute a word-level structured diff coalesced into hunks.
	 *
	 * Uses a longest-common-subsequence over word tokens, then groups runs
	 * of equal/insert/delete operations into hunks with left/right offsets.
	 *
	 * @param string $leftText Left (normalised) text.
	 * @param string $rightText Right (normalised) text.
	 *
	 * @return array<int, array<string, mixed>> Ordered hunks.
	 *
	 * @spec openspec/specs/document-comparison/spec.md
	 */
	public function diff(string $leftText, string $rightText): array {
		$ops = $this->lcsDiff(a: $this->words(text: $leftText), b: $this->words(text: $rightText));

		$hunks = [];
		$walk = new HunkWalk();
		$count = count($ops);

		while ($walk->cursor < $count) {
			$hunks[] = $this->nextHunk(ops: $ops, walk: $walk);
		}

		return $hunks;
	}//end diff()

	/**
	 * Take the run of ops at the cursor and build its hunk: equal words, or a block of deletes and inserts.
	 *
	 * @param array<int, array<string, mixed>> $ops The op list.
	 * @param HunkWalk $walk The cursor and byte offsets, moved past the run.
	 *
	 * @return array<string, mixed> The hunk.
	 */
	private function nextHunk(array $ops, HunkWalk $walk): array {
		// Coalesce consecutive ops of the same logical hunk type.
		if ($ops[$walk->cursor]['op'] === 'equal') {
			$run = $this->takeEqualRun(ops: $ops, cursor: $walk->cursor);
			$walk->cursor = $run['cursor'];
			return $this->unchangedHunk(text: $run['text'], offsets: $walk);
		}

		// A change block = a run of deletes and/or inserts.
		$run = $this->takeChangeRun(ops: $ops, cursor: $walk->cursor);
		$walk->cursor = $run['cursor'];
		return $this->changeHunk(run: $run, offsets: $walk);
	}//end nextHunk()

	/**
	 * Split normalised text into its word tokens.
	 *
	 * @param string $text The normalised text.
	 *
	 * @return array<int, string> The word tokens.
	 */
	private function words(string $text): array {
		if ($text === '') {
			return [];
		}

		return explode(' ', $text);
	}//end words()

	/**
	 * Consume the run of consecutive `equal` ops starting at the cursor.
	 *
	 * @param array<int, array<string, mixed>> $ops The op list.
	 * @param int $cursor Index of the first op of the run.
	 *
	 * @return array{text: string, cursor: int} The joined text and the index
	 *                                          just past the run.
	 */
	private function takeEqualRun(array $ops, int $cursor): array {
		$count = count($ops);
		$words = [];
		while ($cursor < $count && $ops[$cursor]['op'] === 'equal') {
			$words[] = $ops[$cursor]['left'];
			$cursor++;
		}

		return [
			'text' => implode(' ', $words),
			'cursor' => $cursor,
		];

	}//end takeEqualRun()

	/**
	 * Consume the run of consecutive non-`equal` ops starting at the cursor.
	 *
	 * @param array<int, array<string, mixed>> $ops The op list.
	 * @param int $cursor Index of the first op of the run.
	 *
	 * @return array{removed: array<int, string>, added: array<int, string>, cursor: int}
	 */
	private function takeChangeRun(array $ops, int $cursor): array {
		$count = count($ops);
		$removed = [];
		$added = [];
		while ($cursor < $count && $ops[$cursor]['op'] !== 'equal') {
			if ($ops[$cursor]['op'] === 'delete') {
				$removed[] = $ops[$cursor]['left'];
			} elseif ($ops[$cursor]['op'] === 'insert') {
				$added[] = $ops[$cursor]['right'];
			}

			$cursor++;
		}

		return [
			'removed' => $removed,
			'added' => $added,
			'cursor' => $cursor,
		];

	}//end takeChangeRun()

	/**
	 * Build an `unchanged` hunk for a run of equal words and move both offsets past it.
	 *
	 * @param string $text The run text.
	 * @param HunkWalk $offsets The current byte offsets.
	 *
	 * @return array<string, mixed> The hunk.
	 */
	private function unchangedHunk(string $text, HunkWalk $offsets): array {
		$length = strlen($text);

		$hunk = [
			'type' => 'unchanged',
			'left' => [
				'offset' => $offsets->left,
				'length' => $length,
			],
			'right' => [
				'offset' => $offsets->right,
				'length' => $length,
			],
			'leftText' => $text,
			'rightText' => $text,
		];
		$offsets->advance(left: ($length + 1), right: ($length + 1));

		return $hunk;

	}//end unchangedHunk()

	/**
	 * Build a changed/added/removed hunk and move the offsets past what it consumes.
	 *
	 * @param array{removed: array<int, string>, added: array<int, string>} $run The change run.
	 * @param HunkWalk $offsets The current byte offsets.
	 *
	 * @return array<string, mixed> The hunk.
	 */
	private function changeHunk(array $run, HunkWalk $offsets): array {
		$left = $this->changedSide(words: $run['removed'], offset: $offsets->left);
		$right = $this->changedSide(words: $run['added'], offset: $offsets->right);

		$hunk = ['type' => $this->hunkType(removed: $run['removed'], added: $run['added'])];
		$hunk['left'] = $left['span'];
		if ($left['span'] !== null) {
			$hunk['leftText'] = $left['text'];
		}

		$hunk['right'] = $right['span'];
		if ($right['span'] !== null) {
			$hunk['rightText'] = $right['text'];
		}

		$offsets->advance(left: $left['advance'], right: $right['advance']);

		return $hunk;

	}//end changeHunk()

	/**
	 * One side of a change hunk: its span, its text and how far it moves that side's offset.
	 *
	 * @param array<int, string> $words The side's words (none for an untouched side).
	 * @param int $offset The side's current byte offset.
	 *
	 * @return array{span: array{offset: int, length: int}|null, text: string, advance: int}
	 */
	private function changedSide(array $words, int $offset): array {
		if ($words === []) {
			return ['span' => null, 'text' => '', 'advance' => 0];
		}

		$text = implode(' ', $words);

		return [
			'span' => [
				'offset' => $offset,
				'length' => strlen($text),
			],
			'text' => $text,
			'advance' => (strlen($text) + 1),
		];

	}//end changedSide()

	/**
	 * Classify a change run as changed, added, or removed.
	 *
	 * @param array<int, string> $removed Removed words.
	 * @param array<int, string> $added Added words.
	 *
	 * @return string The hunk type.
	 */
	private function hunkType(array $removed, array $added): string {
		if ($removed !== [] && $added !== []) {
			return 'changed';
		}

		if ($added !== []) {
			return 'added';
		}

		return 'removed';
	}//end hunkType()

	/**
	 * Compute an LCS-based op list over two word arrays.
	 *
	 * @param array<int, string> $a Left words.
	 * @param array<int, string> $b Right words.
	 *
	 * @return array<int, array{op:string, left?:string, right?:string}> Ops.
	 */
	private function lcsDiff(array $a, array $b): array {
		return $this->backtrack(a: $a, b: $b);
	}//end lcsDiff()

	/**
	 * Build the LCS length table for two word arrays.
	 *
	 * @param array<int, string> $a Left words.
	 * @param array<int, string> $b Right words.
	 *
	 * @return array<int, array<int, int>> The suffix LCS length table.
	 */
	private function lcsTable(array $a, array $b): array {
		$n = count($a);
		$m = count($b);

		$lcs = $this->zeroTable(rows: ($n + 1), columns: ($m + 1));

		for ($i = ($n - 1); $i >= 0; $i--) {
			for ($j = ($m - 1); $j >= 0; $j--) {
				if ($a[$i] === $b[$j]) {
					$lcs[$i][$j] = ($lcs[($i + 1)][($j + 1)] + 1);
					continue;
				}

				$lcs[$i][$j] = max($lcs[($i + 1)][$j], $lcs[$i][($j + 1)]);
			}
		}

		return $lcs;
	}//end lcsTable()

	/**
	 * A table of zeros, one row per index.
	 *
	 * @param int $rows    How many rows.
	 * @param int $columns How many cells each row holds.
	 *
	 * @return array<int, array<int, int>> The table.
	 */
	private function zeroTable(int $rows, int $columns): array {
		$table = [];
		for ($i = 0; $i < $rows; $i++) {
			$table[$i] = array_fill(0, $columns, 0);
		}

		return $table;
	}//end zeroTable()

	/**
	 * Backtrack the LCS length table of two word lists into an ordered op list.
	 *
	 * @param array<int, string> $a Left words.
	 * @param array<int, string> $b Right words.
	 *
	 * @return array<int, array{op:string, left?:string, right?:string}> Ops.
	 */
	private function backtrack(array $a, array $b): array {
		$walk = new LcsWalk(a: $a, b: $b, lcs: $this->lcsTable(a: $a, b: $b));

		$ops = [];
		while ($walk->inBoth() === true) {
			$ops[] = $walk->step();
		}

		return array_merge(
			$ops,
			$this->trailing(words: $walk->leftRest(), op: 'delete'),
			$this->trailing(words: $walk->rightRest(), op: 'insert')
		);
	}//end backtrack()

	/**
	 * One op per word left over once the other side ran out: delete for the
	 * left side, insert for the right.
	 *
	 * @param array<int, string> $words The words left over.
	 * @param string             $op    'delete' or 'insert'.
	 *
	 * @return array<int, array{op:string, left?:string, right?:string}> Ops.
	 */
	private function trailing(array $words, string $op): array {
		$side = ['delete' => 'left', 'insert' => 'right'][$op];
		return array_map(static fn (string $word): array => ['op' => $op, $side => $word], $words);
	}//end trailing()
}//end class
