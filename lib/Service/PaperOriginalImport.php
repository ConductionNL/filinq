<?php

/**
 * Paper original import
 *
 * Registers the paper originals of a digitisation project from a CSV box
 * list. The columns map one to one onto `paperOriginal` (`box`, `shelfMark`,
 * `description`, `dateFrom`, `dateTo`), every row is saved through
 * OpenRegister's object API as the person importing, and a row that cannot be
 * saved is listed with its reason instead of stopping the import.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-an-archivist-runs-a-digitisation-project-req-pad-001
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use InvalidArgumentException;
use OCA\Filinq\Kernel\Contracts\PaperOriginalImportInterface;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use Throwable;

/**
 * Import a CSV box list as paper originals of one project (design D5).
 *
 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-an-archivist-runs-a-digitisation-project-req-pad-001
 */
class PaperOriginalImport implements PaperOriginalImportInterface {

	public const REGISTER = 'filinq';

	public const SCHEMA = 'paperOriginal';

	/**
	 * The columns a box list carries; `box` and `shelfMark` are required.
	 */
	public const COLUMNS = ['box', 'shelfMark', 'description', 'dateFrom', 'dateTo'];

	/**
	 * The most rows one import takes.
	 */
	public const MAX_ROWS = 5000;

	/**
	 * Constructor.
	 *
	 * @param ObjectServiceInterface $objectService OpenRegister's object API.
	 */
	public function __construct(
		private readonly ObjectServiceInterface $objectService,
	) {
	}//end __construct()

	/**
	 * Import a CSV box list into a project.
	 *
	 * The first line names the columns. A row without a box or a shelf mark,
	 * with a different number of cells than the header, or that OpenRegister
	 * refuses, is listed in `failures` with its line number and reason; the
	 * other rows are saved as `registered` originals of the project.
	 *
	 * @param string $projectId The digitisationProject the originals belong to.
	 * @param string $csv       The box list.
	 *
	 * @return array{imported: int, failures: array<int, array{line: int, reason: string}>}
	 *
	 * @throws InvalidArgumentException When the list has no header with the required columns.
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/tasks.md#task-3.2
	 */
	public function import(string $projectId, string $csv): array {
		$projectId = trim($projectId);
		if ($projectId === '') {
			throw new InvalidArgumentException('An import names the project the originals belong to.');
		}

		$lines = self::lines(csv: $csv);
		if ($lines === []) {
			throw new InvalidArgumentException('The box list is empty.');
		}

		$header = self::header(cells: self::cells(line: array_shift($lines)['text']));
		if (count($lines) > self::MAX_ROWS) {
			throw new InvalidArgumentException(sprintf('A box list holds at most %d rows.', self::MAX_ROWS));
		}

		$imported = 0;
		$failures = [];
		foreach ($lines as ['number' => $number, 'text' => $text]) {
			$cells = self::cells(line: $text);
			if (count($cells) !== count($header)) {
				$failures[] = ['line' => $number, 'reason' => sprintf('expected %d columns, found %d', count($header), count($cells))];
				continue;
			}

			$row = array_combine($header, $cells);
			$reason = self::missing(row: $row);
			if ($reason !== null) {
				$failures[] = ['line' => $number, 'reason' => $reason];
				continue;
			}

			try {
				$this->objectService->saveObject(
					object: self::original(projectId: $projectId, row: $row),
					options: new ObjectSaveOptions(target: new ObjectFindTarget(register: self::REGISTER, schema: self::SCHEMA))
				);
				$imported++;
			} catch (Throwable $e) {
				$failures[] = ['line' => $number, 'reason' => $e->getMessage()];
			}
		}

		return ['imported' => $imported, 'failures' => $failures];

	}//end import()

	/**
	 * The non-empty lines of the list with their 1-based line numbers.
	 *
	 * @param string $csv The box list.
	 *
	 * @return array<int, array{number: int, text: string}>
	 */
	private static function lines(string $csv): array {
		$csv = (string)preg_replace('/^\xEF\xBB\xBF/', '', $csv);
		$split = preg_split('/\r\n|\n|\r/', $csv);
		if ($split === false) {
			$split = [];
		}

		$lines = [];
		foreach ($split as $index => $text) {
			if (trim($text) !== '') {
				$lines[] = ['number' => ($index + 1), 'text' => $text];
			}
		}

		return $lines;

	}//end lines()

	/**
	 * The cells of one line, split on a semicolon when the line has one and on
	 * a comma otherwise (Dutch spreadsheets write semicolons).
	 *
	 * @param string $line One line of the list.
	 *
	 * @return array<int, string>
	 */
	private static function cells(string $line): array {
		$separator = ',';
		if (str_contains($line, ';') === true) {
			$separator = ';';
		}

		return array_map(
			static fn (?string $cell): string => trim((string)$cell),
			str_getcsv($line, $separator, '"', '')
		);

	}//end cells()

	/**
	 * The header's column names, checked against the known columns.
	 *
	 * @param array<int, string> $cells The header cells.
	 *
	 * @return array<int, string>
	 *
	 * @throws InvalidArgumentException When a required column is missing or a column is unknown.
	 */
	private static function header(array $cells): array {
		$known = array_combine(array_map('strtolower', self::COLUMNS), self::COLUMNS);
		$header = [];
		foreach ($cells as $cell) {
			$column = ($known[strtolower($cell)] ?? null);
			if ($column === null) {
				throw new InvalidArgumentException(sprintf('Unknown column "%s"; a box list has the columns %s.', $cell, implode(', ', self::COLUMNS)));
			}

			$header[] = $column;
		}

		foreach (['box', 'shelfMark'] as $required) {
			if (in_array($required, $header, true) === false) {
				throw new InvalidArgumentException(sprintf('The box list has no "%s" column.', $required));
			}
		}

		return $header;

	}//end header()

	/**
	 * Why a row cannot be imported, or null when it can.
	 *
	 * @param array<string, string> $row The row by column.
	 *
	 * @return string|null
	 */
	private static function missing(array $row): ?string {
		foreach (['box' => 'the box is empty', 'shelfMark' => 'the shelf mark is empty'] as $column => $reason) {
			if (($row[$column] ?? '') === '') {
				return $reason;
			}
		}

		return null;

	}//end missing()

	/**
	 * The paperOriginal a row registers.
	 *
	 * @param string                $projectId The project.
	 * @param array<string, string> $row       The row by column.
	 *
	 * @return array<string, mixed>
	 */
	private static function original(string $projectId, array $row): array {
		$original = [
			'project' => $projectId,
			'box' => $row['box'],
			'shelfMark' => $row['shelfMark'],
			'status' => 'registered',
		];

		if (($row['description'] ?? '') !== '') {
			$original['description'] = $row['description'];
		}

		$range = implode('-', array_filter([($row['dateFrom'] ?? ''), ($row['dateTo'] ?? '')], static fn (string $part): bool => $part !== ''));
		if ($range !== '') {
			$original['dateRange'] = $range;
		}

		return $original;

	}//end original()
}//end class
