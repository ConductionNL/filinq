<?php

/**
 * Word Differ
 *
 * Computes the word-level structured diff behind the document comparison
 * response: a longest-common-subsequence over word tokens, backtracked into an
 * op list and coalesced into ordered hunks with left/right offsets.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Comparison
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/document-comparison/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Comparison;

/**
 * Computes a word-level structured diff coalesced into hunks.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Comparison
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
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
		$leftOffset = 0;
		$rightOffset = 0;
		$cursor = 0;
		$count = count($ops);

		while ($cursor < $count) {
			// Coalesce consecutive ops of the same logical hunk type.
			if ($ops[$cursor]['op'] === 'equal') {
				$run = $this->takeEqualRun(ops: $ops, cursor: $cursor);
				$cursor = $run['cursor'];

				$hunks[] = $this->unchangedHunk(
					text: $run['text'],
					leftOffset: $leftOffset,
					rightOffset: $rightOffset
				);
				$advance = (strlen($run['text']) + 1);
				$leftOffset += $advance;
				$rightOffset += $advance;
				continue;
			}

			// A change block = a run of deletes and/or inserts.
			$run = $this->takeChangeRun(ops: $ops, cursor: $cursor);
			$cursor = $run['cursor'];

			$built = $this->changeHunk(
				removed: $run['removed'],
				added: $run['added'],
				leftOffset: $leftOffset,
				rightOffset: $rightOffset
			);
			$hunks[] = $built['hunk'];
			$leftOffset += $built['leftAdvance'];
			$rightOffset += $built['rightAdvance'];
		}//end while

		return $hunks;
	}//end diff()

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
	 * Build an `unchanged` hunk for a run of equal words.
	 *
	 * @param string $text The run text.
	 * @param int $leftOffset Current left byte offset.
	 * @param int $rightOffset Current right byte offset.
	 *
	 * @return array<string, mixed> The hunk.
	 */
	private function unchangedHunk(string $text, int $leftOffset, int $rightOffset): array {
		$length = strlen($text);

		return [
			'type' => 'unchanged',
			'left' => [
				'offset' => $leftOffset,
				'length' => $length,
			],
			'right' => [
				'offset' => $rightOffset,
				'length' => $length,
			],
			'leftText' => $text,
			'rightText' => $text,
		];

	}//end unchangedHunk()

	/**
	 * Build a changed/added/removed hunk plus the offset advances it consumes.
	 *
	 * @param array<int, string> $removed Removed words.
	 * @param array<int, string> $added Added words.
	 * @param int $leftOffset Current left byte offset.
	 * @param int $rightOffset Current right byte offset.
	 *
	 * @return array{hunk: array<string, mixed>, leftAdvance: int, rightAdvance: int}
	 */
	private function changeHunk(array $removed, array $added, int $leftOffset, int $rightOffset): array {
		$removedText = implode(' ', $removed);
		$addedText = implode(' ', $added);

		$hunk = ['type' => $this->hunkType(removed: $removed, added: $added)];
		$leftAdvance = 0;
		$rightAdvance = 0;

		$hunk['left'] = null;
		if ($removed !== []) {
			$hunk['left'] = [
				'offset' => $leftOffset,
				'length' => strlen($removedText),
			];
			$hunk['leftText'] = $removedText;
			$leftAdvance = (strlen($removedText) + 1);
		}

		$hunk['right'] = null;
		if ($added !== []) {
			$hunk['right'] = [
				'offset' => $rightOffset,
				'length' => strlen($addedText),
			];
			$hunk['rightText'] = $addedText;
			$rightAdvance = (strlen($addedText) + 1);
		}

		return [
			'hunk' => $hunk,
			'leftAdvance' => $leftAdvance,
			'rightAdvance' => $rightAdvance,
		];

	}//end changeHunk()

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
		return $this->backtrack(a: $a, b: $b, lcs: $this->lcsTable(a: $a, b: $b));
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

		$lcs = [];
		for ($i = 0; $i <= $n; $i++) {
			$lcs[$i] = array_fill(0, ($m + 1), 0);
		}

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
	 * Backtrack an LCS length table into an ordered op list.
	 *
	 * @param array<int, string> $a Left words.
	 * @param array<int, string> $b Right words.
	 * @param array<int, array<int, int>> $lcs The LCS length table.
	 *
	 * @return array<int, array{op:string, left?:string, right?:string}> Ops.
	 */
	private function backtrack(array $a, array $b, array $lcs): array {
		$n = count($a);
		$m = count($b);

		$ops = [];
		$leftPos = 0;
		$rightPos = 0;
		while ($leftPos < $n && $rightPos < $m) {
			if ($a[$leftPos] === $b[$rightPos]) {
				$ops[] = [
					'op' => 'equal',
					'left' => $a[$leftPos],
					'right' => $b[$rightPos],
				];
				$leftPos++;
				$rightPos++;
				continue;
			}

			if ($lcs[($leftPos + 1)][$rightPos] >= $lcs[$leftPos][($rightPos + 1)]) {
				$ops[] = [
					'op' => 'delete',
					'left' => $a[$leftPos],
				];
				$leftPos++;
				continue;
			}

			$ops[] = [
				'op' => 'insert',
				'right' => $b[$rightPos],
			];
			$rightPos++;
		}//end while

		while ($leftPos < $n) {
			$ops[] = [
				'op' => 'delete',
				'left' => $a[$leftPos],
			];
			$leftPos++;
		}

		while ($rightPos < $m) {
			$ops[] = [
				'op' => 'insert',
				'right' => $b[$rightPos],
			];
			$rightPos++;
		}

		return $ops;
	}//end backtrack()
}//end class
