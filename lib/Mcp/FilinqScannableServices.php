<?php

/**
 * Filinq scannable-services opt-in (ADR-063 chain 3/3).
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Declares which of Filinq's own service classes OpenRegister's
 * `AttributeToolScanner` may reflect for `#[McpTool]`-attributed methods.
 * Registered under the `OCA\OpenRegister\Mcp\IMcpScannableServices::filinq`
 * DI alias in `RegistrationBootstrap`.
 *
 * Filinq ships NO hand-written `IMcpToolProvider`: every read is served by
 * OpenRegister's schema-derived tools from the `x-openregister-mcp` blocks in
 * `lib/Settings/filinq_register.json`, and the curated non-CRUD operations
 * are `#[McpTool]`-attributed methods on the classes listed here.
 *
 * The list is deliberately short. Filinq holds document content, signature
 * material and citizen contact data, and it can produce legally binding
 * artefacts, so what is absent matters as much as what is present: nothing
 * under `Service/Signing/`, no `CorrespondenceService::generateBatch()`, and
 * of `SigningService` only `createRequest()` (as `startSigningRequest`) --
 * applying, declining, cancelling or verifying a signature is an act with
 * legal effect and stays a human action, while initiating a request is a
 * write Hermiq holds for human approval. A mail-merge over N recipients is a
 * bulk personal-data operation.
 *
 * @category  Mcp
 * @package   OCA\Filinq\Mcp
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/filinq-mcp-adoption/tasks.md#task-2-2
 */

declare(strict_types=1);

namespace OCA\Filinq\Mcp;

use OCA\Filinq\Service\CorrespondenceService;
use OCA\Filinq\Service\Editing\DocumentAgentService;
use OCA\Filinq\Service\SigningService;
use OCA\OpenRegister\Mcp\IMcpScannableServices;

/**
 * Lists Filinq's `#[McpTool]`-attributed service classes.
 *
 * @category Mcp
 * @package  OCA\Filinq\Mcp
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/filinq-mcp-surface/spec.md#requirement-filinq-exposes-one-curated-document-generation-tool
 * @spec openspec/specs/filinq-mcp-surface/spec.md#requirement-signing-is-never-agent-writable
 */
class FilinqScannableServices implements IMcpScannableServices {

	/**
	 * The service classes OpenRegister may reflect for `#[McpTool]` methods.
	 *
	 * `CorrespondenceService` carries `generateCorrespondence`;
	 * `DocumentAgentService` carries the document read, edit, convert, status
	 * and anonymisation tools; `SigningService` carries `startSigningRequest`
	 * and nothing else. Initiating a request is the one way into the signing
	 * domain, behind Hermiq's approval gate: the signing act itself (sign,
	 * decline, bulk sign, cancel, verify) is never exposed.
	 *
	 * @return list<class-string> The scannable classes.
	 *
	 * @spec openspec/specs/filinq-mcp-surface/spec.md#requirement-filinq-exposes-one-curated-document-generation-tool
	 * @spec openspec/changes/hermiq-ai-tooling/specs/filinq-mcp-surface/spec.md#requirement-an-approval-gated-signing-request-initiation-tool-exists-req-ddhat-001
	 */
	public function getScannableServiceClasses(): array {
		return [
			CorrespondenceService::class,
			DocumentAgentService::class,
			SigningService::class,
		];

	}//end getScannableServiceClasses()
}//end class
