<?php

/**
 * Correspondence Request
 *
 * The template and request options one correspondence generation runs with.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
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

namespace OCA\Filinq\Service;

/**
 * The template ID and the options shared by every letter of one generation request.
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
 */
class CorrespondenceRequest {

	/**
	 * @param string               $templateId The UUID of the template
	 * @param array<string, mixed> $options    Generation options: format, register, schema, huisstijlId,
	 *                                         caseReference, recipientType, userId
	 */
	public function __construct(
		public readonly string $templateId,
		public readonly array $options,
	) {
	}//end __construct()
}//end class
