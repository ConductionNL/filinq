<?php

/**
 * Contract of DocumentGenerationRequestService
 *
 * The public surface of the DocumentGenerationRequestService service, so callers in other
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
use RuntimeException;
use UnexpectedValueException;

/**
 * Contract of DocumentGenerationRequestService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface GenerationRequestServiceInterface {
	/**
	 * Refuse a request that cannot be carried out.
	 *
	 * Shared by the flow node's validateConfig() (run when a flow is saved)
	 * and by generate() (run for every request, because a flow imported from
	 * a declaration reaches execution unvalidated, and an event carries
	 * whatever the dispatcher built).
	 *
	 * @param array<string, mixed> $request The request or the node's configuration.
	 *
	 * @return void
	 *
	 * @throws UnexpectedValueException When the request is unusable.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-configuration-that-cannot-run-is-refused-when-the-flow-is-saved
	 */
	public function validate(array $request): void;

	/**
	 * Generate one document and announce it.
	 *
	 * Throws on every failure. The flow node lets it reach the engine, whose
	 * `onError` policy decides what happens next; the command listener turns
	 * it into the event's error slot. Nothing here catches and carries on,
	 * because a generation that reports success without a document is the
	 * failure the dossiq nodes this replaces were deleted for.
	 *
	 * @param array<string, mixed> $request       The request: a template (templateId, templateSlug +
	 *                                            templateNamespace [+ templateTenantId], or template
	 *                                            text [+ templateName]); data (the render context);
	 *                                            object ({register, schema, id}); format; storeFile;
	 *                                            targetField; targetPath; filename; huisstijlId;
	 *                                            metadata; userId.
	 * @param string               $requestingApp The app asking, echoed on DocumentGeneratedEvent.
	 *
	 * @return array<string, mixed> fileId, path, name, mime, size, format, template, object,
	 *                              targetField, metadata, requestingApp, warnings; plus `text`
	 *                              (the rendered template) when targetField was written.
	 *
	 * @throws UnexpectedValueException When the request is unusable.
	 * @throws RuntimeException When no user can own the file, or the field has no object to go to.
	 * @throws \Exception When the template cannot be found or rendering, storing or writing fails.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-one-generation-path-for-the-flow-node-the-command-event-and-the-api
	 */
	public function generate(array $request, string $requestingApp): array;
}//end interface
