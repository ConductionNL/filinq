<?php

/**
 * DocuDesk SpreadsheetFamilyCodec
 *
 * One spreadsheet family's answer to "read the cells" and "write this one".
 *
 * ODS keeps values in `content.xml` with a repeat-count shorthand for runs of
 * columns; XLSX splits them across a worksheet part and a shared string table.
 * The shared guards — formula intent, the cell bound, stale dependents — belong
 * to neither and live in {@see SpreadsheetCodec}.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://docudesk.app
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#11
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Editing;

/**
 * Reads and writes cells for one spreadsheet family.
 */
interface SpreadsheetFamilyCodec {

	/**
	 * Whether this codec handles an extension.
	 *
	 * @param string $extension The lower-case file extension.
	 *
	 * @return bool True when handled.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#12
	 */
	public function supports(string $extension): bool;

	/**
	 * The package part carrying cell values.
	 *
	 * @return string The part path.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#12
	 */
	public function valuePart(): string;

	/**
	 * Read every populated cell.
	 *
	 * @param string $xml          The value part.
	 * @param string $packageBytes The whole package, for families that indirect.
	 *
	 * @return array<int, array{cell: string, value: string, formula: string|null}> The cells.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#12
	 */
	public function readCells(string $xml, string $packageBytes): array;

	/**
	 * Write a literal into one cell.
	 *
	 * @param string $xml   The value part.
	 * @param string $cell  The normalised `Sheet!Cell` address.
	 * @param string $value The literal.
	 *
	 * @return string The rewritten part.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#12
	 */
	public function writeCell(string $xml, string $cell, string $value): string;
}//end interface
