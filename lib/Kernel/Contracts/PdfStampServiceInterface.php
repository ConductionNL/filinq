<?php

/**
 * Contract of PdfStampService
 *
 * The public surface of PdfStampService that other components call, so they depend
 * on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction b.v. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://github.com/ConductionNL/filinq
 *
 * @spec openspec/specs/method-decomposition/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use OCA\Filinq\Exception\PdfStampRefusal;

/**
 * Contract of PdfStampService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction b.v. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://github.com/ConductionNL/filinq
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PdfStampServiceInterface {

	/**
	 * Stamp a text on every page of a PDF.
	 *
	 * @param string $pdf     The PDF bytes.
	 * @param string $text    Plain text, at most 200 characters, lines separated by a newline.
	 * @param array  $options placement: diagonal, footer or both (default both);
	 *                        opacity: 0-1 for the text across the page (default 0.15).
	 *
	 * @return string The stamped PDF bytes.
	 *
	 * @throws PdfStampRefusal When the file is not a PDF, encrypted, too large, or cannot be stamped.
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1.1
	 */
	public function stamp(string $pdf, string $text, array $options = []): string;
}//end interface
