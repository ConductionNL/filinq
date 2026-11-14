<?php

/**
 * Contract of MultiFormatOutputProducer
 *
 * The public surface of the MultiFormatOutputProducer service, so callers in other
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

use Exception;

/**
 * Contract of MultiFormatOutputProducer.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface MultiFormatOutputProducerInterface {
	/**
	 * The formats generation can make.
	 *
	 * @var string[]
	 */
	public const FORMATS = ['pdf', 'odf', 'docx', 'html'];

	/**
	 * Generate one document in several formats.
	 *
	 * @param string $templateId The template.
	 * @param array  $dataRefs   Data references: [{register, schema, id}, ...].
	 * @param array  $options    `formats` (required), `userId` (required), `filename`,
	 *                           `output.targetPath`, and what a single generation takes
	 *                           for the render (huisstijlId, adHocData, listRefs, pdfOptions, caseId).
	 *
	 * @return array{outputs: array<int, array<string, mixed>>, metadata: array, warnings: string[]}
	 *               One output per format: format, status (generated|failed), fileId,
	 *               fileName, downloadUrl, size, and error when it failed.
	 *
	 * @throws Exception 400 for a malformed request or a template with a plain-language
	 *                   counterpart; a failing render aborts the whole job.
	 *
	 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.4
	 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.6
	 */
	public function generate(string $templateId, array $dataRefs, array $options): array;

	/**
	 * The formats a request asks for, validated and deduplicated.
	 *
	 * @param array $options The generation options.
	 *
	 * @return string[] The formats, in the order asked.
	 *
	 * @throws Exception 400 when `formats` is combined with `format`, empty, not a list or names an unknown format.
	 *
	 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.4
	 */
	public function requestedFormats(array $options): array;
}//end interface
