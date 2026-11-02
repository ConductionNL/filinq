<?php

/**
 * Contract of ScanBatchService
 *
 * The public surface of the ScanBatchService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use OCP\Files\File;
use OCP\Files\Folder;

/**
 * Contract of ScanBatchService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ScanBatchServiceInterface {
	/**
	 * The schema holding the batches.
	 *
	 * @var string
	 */
	public const SCHEMA = 'scanBatch';

	/**
	 * Cut on the QR separator sheets filinq printed.
	 *
	 * @var string
	 */
	public const MODE_QR = 'qr';

	/**
	 * Cut on blank pages.
	 *
	 * @var string
	 */
	public const MODE_BLANK = 'blankPage';

	/**
	 * A batch as it arrived, not yet cut.
	 *
	 * @var string
	 */
	public const STATUS_RECEIVED = 'received';

	/**
	 * Cut into documents.
	 *
	 * @var string
	 */
	public const STATUS_SPLIT = 'split';

	/**
	 * Could not be cut, and delivered nothing.
	 *
	 * @var string
	 */
	public const STATUS_FAILED = 'failed';

	/**
	 * Take in one batch a scanner delivered.
	 *
	 * @param File $file The PDF the scanner wrote.
	 * @param array<string, mixed> $profile The scan profile whose folder it landed in.
	 *
	 * @return array<string, mixed> The stored batch, in `received`.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function receive(File $file, array $profile): array;

	/**
	 * Cut one batch into documents and announce every one of them.
	 *
	 * @param array<string, mixed> $batch The batch, as received.
	 * @param array<string, mixed> $profile The scan profile.
	 * @param File $file The batch file.
	 * @param Folder $target The folder the segments are written to.
	 *
	 * @return array<string, mixed> The batch, `split` or `failed`.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function split(array $batch, array $profile, File $file, Folder $target): array;

	/**
	 * Cut a list of pages into segments at the separators.
	 *
	 * A separator belongs to NEITHER side: it is a sheet of paper somebody put
	 * in the stack, not part of the document before it or after it. A run of
	 * separators with nothing between them produces no empty segment.
	 *
	 * @param array<int, string> $pages The pages, in order.
	 * @param string $mode The separator mode.
	 * @param array<string, mixed> $profile The scan profile.
	 *
	 * @return array<int, array<string, mixed>> The segments, each with its pages and the case its separator named.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function segmentsOf(array $pages, string $mode, array $profile): array;
}//end interface
