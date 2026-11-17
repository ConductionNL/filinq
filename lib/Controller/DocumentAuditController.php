<?php

/**
 * DocumentAuditController
 *
 * Serves the audit trail of one document (specify-capability-rows REQ-SCR-007).
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\Audit\DocumentActionAudit;
use OCA\Filinq\Service\ReadableFileResolver;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Serves `GET api/documents/{fileId}/audit`.
 *
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
 */
class DocumentAuditController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string               $appName App name.
	 * @param IRequest             $request Request object.
	 * @param DocumentActionAudit  $audit   The document trail.
	 * @param ReadableFileResolver $files   The file behind an id, as the reader may read it.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly DocumentActionAudit $audit,
		private readonly ReadableFileResolver $files,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * The trail of a document the caller may read, oldest first, each entry
	 * with its action, actor and moment. A file the caller may not read
	 * answers 404, exactly like one that does not exist.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return JSONResponse `{results, total}`, or 404.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
	 */
	#[NoAdminRequired]
	public function index(int $fileId): JSONResponse {
		if ($this->files->node(fileId: $fileId) === null) {
			return new JSONResponse(data: ['error' => 'Document not found'], statusCode: Http::STATUS_NOT_FOUND);
		}

		$entries = $this->audit->trail(fileId: $fileId);

		return new JSONResponse(data: ['results' => $entries, 'total' => count($entries)], statusCode: Http::STATUS_OK);

	}//end index()
}//end class
