<?php
/**
 * Portal Signing Document Resolver
 *
 * Resolves the target document's `File` node for the portal signing
 * receiver's `viewDocument` act, reading it from the signing request's
 * initiator's user folder.
 *
 * This class makes NO authorisation decision. The receiver's fail-closed
 * ordering — verify assertion (401) -> derive scope from claims (403) ->
 * validate input (403) -> authorise against the domain row (403, uniform) ->
 * act -> relay — lives entirely in
 * {@see \OCA\DocuDesk\Controller\PortalSigningReceiverController}, and this
 * resolver is only ever called once that ordering has already granted the
 * act. It was extracted from the controller so the trust boundary stays a
 * small, readable class; the resolution logic itself is unchanged.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCP\Files\File;
use OCP\Files\IRootFolder;
use Throwable;

/**
 * Resolves the signing request's target document node.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 */
class PortalSigningDocumentResolver
{

    /**
     * Constructor.
     *
     * @param IRootFolder $rootFolder Root folder (reads the target document).
     *
     * @return void
     */
    public function __construct(
        private readonly IRootFolder $rootFolder
    ) {

    }//end __construct()

    /**
     * Resolve the target document's File node for viewDocument.
     *
     * Returns null — never throws — when the request carries no usable file
     * reference or the node cannot be read, so the caller can answer
     * `document_unavailable` without leaking storage internals.
     *
     * @param array<string, mixed> $signingRequest The resolved signing request.
     *
     * @return File|null The resolved file node, or null when unavailable.
     *
     * @spec openspec/specs/portal-signing-actions/spec.md
     */
    public function resolve(array $signingRequest): ?File
    {
        $fileId    = (int) ($signingRequest['documentFileId'] ?? 0);
        $initiator = (string) ($signingRequest['initiatorUserId'] ?? '');
        if ($fileId <= 0 || $initiator === '') {
            return null;
        }

        try {
            $nodes = $this->rootFolder->getUserFolder($initiator)->getById($fileId);
        } catch (Throwable $e) {
            return null;
        }

        foreach ($nodes as $node) {
            if ($node instanceof File) {
                return $node;
            }
        }

        return null;

    }//end resolve()
}//end class
