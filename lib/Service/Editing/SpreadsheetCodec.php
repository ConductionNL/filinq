<?php

/**
 * Filinq SpreadsheetCodec
 *
 * Reads and edits spreadsheet cells addressed as `Sheet!Cell`.
 *
 * 🔑 A spreadsheet has no anchor problem. A cell address IS a durable identity:
 * when a human inserts a row, everything below shifts in a way the file format
 * and the user's mental model already agree on. So none of the block-anchor
 * machinery the text codec needs applies here, and inventing it would be worse
 * than useless — an anchor derived from cell CONTENT would collide the moment
 * two cells both said "10".
 *
 * @category Service
 * @package  OCA\Filinq\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://filinq.app
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#1-codec-interface--spreadsheet
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Editing;

use RuntimeException;

/**
 * Cell-addressed reading and editing for ODS and XLSX packages.
 */
class SpreadsheetCodec {

	/**
	 * The most cells one call may write.
	 *
	 * ⚠️ Exceeding this REFUSES the call rather than truncating it. A truncated
	 * bulk write reports success for edits it never made, and the caller — a
	 * language model — has no way to tell which half landed.
	 *
	 * @var int
	 */
	public const MAX_CELLS_PER_CALL = 200;

	/**
	 * Spreadsheet error literals.
	 *
	 * A dependent already holding one of these is reported separately from an
	 * ordinary stale dependent: "this number no longer follows from its inputs"
	 * and "this cell is broken" are different things for a caller to hear, and
	 * an error value that persists silently through an edit reads as data.
	 *
	 * @var array<int, string>
	 */
	public const ERROR_VALUES = ['#REF!', '#DIV/0!', '#VALUE!', '#N/A', '#NAME?', '#NULL!', '#NUM!'];

	/**
	 * The per-family codecs, tried in order.
	 *
	 * @var array<int, SpreadsheetFamilyCodec>
	 */
	private array $families;

	/**
	 * Constructor.
	 *
	 * @param PackagePartIo                         $io       Package reader/writer.
	 * @param array<int, SpreadsheetFamilyCodec>|null $families Optional override, for tests.
	 */
	public function __construct(
		private readonly PackagePartIo $io,
		?array $families = null,
	) {
		$this->families = ($families ?? [new OdsSpreadsheetCodec(), new XlsxSpreadsheetCodec($io)]);
	}//end __construct()

	/**
	 * Whether this codec handles an extension.
	 *
	 * @param string $extension The lower-case file extension.
	 *
	 * @return bool True when handled.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function supports(string $extension): bool {
		return ($this->codecFor(extension: $extension) !== null);
	}//end supports()

	/**
	 * Read every populated cell, addressed as `Sheet!Cell`.
	 *
	 * @param string $packageBytes The package.
	 * @param string $extension    The file extension.
	 *
	 * @return array<int, array{cell: string, value: string, formula: string|null}> The cells.
	 *
	 * @throws RuntimeException When the extension is not a spreadsheet.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function readCells(string $packageBytes, string $extension): array {
		$codec = $this->requireCodec(extension: $extension);
		$xml = $this->io->readPart(packageBytes: $packageBytes, part: $codec->valuePart());

		return $codec->readCells(xml: $xml, packageBytes: $packageBytes);
	}//end readCells()

	/**
	 * Write literal values into addressed cells.
	 *
	 * The GUARDS live here rather than in each family: they are policy, not
	 * file format, and two copies of "may this write proceed" is how one family
	 * ends up permitting what the other refuses.
	 *
	 * @param string $packageBytes The package.
	 * @param string $extension    The file extension.
	 * @param array  $edits        Each `{cell, value, replaceFormula?}`.
	 *
	 * @return array{bytes: string, applied: array<int, string>, staleDependents: array<int, string>, erroredDependents: array<int, string>} The result.
	 *
	 * @throws RuntimeException When an edit is refused.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function applyCellEdits(string $packageBytes, string $extension, array $edits): array {
		if ($edits === []) {
			throw new RuntimeException('At least one cell edit is required.');
		}

		if (count($edits) > self::MAX_CELLS_PER_CALL) {
			throw new RuntimeException(
				sprintf(
					'This call writes %d cells; the limit is %d. The call is refused rather than '
					. 'truncated — a partial bulk write reports success for edits it never made.',
					count($edits),
					self::MAX_CELLS_PER_CALL
				)
			);
		}

		$codec = $this->requireCodec(extension: $extension);
		$xml = $this->io->readPart(packageBytes: $packageBytes, part: $codec->valuePart());
		$existing = $this->indexByCell(cells: $this->readCells(packageBytes: $packageBytes, extension: $extension));

		$applied = [];
		foreach ($edits as $position => $edit) {
			$cell = $this->normaliseAddress(address: (string)($edit['cell'] ?? ''));
			if ($cell === '') {
				throw new RuntimeException(sprintf('Edit %d: a cell address is required.', ((int)$position + 1)));
			}

			$this->assertFormulaIntent(cell: $cell, edit: $edit, existing: $existing, position: (int)$position);

			$xml = $codec->writeCell(xml: $xml, cell: $cell, value: (string)($edit['value'] ?? ''));
			$applied[] = $cell;
		}//end foreach

		$stale = $this->staleDependents(existing: $existing, written: $applied);

		return [
			'bytes' => $this->io->writePart(packageBytes: $packageBytes, part: $codec->valuePart(), xml: $xml),
			'applied' => $applied,
			'staleDependents' => $stale,
			'erroredDependents' => $this->erroredDependents(existing: $existing, stale: $stale),
		];
	}//end applyCellEdits()

	/**
	 * The codec for an extension, or null.
	 *
	 * @param string $extension The file extension.
	 *
	 * @return SpreadsheetFamilyCodec|null The codec, or null when unhandled.
	 */
	private function codecFor(string $extension): ?SpreadsheetFamilyCodec {
		foreach ($this->families as $codec) {
			if ($codec->supports(extension: $extension) === true) {
				return $codec;
			}
		}

		return null;
	}//end codecFor()

	/**
	 * The codec for an extension, refusing by name when absent.
	 *
	 * @param string $extension The file extension.
	 *
	 * @return SpreadsheetFamilyCodec The codec.
	 *
	 * @throws RuntimeException When unsupported.
	 */
	private function requireCodec(string $extension): SpreadsheetFamilyCodec {
		$codec = $this->codecFor(extension: $extension);
		if ($codec === null) {
			throw new RuntimeException(
				sprintf('"%s" is not a spreadsheet this codec edits. Supported: ods, xlsx.', $extension)
			);
		}

		return $codec;
	}//end requireCodec()

	/**
	 * Refuse a literal write over a formula without per-cell intent.
	 *
	 * 🔴 The intent must be given FOR THAT CELL. A per-call or global flag is
	 * not acceptable: a bulk write would carry one cell's permission across
	 * every other cell in the same call, and the formula the caller never
	 * looked at is exactly the one that gets destroyed.
	 *
	 * @param string $cell     The cell address.
	 * @param array  $edit     The edit.
	 * @param array  $existing Cells indexed by address.
	 * @param int    $position The edit's position, for the message.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the cell holds a formula and intent is absent.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.3
	 */
	private function assertFormulaIntent(string $cell, array $edit, array $existing, int $position): void {
		$formula = ($existing[$cell]['formula'] ?? null);
		if ($formula === null) {
			return;
		}

		if (($edit['replaceFormula'] ?? false) === true) {
			return;
		}

		throw new RuntimeException(
			sprintf(
				'Edit %d: %s holds the formula `%s`. Writing a literal would destroy it. '
				. 'Set `replaceFormula: true` ON THIS EDIT if that is what you mean — the flag is '
				. 'per cell, so it cannot be granted once and carried across a bulk write.',
				($position + 1),
				$cell,
				$formula
			)
		);
	}//end assertFormulaIntent()

	/**
	 * Cells whose cached value is now stale because a cell they reference changed.
	 *
	 * ⚠️ Reported, never recalculated. Recalculation needs a formula engine this
	 * app does not have, and a cached value left in place is a number that
	 * LOOKS current and is not. Saying which cells went stale is honest; quietly
	 * leaving them is the failure this reporting exists to prevent.
	 *
	 * @param array<string, array{cell: string, value: string, formula: string|null}> $existing Cells by address.
	 * @param array<int, string>                                                      $written  Addresses written.
	 *
	 * @return array<int, string> Addresses whose cached values no longer follow.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.4
	 */
	private function staleDependents(array $existing, array $written): array {
		$stale = [];
		$writtenSet = array_flip($written);

		foreach ($existing as $address => $cell) {
			if ($cell['formula'] === null || isset($writtenSet[$address]) === true) {
				continue;
			}

			foreach ($written as $target) {
				$bare = $this->bareRef(cell: $target);
				if (preg_match('/(?<![A-Z0-9$])' . preg_quote($bare, '/') . '(?![0-9])/i', $cell['formula']) === 1) {
					$stale[] = $address;
					break;
				}
			}
		}

		return $stale;
	}//end staleDependents()

	/**
	 * Dependents whose cached value is a spreadsheet ERROR.
	 *
	 * ⚠️ Reported separately, and never repaired. A `#REF!` or `#DIV/0!` sitting
	 * behind a changed input is not merely out of date — it is a cell the sheet
	 * itself could not compute, and letting it persist through an edit without
	 * a word makes it look like content.
	 *
	 * @param array<string, array{cell: string, value: string, formula: string|null}> $existing Cells by address.
	 * @param array<int, string>                                                      $stale    Stale addresses.
	 *
	 * @return array<int, string> Addresses whose cached value is an error literal.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-4.2
	 */
	private function erroredDependents(array $existing, array $stale): array {
		$errored = [];
		foreach ($stale as $address) {
			$value = trim((string)($existing[$address]['value'] ?? ''));
			if (in_array(strtoupper($value), self::ERROR_VALUES, true) === true) {
				$errored[] = $address;
			}
		}

		return $errored;
	}//end erroredDependents()

	/**
	 * The cell part of a `Sheet!Cell` address.
	 *
	 * @param string $cell The address.
	 *
	 * @return string The bare cell reference.
	 */
	private function bareRef(string $cell): string {
		$parts = explode('!', $cell);

		return end($parts);
	}//end bareRef()

	/**
	 * Index cells by their address.
	 *
	 * @param array<int, array{cell: string, value: string, formula: string|null}> $cells The cells.
	 *
	 * @return array<string, array{cell: string, value: string, formula: string|null}> Indexed cells.
	 */
	private function indexByCell(array $cells): array {
		$indexed = [];
		foreach ($cells as $cell) {
			$indexed[$this->normaliseAddress(address: $cell['cell'])] = $cell;
		}

		return $indexed;
	}//end indexByCell()

	/**
	 * Normalise an address for comparison.
	 *
	 * ⚠️ Only the CELL REFERENCE is upper-cased. A sheet name is case-sensitive
	 * in every spreadsheet application, so upper-casing the whole address turns
	 * `Sales!b2` into `SALES!B2` and it matches no sheet at all — the write then
	 * fails on a document where the sheet plainly exists.
	 *
	 * @param string $address The `Sheet!Cell` address.
	 *
	 * @return string The normalised address.
	 */
	private function normaliseAddress(string $address): string {
		$position = strrpos($address, '!');
		if ($position === false) {
			return strtoupper($address);
		}

		return substr($address, 0, $position) . '!' . strtoupper(substr($address, ($position + 1)));
	}//end normaliseAddress()
}//end class
