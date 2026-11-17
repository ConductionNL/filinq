<?php

/**
 * The Service classes that carry `#[McpTool]` methods
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/filinq-mcp-surface/spec.md#requirement-filinq-exposes-one-curated-document-generation-tool
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Service\Editing\DocumentAgentService;

/**
 * The curated list Mcp\FilinqScannableServices hands to OpenRegister; see
 * that class for why each entry is (and what is not) on it.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/filinq-mcp-surface/spec.md#requirement-signing-is-never-agent-writable
 */
final class McpToolServices {

	/**
	 * The scannable classes, in the order OpenRegister reflects them.
	 *
	 * @var list<class-string>
	 */
	public const CLASSES = [
		CorrespondenceService::class,
		DocumentAgentService::class,
		FileListingService::class,
		AnonymizationService::class,
		SigningService::class,
	];
}//end class
