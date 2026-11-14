<?php

/**
 * Contract of CorrespondenceService
 *
 * The public surface of the CorrespondenceService service, so callers in other
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
 * Contract of CorrespondenceService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface CorrespondenceServiceInterface {
	/**
	 * Generate a single correspondence document
	 *
	 * Fetches the template, resolves recipient data, applies huisstijl,
	 * renders the template, produces the output, and logs to the register.
	 *
	 * @param string $templateId The UUID of the template to use
	 * @param array $dataRefs Array of data references with register/schema/id
	 * @param array $options Options: format (pdf|docx|html|email),
	 *                       huisstijlId, caseReference, recipientType, userId
	 *
	 * @return array{content: string, format: string, warnings: array, registerEntry: array}
	 *
	 * @throws Exception If generation fails
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-correspondence-generation-api
	 * @spec openspec/changes/filinq-mcp-adoption/tasks.md#task-2-1
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-007
	 */
	public function generate(string $templateId, array $dataRefs, array $options = []): array;

	/**
	 * Generate correspondence for a batch of recipients
	 *
	 * For batches of 10 or fewer, generation is synchronous.
	 * For larger batches, a background job is dispatched.
	 *
	 * @param string $templateId The UUID of the template to use
	 * @param array $recipientIds Array of recipient object UUIDs
	 * @param array $options Options: format, register, schema, huisstijlId,
	 *                       caseReference, recipientType, userId
	 *
	 * @return array Synchronous results or job info
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
	 */
	public function generateBatch(string $templateId, array $recipientIds, array $options = []): array;

	/**
	 * Get the status of a batch job
	 *
	 * @param string $jobId The job UUID
	 *
	 * @return array|null The job status or null if not found
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-rest-endpoints
	 */
	public function getJobStatus(string $jobId): ?array;

	/**
	 * Update the status of a batch job
	 *
	 * @param string $jobId The job UUID
	 * @param array $data The status data to store
	 *
	 * @return void
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
	 */
	public function storeJobStatus(string $jobId, array $data): void;
}//end interface
