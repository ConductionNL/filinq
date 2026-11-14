<?php

/**
 * The state of one document while an erasure run processes it.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SubjectErasure
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-3.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SubjectErasure;

/**
 * Run object of {@see SubjectErasureDocumentStep::process()}: the document, the
 * values to take out, who asked, and the result as it is built. It is created
 * and read by that step only.
 *
 * @spec openspec/specs/anonymization-link/spec.md
 */
final class DocumentErasureRun {

	/**
	 * The document's file id.
	 *
	 * @var int
	 */
	public int $fileId = 0;

	/**
	 * The assessed document.
	 *
	 * @var array<string, mixed>
	 */
	public array $document = [];

	/**
	 * The values to take out of it.
	 *
	 * @var array<int, string>
	 */
	public array $values = [];

	/**
	 * The erasure request.
	 *
	 * @var string
	 */
	public string $requestUuid = '';

	/**
	 * The caller.
	 *
	 * @var string
	 */
	public string $userId = '';

	/**
	 * The result so far.
	 *
	 * @var array<string, mixed>
	 */
	public array $result = [];
}//end class
