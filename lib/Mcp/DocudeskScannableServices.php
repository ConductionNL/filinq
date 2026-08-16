<?php

/**
 * DocuDesk scannable-services opt-in (ADR-063 chain 3/3).
 *
 * Declares which of DocuDesk's own service classes OpenRegister's
 * `AttributeToolScanner` may reflect for `#[McpTool]`-attributed methods.
 * Registered under the `OCA\OpenRegister\Mcp\IMcpScannableServices::docudesk`
 * DI alias in `RegistrationBootstrap`.
 *
 * DocuDesk ships NO hand-written `IMcpToolProvider`: every read is served by
 * OpenRegister's schema-derived tools from the `x-openregister-mcp` blocks in
 * `lib/Settings/docudesk_register.json`, and the curated non-CRUD operations
 * are `#[McpTool]`-attributed methods on the classes listed here.
 *
 * The list is deliberately short. DocuDesk holds document content, signature
 * material and citizen contact data, and it can produce legally binding
 * artefacts, so what is absent matters as much as what is present: nothing
 * under `Service/Signing/`, no `SigningService`, no
 * `CorrespondenceService::generateBatch()` -- applying a signature is an act
 * with legal effect and a mail-merge over N recipients is a bulk personal-data
 * operation.
 *
 * @category  Mcp
 * @package   OCA\DocuDesk\Mcp
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/docudesk-mcp-adoption/tasks.md#task-2-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Mcp;

use OCA\DocuDesk\Service\CorrespondenceService;
use OCA\DocuDesk\Service\Editing\DocumentAgentService;
use OCA\OpenRegister\Mcp\IMcpScannableServices;

/**
 * Lists DocuDesk's `#[McpTool]`-attributed service classes.
 *
 * @category Mcp
 * @package  OCA\DocuDesk\Mcp
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/docudesk-mcp-surface/spec.md#requirement-docudesk-exposes-one-curated-document-generation-tool
 * @spec openspec/specs/docudesk-mcp-surface/spec.md#requirement-signing-is-never-agent-writable
 */
class DocudeskScannableServices implements IMcpScannableServices {

	/**
	 * The service classes OpenRegister may reflect for `#[McpTool]` methods.
	 *
	 * `CorrespondenceService` carries `generateCorrespondence`;
	 * `DocumentAgentService` carries `readDocument`, `editDocument` and
	 * `convertDocumentToPdf`.
	 *
	 * @return list<class-string> The scannable classes.
	 *
	 * @spec openspec/specs/docudesk-mcp-surface/spec.md#requirement-docudesk-exposes-one-curated-document-generation-tool
	 */
	public function getScannableServiceClasses(): array {
		return [
			CorrespondenceService::class,
			DocumentAgentService::class,
		];

	}//end getScannableServiceClasses()
}//end class
