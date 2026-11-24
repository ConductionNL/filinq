<?php

/**
 * Contract of FormDocumentService
 *
 * The public surface of the FormDocumentService service, so callers in other
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

use OCA\Filinq\Exception\FormDocumentRefused;

/**
 * Contract of FormDocumentService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface FormDocumentServiceInterface {
	/**
	 * Check a document reference a form field holds: it resolves and the caller may read it.
	 *
	 * @param array<string, mixed> $reference `{register?, schema?, id}`; register and schema default to filinq's document.
	 *
	 * @return array{register: string, schema: string, id: string, title: string} The reference, never the file.
	 *
	 * @throws FormDocumentRefused 400 malformed, 404 when it does not resolve or the caller may not read it.
	 *
	 * @spec openspec/changes/forms-as-documents/tasks.md#1-build
	 */
	public function checkReference(array $reference): array;

	/**
	 * Render a submitted form from the values as submitted and file the document on the record.
	 *
	 * @param array<string, mixed> $submission `{templateId, record: {register, schema, id}, values,
	 *                                         documentFields?, filename?, format?}`.
	 *
	 * @return array<string, mixed> The filed document: fileName, fileId, sha256, templateVersion,
	 *                              record and the checked references.
	 *
	 * @throws FormDocumentRefused 400 malformed, 404 for the record or a reference, 502 when filing fails.
	 *
	 * @spec openspec/changes/forms-as-documents/tasks.md#1-build
	 */
	public function renderSubmission(array $submission): array;
}//end interface
